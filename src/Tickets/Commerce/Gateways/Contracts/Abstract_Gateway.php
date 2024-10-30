<?php
/**
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways
 */

namespace TEC\Tickets\Commerce\Gateways\Contracts;

use TEC\Tickets\Commerce;
use TEC\Tickets\Commerce\Gateways\Manager;
use TEC\Tickets\Commerce\Payments_Tab;
use Tribe__Utils__Array as Arr;

/**
 * Abstract Gateway Contract.
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */
abstract class Abstract_Gateway implements Gateway_Interface {

	/**
	 * The Gateway key.
	 *
	 * @since 5.3.0
	 */
	protected static $key;

	/**
	 * The Gateway settings class
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	protected static $settings;

	/**
	 * The Gateway merchant class
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	protected static $merchant;

	/**
	 * Supported currencies.
	 *
	 * @since 5.3.2
	 *
	 * @var string[]
	 */
	protected static $supported_currencies = [];

	/**
	 * The option name prefix that configured whether a gateway is enabled.
	 * It is followed by the gateway 'key'
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $option_enabled_prefix = '_tickets_commerce_gateway_enabled_';

	/**
	 * Default name for the checkout template.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $checkout_container_template_name = 'container';

	/**
	 * Class used to manage the Orders for this Gateway
	 *
	 * @since 5.6.0
	 *
	 * @var string
	 */
	protected string $order_controller_class = Commerce\Order::class;

	/**
	 * @inheritDoc
	 */
	public static function get_key() {
		return static::$key;
	}

	/**
	 * @inheritDoc
	 */
	public static function get_provider_key() {
		return Commerce::PROVIDER . '-' . static::get_key();
	}

	/**
	 * @inheritDoc
	 */
	public function register_gateway( array $gateways ) {
		$gateways[ static::get_key() ] = $this;

		return $gateways;
	}

	/**
	 * @inheritDoc
	 */
	public static function is_connected() {
		// If this gateway shouldn't be shown, then don't change the active status.
		if ( ! static::should_show() ) {
			return false;
		}

		return tribe( static::$merchant )->is_connected();
	}

	/**
	 * @inheritDoc
	 */
	public static function is_active() {
		// If this gateway shouldn't be shown, then don't change the active status.
		if ( ! static::should_show() ) {
			return false;
		}

		return tribe( static::$merchant )->is_active();
	}

	/**
	 * Determine whether Tickets Commerce is in test mode.
	 *
	 * @since 5.1.6
	 *
	 * @return bool Whether Tickets Commerce is in test mode.
	 */
	public static function is_test_mode() {

		if ( Commerce\Settings::is_test_mode() ) {
			return true;
		}

		return tribe_is_truthy( tribe( static::$settings )->is_gateway_test_mode() );
	}

	/**
	 * @inheritDoc
	 */
	public static function should_show() {
		return true;
	}

	/**
	 * Fetches the Gateway Order Controller.
	 *
	 * @since 5.6.0
	 *
	 * @return Commerce\Abstract_Order
	 */
	public function get_order_controller(): Commerce\Abstract_Order {
		return tribe( $this->order_controller_class );
	}

	/**
	 * @inheritDoc
	 */
	public function get_settings() {
		return tribe( static::$settings )->get_settings();
	}

	/**
	 * @inheritDoc
	 */
	public static function get_settings_url( array $args = [] ) {
		// Force the Tickets Commerce section to be this gateway.
		$args[ Payments_Tab::$key_current_section_get_var ] = static::get_key();

		// Pass it to the get_url of the payments tab.
		return tribe( Payments_Tab::class )->get_url( $args );
	}

	/**
	 * @inheritDoc
	 */
	public function handle_invalid_response( $response, $message, $slug = 'error' ) {

		$notices = tribe( Commerce\Notice_Handler::class );
		$body    = (array) json_decode( wp_remote_retrieve_body( $response ) );

		$error         = isset( $body['error'] ) ? $body['error'] : __( 'Something went wrong!', 'event-tickets' );
		$error_message = $body['error_description'] ?? __( 'Unexpected response received.', 'event-tickets' );

		$notices->trigger_admin(
			$slug,
			[
				'content' => sprintf( 'Error - %s : %s - %s', $error, $error_message, $message ),
				'type'    => 'error',
			]
		);
	}

	/**
	 * Generates a Tracking ID for this website.
	 *
	 * The Tracking ID is a site-specific identifier that links the client and platform accounts in the Payment Gateway
	 * without exposing sensitive data. By default, the identifier generated is a URL in the format:
	 *
	 * {SITE_URL}?v={GATEWAY_VERSION}-{RANDOM_6_CHAR_HASH}
	 *
	 * @since 5.3.0 moved to Abstract_Gateway
	 * @since 5.1.9
	 *
	 * @return string
	 */
	public function generate_unique_tracking_id() {
		$id      = wp_generate_password( 6, false, false );;
		$url_frags = wp_parse_url( home_url() );
		$url       = Arr::get( $url_frags, 'host' ) . Arr::get( $url_frags, 'path' );
		$url       = add_query_arg( [
			'v' => static::VERSION . '-' . $id,
		], $url );

		// Always limit it to 127 chars.
		return substr( $url, 0, 127 );
	}

	/**
	 * Get URL for the display logo.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_logo_url(): string {
		return '';
	}

	/**
	 * Get text to use a subtitle when listing gateways.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_subtitle(): string {
		return '';
	}

	/**
	 * Returns the enabled option key.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public static function get_enabled_option_key(): string {
		return static::$option_enabled_prefix . self::get_key();
	}

	/**
	 * Returns if gateway is enabled.
	 *
	 * @since 5.3.0
	 *
	 * @return boolean
	 */
	public static function is_enabled(): bool {
		if ( ! static::should_show() ) {
			return false;
		}

		return tribe_is_truthy( tribe_get_option( static::get_enabled_option_key() ) );
	}

	/**
	 * Returns status text.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public static function get_status_text(): string {
		if ( ! static::is_enabled() || ! static::is_active() ) {
			return '';
		}

		return __( 'Enabled for Checkout', 'event-tickets' );
	}

	/**
	 * Returns name of the container template within the `views/v2/commerce/gateway/{key}/` folder.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public static function get_checkout_container_template_name() {
		return self::$checkout_container_template_name;
	}

	/**
	 * @inheritDoc
	 */
	public static function get_checkout_template_vars() {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function render_checkout_template( \Tribe__Template $template ): string {
		return '';
	}

	/**
	 * Disable the gateway toggle.
	 *
	 * @since 5.3.0
	 *
	 * @return bool
	 */
	public static function disable() {
		if ( ! static::is_enabled() ) {
			return true;
		}

		return tribe_remove_option( static::get_enabled_option_key() );
	}

	/**
	 * Get supported currencies.
	 *
	 * @since 5.3.2
	 *
	 * @return string[]
	 */
	public static function get_supported_currencies() {
		/**
		 * Filter to modify supported currencies for this gateway.
		 *
		 * @since 5.3.2
		 *
		 * @param string[] $supported_currencies Array of three-letter, supported currency codes.
		 */
		return apply_filters( 'tec_tickets_commerce_gateway_supported_currencies_' . static::$key, static::$supported_currencies );
	}

	/**
	 * Is currency supported.
	 *
	 * @since 5.3.2
	 *
	 * @param string $currency_code Currency code.
	 *
	 * @return bool
	 */
	public static function is_currency_supported( $currency_code ) {
		if ( empty( $currency_code ) ) {
			return false;
		}

		$supported_currencies = static::get_supported_currencies();

		// If supported currencies aren't set, assume it's supported.
		if ( empty( $supported_currencies ) ) {
			return true;
		}
		return in_array( $currency_code, $supported_currencies, true );
	}
}
