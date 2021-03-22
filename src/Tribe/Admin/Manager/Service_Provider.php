<?php

namespace Tribe\Tickets\Admin\Manager;

use tad_DI52_ServiceProvider;

/**
 * Class Manager
 *
 * @package Tribe\Tickets\Admin\Manager
 *
 * @since   5.1.0
 */
class Service_Provider extends tad_DI52_ServiceProvider {
	/**
	 * Register the provider singletons.
	 *
	 * @since 5.1.0
	 */
	public function register() {
		$this->container->singleton( 'tickets.admin.manager', self::class );

		$this->hooks();
	}

	/**
	 * Add actions and filters.
	 *
	 * @since 5.1.0
	 */
	protected function hooks() {
		if ( ! is_admin() ) {
			return;
		}

		// Handle AJAX.
		add_action( 'wp_ajax_nopriv_tribe_tickets_admin_manager', [ $this, 'ajax_handle_admin_manager' ] );
		add_action( 'wp_ajax_tribe_tickets_admin_manager', [ $this, 'ajax_handle_admin_manager' ] );
		add_action( 'tribe_settings_before_content_tab_event-tickets', [ $this, 'render_settings_banner' ] );
	}

	/**
	 * Handle response
	 *
	 * @since 5.1.0
	 */
	public function ajax_handle_admin_manager() {
		// @todo Look at adding capability checks of some sort based on a filter that provides capability context for the specific request.
		$response = [
			'html' => '',
		];

		if ( ! check_ajax_referer( 'tribe_tickets_admin_manager_nonce', 'nonce', false ) ) {
			$response['html'] = $this->render_error( __( 'Insecure request.', 'event-tickets' ) );

			wp_send_json_error( $response );
		}

		/*
		 * Get the request vars.
		 *
		 * Note to future developers: Using tribe_get_request_vars() here was removing non-string values (like arrays).
		 */
		$vars = $_REQUEST;

		/**
		 * Filter the admin manager request.
		 *
		 * @since 5.1.0
		 *
		 * @param string|\WP_Error $render_response The render response HTML content or WP_Error with list of errors.
		 * @param array            $vars            The request variables.
		 */
		$render_response = apply_filters( 'tribe_tickets_admin_manager_request', '', $vars );

		if ( is_string( $render_response ) && '' !== $render_response ) {
			// Return the HTML if it's a string.
			$response['html'] = $render_response;

			wp_send_json_success( $response );
		} elseif ( is_wp_error( $render_response ) ) {
			$response['html'] = $this->render_error( $render_response->get_error_messages() );

			wp_send_json_error( $response );
		}

		$response['html'] = $this->render_error( __( 'Something happened here.', 'event-tickets' ) );

		wp_send_json_error( $response );
	}

	/**
	 * Handle error rendering.
	 *
	 * @since 5.1.0
	 *
	 * @param string|array $error_message The error message(s).
	 *
	 * @return string The error template HTML.
	 */
	public function render_error( $error_message ) {

		// @todo @juanfra Re-check how we're going to deal with admin views. Ideally we should follow
		// the same model we do for FE, like the following:

		// // Set required template globals.
		// $args = [
		// 	'error_message' => $error_message,
		// ];

		// /** @var \Tribe__Tickets__Editor__Template $template */
		// $template = tribe( 'tickets.editor.template' );

		// // Add the rendering attributes into global context.
		// $template->add_template_globals( $args );

		// return $template->template( 'path/to/template/error', $args, false );

		return $error_message;
	}

	/**
	 * Render the Help banner for the Ticket Settings Tab.
	 *
	 * @since TBD
	 *
	 * @return string The help banner HTML content.
	 */
	public function render_settings_banner() {
		$et_resource_links = [
			[
				'label' => __( 'Getting Started Guide', 'event-tickets' ),
				'href'  => 'https://theeventscalendar.com/knowledgebase/guide/event-tickets/',
			],

			[
				'label' => __( 'Configuring PayPal for Ticket Purchases', 'event-tickets' ),
				'href'  => 'https://theeventscalendar.com/knowledgebase/k/configuring-paypal-for-ticket-purchases/',
			],
			[
				'label' => __( 'Configuring Tribe Commerce', 'event-tickets' ),
				'href'  => 'https://theeventscalendar.com/knowledgebase/k/configuring-tribe-commerce/',
			],
			[
				'label' => __( 'Managing Orders and Attendees', 'event-tickets' ),
				'href'  => 'https://theeventscalendar.com/knowledgebase/k/tickets-managing-your-orders-and-attendees/',
			],
			[
				'label' => __( 'Event Tickets Manual', 'event-tickets' ),
				'href'  => 'https://theeventscalendar.com/knowledgebase/product/event-tickets/',
			],
		];

		$etp_resource_links = [
			[
				'label' => __( 'Tickets & WooCommerce', 'event-tickets' ),
				'href'  => 'https://theeventscalendar.com/knowledgebase/k/woocommerce-specific-ticket-settings/',
			],

			[
				'label' => __( 'Creating Tickets', 'event-tickets' ),
				'href'  => 'https://theeventscalendar.com/knowledgebase/k/making-tickets/',
			],
			[
				'label' => __( 'Event Tickets and Event Tickets Plus Settings Overview', 'event-tickets' ),
				'href'  => 'https://theeventscalendar.com/knowledgebase/k/settings-overview-event-tickets-and-event-tickets-plus/',
			],
			[
				'label' => __( 'Event Tickets Plus Manual', 'event-tickets' ),
				'href'  => 'https://theeventscalendar.com/knowledgebase/product/event-tickets-plus/',
			],
		];

		$context = [
			'etp_enabled'        => class_exists( 'Tribe__Tickets_Plus__Main' ),
			'et_resource_links'  => $et_resource_links,
			'etp_resource_links' => $etp_resource_links,
		];

		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		return $admin_views->template( 'settings/getting-started', $context );
	}
}
