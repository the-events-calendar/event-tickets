<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Common\Contracts\Service_Provider;
use Tribe__Tickets__Main;

/**
 * Assets for Tickets Commerce Square gateway.
 *
 * @since TBD
 */
class Assets extends Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		$plugin = Tribe__Tickets__Main::instance();

		// Register the Square integration script
		tribe_asset(
			$plugin,
			'tec-tickets-commerce-square',
			'admin/tickets-commerce-square.js',
			[ 'jquery' ],
			'admin_enqueue_scripts',
			[
				'conditionals' => [ $this, 'should_enqueue_admin' ],
				'localize' => [
					[
						'name' => 'tribe_tickets_commerce_square_strings',
						'data' => [
							'connect' => __( 'Connect with Square', 'event-tickets' ),
							'connecting' => __( 'Connecting...', 'event-tickets' ),
							'connectError' => __( 'There was an error connecting to Square. Please try again.', 'event-tickets' ),
							'disconnectConfirm' => __( 'Are you sure you want to disconnect from Square?', 'event-tickets' ),
							'disconnectError' => __( 'There was an error disconnecting from Square. Please try again.', 'event-tickets' ),
							'connectNonce' => wp_create_nonce( 'square-connect' ),
						],
					],
				],
			]
		);
	}

	/**
	 * Determines if we should enqueue admin assets.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function should_enqueue_admin(): bool {
		$admin_page = tribe_get_request_var( 'page', false );
		$tab = tribe_get_request_var( 'tab', false );

		// Only enqueue on the correct admin page
		return 'tec-tickets-settings' === $admin_page && 'payments' === $tab;
	}
}
