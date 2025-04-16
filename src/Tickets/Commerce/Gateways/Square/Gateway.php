<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use TEC\Tickets\Commerce\Gateways\Contracts\Traits\Paid_Gateway;
use TEC\Tickets\Commerce\Payments_Tab;
use TEC\Tickets\Commerce\Settings as TC_Settings;
use TEC\Tickets\Commerce\Utils\Currency;
use Tribe__Tickets__Main;
use Tribe__Utils__Array as Arr;

/**
 * Class Gateway
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Gateway extends Abstract_Gateway {
	use Paid_Gateway;

	/**
	 * @inheritDoc
	 */
	protected static $key = 'square';

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
		'USD', 'CAD', 'GBP', 'AUD', 'JPY', 'EUR'
	];

	/**
	 * Square tracking ID version.
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
		return __( 'Square', 'event-tickets' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_admin_notices() {
		$notices = [
			[
				'slug'    => 'tc-square-signup-error',
				'content' => __( "Square wasn't able to complete your connection request. Try again.", 'event-tickets' ),
				'type'    => 'error',
			],
			[
				'slug'    => 'tc-square-token-error',
				'content' => __( 'Square signup was successful but the authentication tokens could not be retrieved. Try refreshing the tokens.', 'event-tickets' ),
				'type'    => 'error',
			],
			[
				'slug'    => 'tc-square-disconnect-error',
				'content' => __( 'Disconnecting from Square failed. Please try again.', 'event-tickets' ),
				'type'    => 'error',
			],
			[
				'slug' => 'tc-square-currency-mismatch',
				'type' => 'notice',
				'dismiss' => true,
			],
			[
				'slug'    => 'tc-square-account-disconnected',
				'content' => sprintf(
					// Translators: %1$s is the opening <a> tag for the Payments Tab page link. %2$s is the closing <a> tag.
					__( 'Your Square account was disconnected. If you believe this is an error, you can re-connect in the %1$sPayments Tab of the Settings Page%2$s.', 'event-tickets' ),
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
		return Tribe__Tickets__Main::instance()->plugin_url . 'src/resources/images/admin/square-logo.png';
	}

	/**
	 * @inheritDoc
	 */
	public function get_subtitle(): string {
		return __( 'Enable credit card payments, Apple Pay, Google Pay, and more.', 'event-tickets' );
	}

	/**
	 * @inheritDoc
	 */
	public function generate_unique_tracking_id() {
		return site_url( '/' ) . md5( uniqid( 'square', true ) );
	}

	/**
	 * @inheritDoc
	 */
	public function render_checkout_template( \Tribe__Template $template ): string {
		$gateway_key   = static::get_key();
		$template_path = "gateway/{$gateway_key}/container";

		// This would need a Square_Elements class similar to Stripe_Elements
		return $template->template( $template_path, [] );
	}

	/**
	 * Filter to add any admin notices that might be needed.
	 *
	 * @since 5.3.0
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
				'tc-square-currency-not-supported',
				[ $this, 'render_unsupported_currency_notice' ],
				[ 'dismiss' => false, 'type' => 'error' ],
			];
		}

		return $notices;
	}

	/**
	 * HTML for notice for unsupported currencies
	 *
	 * @since 5.3.0
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
			esc_url( 'https://developer.squareup.com/docs/payment-card-support-by-country' ),
			esc_html__( 'here', 'event-tickets' )
		);
		$notice_header = esc_html__( 'Square doesn\'t support your selected currency', 'event-tickets' );
		$notice_text = sprintf(
			// Translators: %1$s: Currency Name. %2$s: Link to gateway provider's currency documentation.
			esc_html__( 'Unfortunately, Square doesn\'t support payments in %1$s. Please try using a different gateway or adjusting your Tickets Commerce currency setting. You can see a list of supported currencies %2$s.', 'event-tickets' ),
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
