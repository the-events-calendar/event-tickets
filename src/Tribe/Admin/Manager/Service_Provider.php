<?php

namespace Tribe\Tickets\Admin\Manager;

use tad_DI52_ServiceProvider;

/**
 * Class Manager
 *
 * @package Tribe\Tickets\Admin\Manager
 *
 * @since   TBD
 */
class Service_Provider extends tad_DI52_ServiceProvider {
	/**
	 * Register the provider singletons.
	 *
	 * @since TBD
	 */
	public function register() {
		$this->container->singleton( 'tickets.admin.manager', self::class );

		$this->hooks();
	}

	/**
	 * Add actions and filters.
	 *
	 * @since TBD
	 */
	protected function hooks() {
		// Handle AJAX.
		add_action( 'wp_ajax_nopriv_tribe_tickets_admin_manager', [ $this, 'ajax_handle_admin_manager' ] );
		add_action( 'wp_ajax_tribe_tickets_admin_manager', [ $this, 'ajax_handle_admin_manager' ] );
	}

	/**
	 * Handle response
	 *
	 * @since TBD
	 */
	public function ajax_handle_admin_manager() {
		$response = [
			'html' => '',
		];

		// Get the request vars.
		$vars = tribe_get_request_vars();

		/**
		 * Filter the admin manager request.
		 *
		 * @since TBD
		 *
		 * @param array $vars The request vars.
		 *
		 * @return mixed The response.
		 */
		$render_response = apply_filters( 'tribe_tickets_admin_manager_request', $vars );

		if ( is_string( $render_response ) && '' !== $render_response ) {
			// Return the HTML if it's a string.
			$response['html'] = $render_response;

			wp_send_json_success( $response );
		} elseif ( is_array( $render_response ) && ! empty( $render_response['errors'] ) ) {
			$response['html'] = $this->render_error( $render_response['errors'] );

			wp_send_json_error( $response );
		}

		$response['html'] = $this->render_error( __( 'Something happened here.', 'event-tickets' ) );

		wp_send_json_error( $response );
	}

	/**
	 * Handle error rendering.
	 *
	 * @since TBD
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
}
