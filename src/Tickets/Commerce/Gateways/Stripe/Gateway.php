<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use TEC\Tickets\Commerce\Gateways\Stripe\REST\Return_Endpoint;
use TEC\Tickets\Commerce\Payments_Tab;
use TEC\Tickets\Commerce\Settings as TC_Settings;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Utils\Currency;
use \Tribe__Tickets__Main;
use Tribe__Utils__Array as Arr;

/**
 * Class Gateway
 *
 * @since   5.3.0
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
	 * @inheritDoc
	 */
	protected string $order_controller_class = Order::class;

	/**
	 * @inheritDoc
	 */
	protected static $supported_currencies = [
		'USD', 'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN', 'BAM', 'BBD',
		'BDT', 'BGN', 'BIF', 'BMD', 'BND', 'BOB', 'BRL', 'BSD', 'BWP', 'BYN', 'BZD', 'CAD', 'CDF',
		'CHF', 'CLP', 'CNY', 'COP', 'CRC', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EGP', 'ETB',
		'EUR', 'FJD', 'FKP', 'GBP', 'GEL', 'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK',
		'HTG', 'HUF', 'IDR', 'ILS', 'INR', 'ISK', 'JMD', 'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KRW',
		'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT',
		'MOP', 'MRO', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'MZN', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR',
		'NZD', 'PAB', 'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'PYG', 'QAR', 'RON', 'RSD', 'RUB', 'RWF',
		'SAR', 'SBD', 'SCR', 'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SRD', 'STD', 'SZL', 'THB', 'TJS',
		'TOP', 'TRY', 'TTD', 'TWD', 'TZS', 'UAH', 'UGX', 'UYU', 'UZS', 'VND', 'VUV', 'WST', 'XAF',
		'XCD', 'XOF', 'XPF', 'YER', 'ZAR', 'ZMW',
	];

	/**
	 * Stripe tracking ID version.
	 *
	 * This shouldn't be updated unless we are modifying something on the Stripe user level.
	 *
	 * @since 5.3.0
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

	/**
	 * Filter to add any admin notices that might be needed.
	 *
	 * @since 5.3.2
	 *
	 * @param array Array of admin notices.
	 *
	 * @return array
	 */
	public function filter_admin_notices( $notices ) {

		// Check for unsupported currency.
		$selected_currency = tribe_get_option( TC_Settings::$option_currency_code );
		if ( $this->is_enabled() && ! $this->is_currency_supported( $selected_currency ) ){
			$notices[] = [
				'tc-stripe-currency-not-supported',
				[ $this, 'render_unsupported_currency_notice' ],
				[ 'dismiss' => false, 'type' => 'error' ],
			];
		}

		return $notices;
	}

	/**
	 * HTML for notice for unsupported currencies
	 *
	 * @since 5.3.2
	 *
	 * @return string
	 */
	public function render_unsupported_currency_notice() {
		$selected_currency = tribe_get_option( TC_Settings::$option_currency_code );
		$currency_name = tribe( Currency::class )->get_currency_name( $selected_currency );
		// If we don't have the currency name configured, use the currency code instead.
		if ( empty( $currency_name ) ) {
			$currency_name = $selected_currency;
		}
		$notice_link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( 'https://stripe.com/docs/currencies' ),
			esc_html__( 'here', 'event-tickets' )
		);
		$notice_header = esc_html__( 'Stripe doesn\'t support your selected currency', 'event-tickets' );
		$notice_text = sprintf(
			// Translators: %1$s: Currency Name. %2$s: Link to gateway provider's currency documentation.
			esc_html__( 'Unfortunately, Stripe doesn\'t support payments in %1$s. Please try using a different gateway or adjusting your Tickets Commerce currency setting. You can see a list of supported currencies %2$s.', 'event-tickets' ),
			$currency_name,
			$notice_link
		);

		return sprintf(
			'<p><strong>%1$s</strong></p><p>%2$s</p>',
			$notice_header,
			$notice_text
		);
	}
}
