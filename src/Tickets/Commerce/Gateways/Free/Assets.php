<?php
/**
 * Handles registering and setup for assets for Free gateway.
 *
 * @since TBD
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
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Assets extends Service_Provider {
	
	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		$plugin = \Tribe__Tickets__Main::instance();
		
		tribe_asset(
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
		
		tribe_asset(
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
	 * Define if the assets for `PayPal` should be enqueued or not.
	 *
	 * @since TBD
	 *
	 * @return bool If the `PayPal` assets should be enqueued or not.
	 */
	public function should_enqueue_assets() {
		return tribe( Checkout::class )->is_current_page() && tribe( Gateway::class )->is_enabled() && tribe( Gateway::class )->is_active();
	}
}
