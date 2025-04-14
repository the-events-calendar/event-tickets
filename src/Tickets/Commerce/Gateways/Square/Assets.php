<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Checkout;
use TEC\Tickets\Commerce\Gateways\Square\Gateway;
use TEC\Tickets\Commerce\Gateways\Square\REST\Order_Endpoint;
use TEC\Tickets\Commerce\Payments_Tab;

/**
 * Class Assets.
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Assets extends \TEC\Common\Contracts\Service_Provider {

	/**
	 * The nonce action to use when requesting the creation of a new order
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	const ORDER_NONCE_ACTION = 'tec_square_order';

	/**
	 * @inheritDoc
	 */
	public function register() {
		$plugin = \Tribe__Tickets__Main::instance();

		tribe_asset(
			$plugin,
			'tec-tickets-commerce-gateway-square-base',
			tribe( Gateway::class )->get_square_js_url(),
			[],
			null,
			[
				'type' => 'js',
			]
		);

		tribe_asset(
			$plugin,
			'tec-tickets-commerce-gateway-square-checkout',
			'commerce/gateway/square/checkout.js',
			[
				'jquery',
				'tribe-common',
				'tec-ky',
				'tribe-query-string',
				'tec-tickets-commerce-gateway-square-base',
				'tribe-tickets-loader',
				'tribe-tickets-commerce-js',
				'tribe-tickets-commerce-notice-js',
				'tribe-tickets-commerce-base-gateway-checkout-toggler',
			],
			'tec-tickets-commerce-checkout-shortcode-assets',
			[
				'module'       => true,
				'groups'       => [
					'tec-tickets-commerce-gateway-square',
				],
				'conditionals' => [ $this, 'should_enqueue_assets' ],
				'localize'     => [
					'name' => 'tecTicketsCommerceGatewaySquareCheckout',
					'data' => static function () {
						return apply_filters( 'tec_tickets_commerce_square_checkout_localized_data', [
							'nonce'             => wp_create_nonce( 'wp_rest' ),
							'orderEndpoint'     => tribe( Order_Endpoint::class )->get_route_url(),
							'applicationId'     => tribe( Gateway::class )->get_application_id(),
							'locationId'        => tribe( Merchant::class )->get_location_id(),
							'paymentData'       => tribe( Payment_Handler::class )->get_publishable_payment_data(),
							'squareCardOptions' => [
								'style'   => [
									'input' => [
										'color'             => '#23282d',
										'backgroundColor'   => '#ffffff',
										'fontSize'          => '14px',
									],
								],
							],
						] );
					},
				],
			]
		);

		// Tickets Commerce Square main frontend styles.
		tribe_asset(
			$plugin,
			'tribe-tickets-commerce-square-style',
			'tickets-commerce/gateway/square.css',
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
			'tec-tickets-commerce-gateway-square-admin-webhooks',
			'admin/gateway/square/webhooks.js',
			[
				'tribe-clipboard',
				'tribe-common',
				'tec-ky',
			],
			'admin_enqueue_scripts',
			[
				'conditionals' => [ $this, 'is_square_section' ]
			]
		);

		// Administration styles for Square gateway.
		tribe_asset(
			$plugin,
			'tec-tickets-commerce-gateway-square-admin-webhooks-styles',
			'tickets-commerce/admin/gateway/square/webhooks.css',
			[],
			'admin_enqueue_scripts',
			[
				'conditionals' => [ $this, 'is_square_section' ]
			]
		);
	}

	/**
	 * Determines if we are currently on the Square section of the settings.
	 *
	 * @since 5.3.0
	 *
	 * @return bool
	 */
	public function is_square_section() : bool {
		return Gateway::get_key() === tribe_get_request_var( Payments_Tab::$key_current_section_get_var );
	}

	/**
	 * Define if the assets for `Square` should be enqueued or not.
	 *
	 * @since 5.3.0
	 *
	 * @return bool If the `Square` assets should be enqueued or not.
	 */
	public function should_enqueue_assets() {
		return tribe( Checkout::class )->is_current_page() && tribe( Gateway::class )->is_enabled() && tribe( Gateway::class )->is_active();
	}
}
