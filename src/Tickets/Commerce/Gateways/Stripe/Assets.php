<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Stripe\REST\Order_Endpoint;
use TEC\Tickets\Commerce\Gateways\Stripe\REST\Publishable_Key_Endpoint;

class Assets extends \tad_DI52_ServiceProvider {

	const PUBLISHABLE_KEY_NONCE_ACTION = 'stripe_pubkey';

	const ORDER_NONCE_ACTION = 'stripe_order';

	/**
	 * @inheritDoc
	 */
	public function register() {
		$plugin = \Tribe__Tickets__Main::instance();

		tribe_asset(
			$plugin,
			'tec-tickets-commerce-gateway-stripe-base',
			'https://js.stripe.com/v3/',
			[],
			'wp_enqueue_scripts',
			[
		//		'conditionals' => [ $this, 'should_enqueue_assets' ],
				'localize' => [],
				'type' => 'js',
			]
		);

		tribe_asset(
			$plugin,
			'tec-tickets-commerce-gateway-stripe-checkout',
			'commerce/gateway/stripe/checkout.js',
			[
				'jquery',
				'tribe-common',
				'tribe-tickets-loader',
				'tribe-tickets-commerce-js',
				'tribe-tickets-commerce-notice-js',
				'tribe-tickets-commerce-base-gateway-checkout-js',
			],
			'wp_enqueue_scripts',
			[
				'groups'       => [
					'tec-tickets-commerce-gateway-stripe',
				],
				//'conditionals' => [ $this, 'should_enqueue_assets' ],
				'localize'     => [
					'name' => 'tecTicketsCommerceGatewayStripeCheckout',
					'data' => static function () {
						return [
							'keyEndpoint' => tribe( Publishable_Key_Endpoint::class )->get_route_url(),
							'keyNonce' => wp_create_nonce( static::PUBLISHABLE_KEY_NONCE_ACTION ),
							'orderEndpoint' => tribe( Order_Endpoint::class )->get_route_url(),
							'orderNonce' => wp_create_nonce( static::ORDER_NONCE_ACTION ),
						];
					},
				],
			]
		);
	}

	/**
	 * Define if the assets for `Stripe` should be enqueued or not.
	 *
	 * @since TBD
	 *
	 * @return bool If the `Stripe` assets should be enqueued or not.
	 */
	public function should_enqueue_assets() {
		return true;
		return tribe( Gateway::class )->is_active();
	}
}