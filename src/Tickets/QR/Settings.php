<?php

namespace TEC\Tickets\QR;

/**
 * Class Settings
 *
 * @since   5.7.0
 *
 * @package TEC\Tickets\QR
 */
class Settings {

	/**
	 * The option key that will be used to store the value.
	 * This particular setting was originally stored in the Event Tickets Plus settings, now ETP should ways use the
	 * option key here.
	 *
	 * @since 5.7.0
	 *
	 * @var string The option key.
	 */
	protected static string $enabled_option_key = 'tickets-enable-qr-codes';

	/**
	 * The option key that will be used to store the value.
	 * This particular setting was originally stored in the Event Tickets Plus settings, now ETP should ways use the
	 * option key here.
	 *
	 * @since 5.7.0
	 *
	 * @var string The option key.
	 */
	protected static string $api_hash_option_key = 'tickets-plus-qr-options-api-key';

	/**
	 * Get the option key that will be used to store the value.
	 *
	 * Option keys that are user controlled, need to be kept as public.
	 *
	 * @since 5.7.0
	 *
	 * @return string
	 */
	public static function get_enabled_option_slug(): string {
		return static::$enabled_option_key;
	}

	/**
	 * Get the option key that will be used to store the value.
	 *
	 * Option keys that are not user controlled, should be kept as protected and interactions should be using
	 * the methods related, like `$this->get_api_hash()`, avoids weird non-tested scenarios.
	 *
	 * @since 5.7.0
	 *
	 * @return string
	 */
	protected static function get_api_key_option_slug(): string {
		return static::$api_hash_option_key;
	}

	/**
	 * Check if the QR code is enabled.
	 *
	 * @since 5.7.0
	 *
	 * @param mixed $context The context of the check.
	 *
	 * @return bool
	 */
	public function is_enabled( $context = null ): bool {
		$controller = tribe( Controller::class );
		$enabled    = false;

		// Only fetch from DB if the controller can use the QR code.
		if ( $controller->can_use() ) {
			$enabled = tribe_is_truthy( tribe_get_option( static::get_enabled_option_slug(), true ) );
		}

		/**
		 * Filters the QR enabled value
		 *
		 * @since 5.6.10
		 * @deprecated 5.7.0 Use tec_tickets_qr_code_enabled instead.
		 *
		 * @param bool  $enabled The bool that comes from the options
		 * @param array $context Context for this check, normally an Array with the ticket
		 */
		$enabled = apply_filters_deprecated( 'tribe_tickets_plus_qr_enabled', [ $enabled, $context ], '5.7.0', 'Use `tec_tickets_qr_code_enabled` instead' );

		/**
		 * Filters the QR enabled value.
		 *
		 * @since 5.7.0
		 *
		 * @param bool  $enabled The bool that comes from the options.
		 * @param array $context Context for this check, normally an Array with the ticket.
		 */
		return apply_filters( 'tec_tickets_qr_code_enabled', $enabled, $context );
	}

	/**
	 * Generate a hash key for QR API.
	 *
	 * @since 5.7.0
	 *
	 * @return string The QR API hash.
	 */
	protected function generate_api_key(): string {
		$random = wp_generate_password( 24, true, true );
		$hash   = substr( md5( $random ), 0, 8 );

		/**
		 * Filters the generated hash key for QR API.
		 *
		 * @since      4.7.5
		 *
		 * @deprecated 5.7.0 Use tec_tickets_qr_settings_generated_api_hash instead.
		 *
		 * @param string $api_key a API key string.
		 */
		$hash = apply_filters_deprecated( 'tribe_tickets_plus_qr_api_hash', [ $hash ], '5.7.0', 'tec_tickets_qr_settings_generated_api_key' );

		/**
		 * Filters the generated hash key for QR API.
		 *
		 * @since 5.7.0
		 *
		 * @param string $hash The API hash string.
		 */
		return apply_filters( 'tec_tickets_qr_settings_generated_api_key', $hash );
	}

	/**
	 * Get the API hash key, if none exists, generate one.
	 *
	 * @since 5.7.0
	 *
	 * @return string
	 */
	public function get_api_key(): string {
		$api_key = tribe_get_option( static::get_api_key_option_slug(), '' );

		if ( empty( $api_key ) ) {
			$api_key = $this->generate_api_key();
			tribe_update_option( static::get_api_key_option_slug(), $api_key );
		}

		return (string) $api_key;
	}

	/**
	 * Deletes the existing API hash key.
	 *
	 * @since 5.7.0
	 *
	 * @return bool
	 */
	public function delete_api_key(): bool {
		return (bool) tribe_remove_option( static::get_api_key_option_slug() );
	}
}