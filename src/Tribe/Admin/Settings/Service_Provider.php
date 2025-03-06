<?php

namespace Tribe\Tickets\Admin\Settings;

use TEC\Common\Contracts\Service_Provider as Service_Provider_Contract;
use Tribe\Tickets\Admin\Settings;

/**
 * Class Manager
 *
 * @package Tribe\Tickets\Admin\Settings
 *
 * @since   5.1.2
 */
class Service_Provider extends Service_Provider_Contract {
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
				'label' => __( 'Event Tickets Manual', 'event-tickets' ),
				'href'  => 'https://evnt.is/1aoz',
			],
			[
				'label' => __( 'What is Tickets Commerce?', 'event-tickets' ),
				'href'  => 'https://evnt.is/1axs',
				'new'   => true,
			],
			[
				'label' => __( 'Configuring Tickets Commerce', 'event-tickets' ),
				'href'  => 'https://evnt.is/1axt',
				'new'   => true,
			],
			[
				'label' => __( 'Using RSVPs', 'event-tickets' ),
				'href'  => 'https://evnt.is/1aox',
			],
			[
				'label' => __( 'Managing Orders and Attendees', 'event-tickets' ),
				'href'  => 'https://evnt.is/1aoy',
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

	/**
	 * Render the Tickets Commerce Upgrade banner for the Ticket Settings Tab.
	 *
	 * @since 5.2.0
	 * @deprecated TBD
	 *
	 * @return array The help banner HTML content array.
	 */
	public function maybe_render_tickets_commerce_upgrade_banner( $commerce_fields ) {
		_deprecated_function( __FUNCTION__, 'TBD' );
		return $commerce_fields;
	}

	/**
	 * Render the Tickets Commerce Notice banner for the Ticket Settings Tab.
	 *
	 * @since 5.2.0
	 * @deprecated TBDs
	 *
	 * @return array The help banner HTML content array.
	 */
	public function maybe_render_tickets_commerce_notice_banner( $commerce_fields ) {
		_deprecated_function( __FUNCTION__, 'TBD' );
		return $commerce_fields;
	}
}
