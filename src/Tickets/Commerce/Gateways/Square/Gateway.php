<?php
/**
 * Square Gateway
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use TEC\Tickets\Commerce\Gateways\Contracts\Traits\Paid_Gateway;
use TEC\Tickets\Commerce\Payments_Tab;
use TEC\Tickets\Commerce\Settings as TC_Settings;
use TEC\Tickets\Commerce\Utils\Currency;
use Tribe__Tickets__Main as Tickets;
use Tribe__Template as Template;

/**
 * Square Gateway
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Gateway extends Abstract_Gateway {
	use Paid_Gateway;

	/**
	 * The Gateway key.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	protected static string $key = 'square';

	/**
	 * The Gateway settings class
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	protected static string $settings = Settings::class;

	/**
	 * The Gateway merchant class
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	protected static string $merchant = Merchant::class;

	/**
	 * Class used to manage the Orders for this Gateway
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	protected string $order_controller_class = Order::class;

	/**
	 * Supported currencies.
	 *
	 * @since 5.24.0
	 *
	 * @var string[]
	 */
	protected static array $supported_currencies = [
		'USD',
		'CAD',
		'GBP',
		'AUD',
		'JPY',
		'EUR',
	];

	/**
	 * Square tracking ID version.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Application ID for live mode.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const APPLICATION_ID_LIVE = 'sq0idp-8PoFNX4o9XOz9vMYOrZ6vA';

	/**
	 * Application ID for sandbox mode.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const APPLICATION_ID_SANDBOX = 'sandbox-sq0idb-JdEESM9hrZMuw36CFhL0mQ';

	/**
	 * Get the label for the Square Gateway.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public static function get_label(): string {
		return esc_html__( 'Square', 'event-tickets' );
	}

	/**
	 * Get the admin notices for the Square Gateway.
	 *
	 * @since 5.24.0
	 *
	 * @return array
	 */
	public function get_admin_notices(): array {
		return [
			[
				'slug'    => 'tc-square-signup-error',
				'content' => esc_html__( "Square wasn't able to complete your connection request. Try again.", 'event-tickets' ),
				'type'    => 'error',
			],
			[
				'slug'    => 'tc-square-token-error',
				'content' => esc_html__( 'Square signup was successful but the authentication tokens could not be retrieved. Try refreshing the tokens.', 'event-tickets' ),
				'type'    => 'error',
			],
			[
				'slug'    => 'tc-square-disconnect-error',
				'content' => esc_html__( 'Disconnecting from Square failed. Please try again.', 'event-tickets' ),
				'type'    => 'error',
			],
			[
				'slug'    => 'tc-square-currency-mismatch',
				'type'    => 'notice',
				'dismiss' => true,
			],
			[
				'slug'    => 'tc-square-account-disconnected',
				'content' => sprintf(
					// Translators: %1$s is the opening <a> tag for the Payments Tab page link. %2$s is the closing <a> tag.
					__( 'Your Square account was disconnected. If you believe this is an error, you can re-connect in the %1$sPayments Tab of the Settings Page%2$s.', 'event-tickets' ),
					'<a href="' . tribe( Payments_Tab::class )->get_url( [ 'tc-section' => self::get_key() ] ) . '">',
					'</a>'
				),
				'type'    => 'error',
				'dismiss' => true,
			],
		];
	}

	/**
	 * Get URL for the display logo.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_logo_url(): string {
		return esc_url( Tickets::instance()->plugin_url . 'src/resources/images/admin/square.png' );
	}

	/**
	 * Get text to use a subtitle when listing the gateway.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_subtitle(): string {
		return esc_html__( 'Enable credit card payments, Apple Pay, Google Pay, and more.', 'event-tickets' );
	}

	/**
	 * Generates a unique tracking ID for this website.
	 *
	 * The Tracking ID is a site-specific identifier that links the client and platform accounts in the Payment Gateway
	 * without exposing sensitive data. By default, the identifier generated is a URL in the format:
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function generate_unique_tracking_id() {
		return site_url( '/' ) . md5( uniqid( 'square', true ) );
	}

	/**
	 * Renders the template for the checkout.
	 *
	 * @since 5.24.0
	 *
	 * @param Template $template Template used to render the checkout.
	 *
	 * @return string
	 */
	public function render_checkout_template( Template $template ): string {
		$gateway_key   = static::get_key();
		$template_path = "gateway/{$gateway_key}/container";

		// This would need a Square_Elements class similar to Stripe_Elements.
		return $template->template( $template_path, [] );
	}

	/**
	 * Filter to add any admin notices that might be needed.
	 *
	 * @since 5.24.0
	 *
	 * @param array $notices Array of admin notices.
	 *
	 * @return array
	 */
	public function filter_admin_notices( $notices ) {
		// Check for unsupported currency.
		$selected_currency = tribe_get_option( TC_Settings::$option_currency_code );
		if ( $this->is_enabled() && ! $this->is_currency_supported( $selected_currency ) ) {
			$notices[] = [
				'tc-square-currency-not-supported',
				[ $this, 'render_unsupported_currency_notice' ],
				[
					'dismiss' => false,
					'type'    => 'error',
				],
			];
		}

		return $notices;
	}

	/**
	 * HTML for notice for unsupported currencies
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function render_unsupported_currency_notice() {
		$selected_currency = tribe_get_option( TC_Settings::$option_currency_code );
		$currency_name     = tribe( Currency::class )->get_currency_name( $selected_currency );

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

	/**
	 * Get the Square.js URL based on test mode.
	 *
	 * @since 5.24.0
	 *
	 * @return string The Square.js URL.
	 */
	public function get_square_js_url(): string {
		$is_test_mode  = $this->is_test_mode();
		$square_js_url = $is_test_mode
			? 'https://sandbox.web.squarecdn.com/v1/square.js'
			: 'https://web.squarecdn.com/v1/square.js';

		/**
		 * Filters the Square.js URL.
		 *
		 * @since 5.24.0
		 *
		 * @param string $square_js_url The Square.js URL.
		 * @param bool   $is_test_mode  Whether test mode is active.
		 */
		return apply_filters( 'tec_tickets_commerce_gateway_square_js_url', $square_js_url, $is_test_mode );
	}

	/**
	 * Get the Application ID for Square, with support for constant override and mode awareness.
	 *
	 * @since 5.24.0
	 *
	 * @return string The Square Application ID.
	 */
	public function get_application_id() {
		$mode          = $this->is_test_mode() ? 'SANDBOX' : 'LIVE';
		$constant_name = 'TEC_TICKETS_COMMERCE_SQUARE_APPLICATION_ID_' . $mode;

		// Check if external constant is defined.
		if ( defined( $constant_name ) ) {
			return constant( $constant_name );
		}

		// Generic fallback external constant.
		if ( defined( 'TEC_TICKETS_COMMERCE_SQUARE_APPLICATION_ID' ) ) {
			return constant( 'TEC_TICKETS_COMMERCE_SQUARE_APPLICATION_ID' );
		}

		// Get from class constants based on mode.
		$application_id = $this->is_test_mode()
			? static::APPLICATION_ID_SANDBOX
			: static::APPLICATION_ID_LIVE;

		/**
		 * Filter the Square Application ID.
		 *
		 * @since 5.24.0
		 *
		 * @param string $application_id The Application ID.
		 * @param string $mode           The current mode ('SANDBOX' or 'LIVE').
		 */
		return apply_filters(
			'tec_tickets_commerce_square_application_id',
			$application_id,
			$mode
		);
	}

	/**
	 * Determines if test mode is enabled.
	 *
	 * @since 5.24.0
	 *
	 * @return bool
	 */
	public static function is_test_mode(): bool {
		return tec_tickets_commerce_is_sandbox_mode();
	}

	/**
	 * Get the location ID to use for transactions based on the current mode.
	 *
	 * @since 5.24.0
	 *
	 * @return string The location ID or empty string if not set.
	 */
	public function get_location_id(): string {
		if ( static::is_test_mode() ) {
			return (string) tribe_get_option( Settings::OPTION_SANDBOX_LOCATION_ID, '' );
		}

		return (string) tribe_get_option( Settings::OPTION_LOCATION_ID, '' );
	}
}
