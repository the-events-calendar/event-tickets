<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Checkout;
use TEC\Tickets\Commerce\Gateways\Stripe\REST\Order_Endpoint;
use TEC\Tickets\Commerce\Payments_Tab;

/**
 * Class Assets.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Assets extends \tad_DI52_ServiceProvider {

	/**
	 * The nonce action to use when requesting the creation of a new order
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ORDER_NONCE_ACTION = 'tec_stripe_order';

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
			null,
			[
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
				'tec-ky',
				'tribe-query-string',
				'tec-tickets-commerce-gateway-stripe-base',
				'tribe-tickets-loader',
				'tribe-tickets-commerce-js',
				'tribe-tickets-commerce-notice-js',
				'tribe-tickets-commerce-base-gateway-checkout-toggler',
			],
			null,
			[
				'module'       => true,
				'groups'       => [
					'tribe-tickets-commerce-checkout',
					'tec-tickets-commerce-gateway-stripe',
				],
				'conditionals' => [ $this, 'should_enqueue_assets' ],
				'localize'     => [
					'name' => 'tecTicketsCommerceGatewayStripeCheckout',
					'data' => static function () {
						return apply_filters( 'tec_tickets_commerce_stripe_checkout_localized_data', [
							'nonce'             => wp_create_nonce( 'wp_rest' ),
							'orderEndpoint'     => tribe( Order_Endpoint::class )->get_route_url(),
							'paymentElement'    => tribe( Stripe_Elements::class )->include_payment_element(),
							'cardElementType'   => tribe( Stripe_Elements::class )->card_element_type(),
							'publishableKey'    => tribe( Merchant::class )->get_publishable_key(),
							'paymentIntentData' => tribe( Client::class )->get_publishable_payment_intent_data(),
							'elementsAppearance' => [
								'variables' => [
									'borderRadius'   => '4px',
									'colorPrimary'   => '#334aff',
									'fontFamily'     => 'Helvetica Neue, Helvetica, -apple-system, BlinkMacSystemFont, Roboto, Arial, sans-serif',
								],
								'rules' => [
									'.Tab'           => [
										'borderColor' => '#d5d5d5',
										'boxShadow'   => 'none'
									],
									'.Tab--selected' => [
										'borderWidth' => '2px'
									],
									'.TabLabel'      => [
										'paddingTop'  => '6px'
									],
									'.Input'         => [
										'boxShadow'   => 'none'
									]
								]
							],
						] );
					},
				],
			]
		);

		// Tickets Commerce stripe main frontend styles.
		tribe_asset(
			$plugin,
			'tribe-tickets-commerce-stripe-style',
			'tickets-commerce/gateway/stripe.css',
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

		// Administration JS for Webhooks.
		tribe_asset(
			$plugin,
			'tec-tickets-commerce-gateway-stripe-admin-webhooks',
			'admin/gateway/stripe/webhooks.js',
			[
				'tribe-clipboard',
				'tribe-common',
				'tec-ky',
			],
			'admin_enqueue_scripts',
			[
				'conditionals' => [ $this, 'is_stripe_section' ]
			]
		);

		// Administration JS for Webhooks.
		tribe_asset(
			$plugin,
			'tec-tickets-commerce-gateway-stripe-admin-webhooks-styles',
			'tickets-commerce/admin/gateway/stripe/webhooks.css',
			[
			],
			'admin_enqueue_scripts',
			[
				'conditionals' => [ $this, 'is_stripe_section' ]
			]
		);
	}

	/**
	 * Determines if we are currently on the stripe section of the settings.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_stripe_section() : bool {
		return Gateway::get_key() === tribe_get_request_var( Payments_Tab::$key_current_section_get_var );
	}

	/**
	 * Define if the assets for `Stripe` should be enqueued or not.
	 *
	 * @since TBD
	 *
	 * @return bool If the `Stripe` assets should be enqueued or not.
	 */
	public function should_enqueue_assets() {
		return tribe( Gateway::class )->is_active() && tribe( Checkout::class )->is_current_page();
	}
}
