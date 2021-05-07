<?php

namespace Tribe\Tickets\Admin\Settings;

use tad_DI52_ServiceProvider;

/**
 * Class Manager
 *
 * @package Tribe\Tickets\Admin\Settings
 *
 * @since   5.1.2
 */
class Service_Provider extends tad_DI52_ServiceProvider {
	/**
	 * Register the provider singletons.
	 *
	 * @since 5.1.2
	 */
	public function register() {
		$this->container->singleton( 'tickets.admin.settings', self::class );

		$this->hooks();
	}

	/**
	 * Add actions and filters.
	 *
	 * @since 5.1.2
	 */
	protected function hooks() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'tribe_settings_before_content_tab_event-tickets', [ $this, 'render_settings_banner' ] );
	}

	/**
	 * Render the Help banner for the Ticket Settings Tab.
	 *
	 * @since 5.1.2
	 *
	 * @return string The help banner HTML content.
	 */
	public function render_settings_banner() {
		$et_resource_links = [
			[
				'label' => __( 'Getting Started Guide', 'event-tickets' ),
				'href'  => 'https://evnt.is/1aot',
			],

			[
				'label' => __( 'Configuring PayPal for Ticket Purchases', 'event-tickets' ),
				'href'  => 'https://evnt.is/1aou',
			],
			[
				'label' => __( 'Configuring Tribe Commerce', 'event-tickets' ),
				'href'  => 'https://evnt.is/1aov',
			],
			[
				'label' => __( 'Using RSVPs', 'event-tickets' ),
				'href'  => 'https://evnt.is/1aox',
			],
			[
				'label' => __( 'Managing Orders and Attendees', 'event-tickets' ),
				'href'  => 'https://evnt.is/1aoy',
			],
			[
				'label' => __( 'Event Tickets Manual', 'event-tickets' ),
				'href'  => 'https://evnt.is/1aoz',
			],
		];

		$etp_resource_links = [
			[
				'label' => __( 'Switching from Tribe Commerce to WooCommerce', 'event-tickets' ),
				'href'  => 'https://evnt.is/1ao-',
			],
			[
				'label' => __( 'Setting Up E-Commerce Plugins for Selling Tickets', 'event-tickets' ),
				'href'  => 'https://evnt.is/1ap0',
			],
			[
				'label' => __( 'Tickets & WooCommerce', 'event-tickets' ),
				'href'  => 'https://evnt.is/1ap1',
			],
			[
				'label' => __( 'Creating Tickets', 'event-tickets' ),
				'href'  => 'https://evnt.is/1ap2',
			],
			[
				'label' => __( 'Event Tickets and Event Tickets Plus Settings Overview', 'event-tickets' ),
				'href'  => 'https://evnt.is/1ap3',
			],
			[
				'label' => __( 'Event Tickets Plus Manual', 'event-tickets' ),
				'href'  => 'https://evnt.is/1ap4',
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
