<?php
/**
 * Handles registering and setup for assets for Free gateway.
 *
 * @since 5.10.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Free
 */

namespace TEC\Tickets\Commerce\Gateways\Free;

use TEC\Tickets\Commerce\Checkout;
use TEC\Common\Contracts\Service_Provider;
use TEC\Tickets\Commerce\Gateways\Free\REST\Order_Endpoint;

/**
 * Class Assets.
 *
 * @since 5.10.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Free
 */
class Assets extends Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.10.0
	 */
	public function register(): void {
		$plugin = \Tribe__Tickets__Main::instance();

		tec_asset(
			$plugin,
			'tec-tickets-commerce-gateway-free-checkout',
			'commerce/gateway/free/checkout.js',
			[
				'jquery',
				'tribe-common',
				'tec-ky',
				'tribe-query-string',
				'tribe-tickets-loader',
				'tribe-tickets-commerce-js',
				'tribe-tickets-commerce-notice-js',
				'tribe-tickets-commerce-base-gateway-checkout-toggler',
			],
			'tec-tickets-commerce-checkout-shortcode-assets',
			[
				'groups'       => [
					'tec-tickets-commerce-gateway-free',
				],
				'conditionals' => [ $this, 'should_enqueue_assets' ],
				'localize'     => [
					'name' => 'tecTicketsCommerceGatewayFreeCheckout',
					'data' => static function () {
						return [
							'orderEndpoint' => tribe( Order_Endpoint::class )->get_route_url(),
							'nonce'         => wp_create_nonce( 'wp_rest' ),
						];
					},
				],
			]
		);

		tec_asset(
			$plugin,
			'tribe-tickets-commerce-free-style',
			'tickets-commerce/gateway/free.css',
			[
				'tribe-common-skeleton-style',
				'tribe-common-full-style',
			],
			null,
			[
				'groups' => [
					'tribe-tickets-commerce',
					'tribe-tickets-commerce-checkout',
				],
				'print'  => true,
			]
		);
	}

	/**
	 * Define if the assets should be enqueued or not.
	 *
	 * @since 5.10.0
	 *
	 * @return bool If the assets should be enqueued or not.
	 */
	public function should_enqueue_assets(): bool {
		return tribe( Checkout::class )->is_current_page() && tribe( Gateway::class )->is_enabled() && tribe( Gateway::class )->is_active();
	}
}
