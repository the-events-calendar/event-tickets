<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Merchant;
use Tribe__Utils__Array as Arr;

/**
 * Class Merchant
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Merchant extends Abstract_Merchant {
	/**
	 * Stores the nonce action for disconnecting Square.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	protected string $disconnect_action = 'square-disconnect';

	/**
	 * Option key to save the information regarding merchant status.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $merchant_unauthorized_option_key = 'tickets-commerce-merchant-unauthorized';

	/**
	 * Option key to save the information regarding merchant authorization.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $merchant_deauthorized_option_key = 'tickets-commerce-merchant-deauthorized';

	/**
	 * Option key to save the information regarding merchant default currency.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $merchant_default_currency_option_key = 'tickets-commerce-merchant-currency';

	/**
	 * Option key to save the PKCE code verifier for OAuth authentication.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $code_verifier_option_key = 'tickets-commerce-square-code-verifier';

	/**
	 * Determines if Merchant is active. For Square this is the same as being connected.
	 *
	 * @since 5.3.0
	 *
	 * @param bool $recheck Whether to force a recheck of the connection.
	 *
	 * @return bool
	 */
	public function is_active( $recheck = false ) {
		return $this->is_connected( $recheck );
	}

	/**
	 * Determines if the Merchant is connected.
	 *
	 * @since 5.3.0
	 *
	 * @param bool $recheck Whether to force a recheck of the connection.
	 *
	 * @return bool
	 */
	public function is_connected( $recheck = false ) {
		$client_data = $this->to_array();

		if ( empty( $client_data['client_id'] )
			 || empty( $client_data['access_token'] )
		) {
			return false;
		}

		if ( $recheck ) {
			$status = $this->check_account_status( $client_data );

			if ( false === $status['connected'] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns the options key for the account in the merchant mode.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_account_key() {
		$gateway_key = Gateway::get_key();

		return "tec_tickets_commerce_{$gateway_key}_account";
	}

	/**
	 * Returns the data retrieved from the signup process.
	 *
	 * Uses normal WP options to be saved, instead of the normal tribe_update_option.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_signup_data_key() {
		$gateway_key = Gateway::get_key();

		return "tec_tickets_commerce_{$gateway_key}_signup_data";
	}

	/**
	 * Returns the Square access token stored for server-side transactions.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_access_token() {
		$keys = get_option( $this->get_signup_data_key() );

		if ( empty( $keys[ $this->get_mode() ]->access_token ) ) {
			return '';
		}

		return $keys[ $this->get_mode() ]->access_token;
	}

	/**
	 * Returns the Square refresh token for refreshing access tokens.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_refresh_token() {
		$keys = get_option( $this->get_signup_data_key() );

		if ( empty( $keys[ $this->get_mode() ]->refresh_token ) ) {
			return '';
		}

		return $keys[ $this->get_mode() ]->refresh_token;
	}

	/**
	 * Returns the Square merchant ID stored for server-side transactions.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_merchant_id() {
		$keys = get_option( $this->get_signup_data_key() );

		if ( empty( $keys['merchant_id'] ) ) {
			return '';
		}

		return $keys['merchant_id'];
	}

	/**
	 * Get the account ID (same as merchant ID for Square).
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_account_id() {
		return $this->get_merchant_id();
	}

	/**
	 * Return array of merchant details.
	 *
	 * @since 5.3.0
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'client_id'     => $this->get_merchant_id(),
			'access_token'  => $this->get_access_token(),
			'refresh_token' => $this->get_refresh_token(),
		];
	}

	/**
	 * Saves signup data from the redirect into permanent option.
	 *
	 * @since 5.3.0
	 *
	 * @param array $signup_data
	 *
	 * @return bool
	 */
	public function save_signup_data( array $signup_data ) {
		unset( $signup_data['whodat'] );
		unset( $signup_data['state'] );

		return update_option( $this->get_signup_data_key(), $signup_data );
	}

	/**
	 * Query the Square API to gather information about the current connected account.
	 *
	 * @since 5.3.0
	 *
	 * @param array $client_data Connection data from the database.
	 *
	 * @return array
	 */
	public function check_account_status( $client_data = [] ) {
		if ( empty( $client_data ) ) {
			$client_data = $this->to_array();
		}

		$return = [
			'connected'       => false,
			'errors'          => [],
			'capabilities'    => [],
		];

		if ( empty( $client_data['client_id'] ) || empty( $client_data['access_token'] ) ) {
			$return['errors'][] = __( 'Missing Square account credentials.', 'event-tickets' );
			return $return;
		}

		$status = tribe( WhoDat::class )->get_token_status();

		if ( ! is_array( $status ) || empty( $status ) ) {
			$return['errors'][] = __( 'Unable to connect to Square.', 'event-tickets' );
			return $return;
		}

		if ( ! empty( $status['error'] ) ) {
			$return['errors'][] = $status['error_description'] ?? __( 'Unknown Square error.', 'event-tickets' );
			return $return;
		}

		$return['connected'] = true;
		return $return;
	}

	/**
	 * Delete all signup data.
	 *
	 * @since 5.3.0
	 *
	 * @return bool
	 */
	public function delete_signup_data() {
		return delete_option( $this->get_signup_data_key() );
	}

	/**
	 * Check if the merchant is unauthorized.
	 *
	 * @since 5.3.0
	 *
	 * @return bool
	 */
	public function is_merchant_unauthorized() {
		$unauthorized = get_option( static::$merchant_unauthorized_option_key );

		return ! empty( $unauthorized );
	}

	/**
	 * Set the merchant as unauthorized.
	 *
	 * @since 5.3.0
	 *
	 * @param string $validation_key A unique key to identify this validation.
	 *
	 * @return bool
	 */
	public function set_merchant_unauthorized( $validation_key ) {
		return update_option( static::$merchant_unauthorized_option_key, sanitize_key( $validation_key ) );
	}

	/**
	 * Remove merchant unauthorized status.
	 *
	 * @since 5.3.0
	 *
	 * @return bool
	 */
	public function unset_merchant_unauthorized() {
		return delete_option( static::$merchant_unauthorized_option_key );
	}

	/**
	 * Check if the merchant is deauthorized.
	 *
	 * @since 5.3.0
	 *
	 * @return bool
	 */
	public function is_merchant_deauthorized() {
		$deauthorized = get_option( static::$merchant_deauthorized_option_key );

		return ! empty( $deauthorized );
	}

	/**
	 * Set the merchant as deauthorized.
	 *
	 * @since 5.3.0
	 *
	 * @param string $validation_key A unique key to identify this validation.
	 *
	 * @return bool
	 */
	public function set_merchant_deauthorized( $validation_key ) {
		return update_option( static::$merchant_deauthorized_option_key, sanitize_key( $validation_key ) );
	}

	/**
	 * Remove merchant deauthorized status.
	 *
	 * @since 5.3.0
	 *
	 * @return bool
	 */
	public function unset_merchant_deauthorized() {
		return delete_option( static::$merchant_deauthorized_option_key );
	}

	/**
	 * Get merchant's default currency.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_merchant_currency() {
		return get_option( static::$merchant_default_currency_option_key, 'USD' );
	}

	/**
	 * Generates and stores a PKCE code verifier for OAuth authentication.
	 *
	 * @since 5.3.0
	 *
	 * @return string The generated code verifier
	 */
	public function generate_code_verifier() {
		// Generate a code_verifier (random string between 43-128 chars)
		$code_verifier = bin2hex( random_bytes( 43 ) );

		// Store the code verifier as an option with a 2-hour expiration
		set_transient( static::$code_verifier_option_key, $code_verifier, HOUR_IN_SECONDS * 2 );

		return $code_verifier;
	}

	/**
	 * Creates a PKCE code challenge from the stored code verifier.
	 *
	 * @since 5.3.0
	 *
	 * @return string The code challenge for OAuth authentication
	 */
	public function generate_code_challenge() {
		$code_verifier = $this->get_code_verifier();

		if ( empty( $code_verifier ) ) {
			$code_verifier = $this->generate_code_verifier();
		}

		// Create code_challenge using SHA256 hash of the code_verifier (PKCE)
		$code_challenge = rtrim( strtr( base64_encode( hash( 'sha256', $code_verifier, true ) ), '+/', '-_' ), '=' );

		return $code_challenge;
	}

	/**
	 * Gets the stored PKCE code verifier.
	 *
	 * @since 5.3.0
	 *
	 * @return string The stored code verifier or empty string if not found
	 */
	public function get_code_verifier() {
		$code_verifier = get_transient( static::$code_verifier_option_key );

		return $code_verifier ?: '';
	}

	/**
	 * Deletes the stored PKCE code verifier.
	 *
	 * @since 5.3.0
	 *
	 * @return bool True if successful, false otherwise
	 */
	public function delete_code_verifier() {
		return delete_transient( static::$code_verifier_option_key );
	}

	/**
	 * Update merchant data.
	 *
	 * @since 5.3.0
	 *
	 * @param array $data New merchant data.
	 *
	 * @return bool
	 */
	public function update( $data ) {
		if ( empty( $data ) ) {
			return false;
		}

		$current_data = get_option( $this->get_signup_data_key(), [] );
		$merged_data = array_merge( $current_data, $data );

		return update_option( $this->get_signup_data_key(), $merged_data );
	}
}
