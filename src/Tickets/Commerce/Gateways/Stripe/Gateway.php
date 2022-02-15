<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use TEC\Tickets\Commerce\Gateways\Stripe\REST\Return_Endpoint;
use TEC\Tickets\Commerce\Payments_Tab;
use \Tribe__Tickets__Main;
use Tribe__Utils__Array as Arr;

/**
 * Class Gateway
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Gateway extends Abstract_Gateway {

	/**
	 * @inheritDoc
	 */
	protected static $key = 'stripe';

	/**
	 * @inheritDoc
	 */
	protected static $settings = Settings::class;

	/**
	 * @inheritDoc
	 */
	protected static $merchant = Merchant::class;

	/**
	 * Stripe tracking ID version.
	 *
	 * This shouldn't be updated unless we are modifying something on the Stripe user level.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * @inheritDoc
	 */
	public static function get_label() {
		return __( 'Stripe', 'event-tickets' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_admin_notices() {
		$notices = [
			[
				'slug'    => 'tc-stripe-signup-error',
				'content' => __( "Stripe wasn't able to complete your connection request. Try again.", 'event-tickets' ),
				'type'    => 'error',
			],
			[
				'slug'    => 'tc-stripe-token-error',
				'content' => __( 'Stripe signup was successful but the authentication tokens could not be retrieved. Try refreshing the tokens.', 'event-tickets' ),
				'type'    => 'error',
			],
			[
				'slug'    => 'tc-stripe-disconnect-error',
				'content' => __( 'Disconnecting from Stripe failed. Please try again.', 'event-tickets' ),
				'type'    => 'error',
			],
			[
				'slug' => 'tc-stripe-currency-mismatch',
				'type' => 'notice',
				'dismiss' => true,
			],
			[
				'slug'    => 'tc-stripe-country-denied',
				'content' => __( 'Due to Regulatory Issues between Stripe and the country listed in your Stripe account, the free version of Event Tickets cannot accept connections from accounts in your country. Please use a Stripe account from a different country or purchase Event Tickets Plus to continue.', 'event-tickets' ),
				'type'    => 'error',
			],
			[
				'slug'    => 'tc-stripe-account-disconnected',
				'content' => sprintf(
					// Translators: %1$s is the opening <a> tag for the Payments Tab page link. %2$s is the closing <a> tag.
					__( 'Your stripe account was disconnected from the Stripe dashboard. If you believe this is an error, you can re-connect in the %1$sPayments Tab of the Settings Page%2$s.', 'event-tickets' ),
					'<a href="' . tribe( Payments_Tab::class )->get_url( [ 'tc-section' => Gateway::get_key() ] ) . '">',
					'</a>' ),
				'type'    => 'error',
				'dismiss' => true,
			],
		];

		return $notices;
	}

	/**
	 * @inheritDoc
	 */
	public function get_logo_url(): string {
		return Tribe__Tickets__Main::instance()->plugin_url . 'src/resources/images/admin/stripe-logo.png';
	}

	/**
	 * @inheritDoc
	 */
	public function get_subtitle(): string {
		return __( 'Enable credit card payments, Afterpay, AliPay, Giropay, Klarna and more.', 'event-tickets' );
	}

	/**
	 * @inheritDoc
	 */
	public function generate_unique_tracking_id() {
		return tribe( Return_Endpoint::class )->get_route_url();
	}

	/**
	 * @inheritDoc
	 */
	public function render_checkout_template( \Tribe__Template $template ): string {
		$gateway_key   = static::get_key();
		$template_path = "gateway/{$gateway_key}/container";

		return $template->template( $template_path, tribe( Stripe_Elements::class )->get_checkout_template_vars() );
	}
}
