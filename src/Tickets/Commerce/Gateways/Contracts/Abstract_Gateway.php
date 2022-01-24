<?php
/**
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets\Commerce\Gateways
 */

namespace TEC\Tickets\Commerce\Gateways\Contracts;

use TEC\Tickets\Commerce;
use Tribe__Utils__Array as Arr;

/**
 * Abstract Gateway Contract
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */
abstract class Abstract_Gateway implements Gateway_Interface {

	/**
	 * The Gateway key.
	 *
	 * @since 5.1.6
	 */
	protected static $key;

	/**
	 * The Gateway settings class
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $settings;

	/**
	 * The Gateway merchant class
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $merchant;

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
	 * @inheritDoc
	 */
	public function get_settings() {
		return tribe( static::$settings )->get_settings();
	}

	/**
	 * @inheritDoc
	 */
	public function handle_invalid_response( $response, $message, $slug = 'error' ) {

		$notices = tribe( Notice_Handler::class );
		$body    = (array) json_decode( wp_remote_retrieve_body( $response ) );

		$error = isset( $body['error'] ) ? $body['error'] : __( 'Something went wrong!' , 'event-tickets' );
		$error_message = isset( $body['error_description'] ) ? $body['error_description'] : __( 'Unexpected response recieved.' , 'event-tickets' );

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
	 * @since TBD moved to Abstract_Gateway
	 * @since 5.1.9
	 *
	 * @return string
	 */
	public function generate_unique_tracking_id() {
		$gateway = static::$key;
		$id      = wp_generate_password( 6, false, false );;
		$url_frags = wp_parse_url( home_url() );
		$url       = Arr::get( $url_frags, 'host' ) . Arr::get( $url_frags, 'path' );
		$url       = add_query_arg( [
			'v' => static::VERSION . '-' . $id,
		], $url );

		// Always limit it to 127 chars.
		return substr( (string) $url, 0, 127 );
	}
}
