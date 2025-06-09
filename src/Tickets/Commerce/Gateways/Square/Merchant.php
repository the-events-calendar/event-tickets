<?php
/**
 * Merchant class for the Square gateway.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Merchant;
use TEC\Tickets\Commerce\Settings as Commerce_Settings;
use Exception;

/**
 * Class Merchant
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Merchant extends Abstract_Merchant {
	/**
	 * Stores the nonce action for disconnecting Square.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	protected string $disconnect_action = 'square-disconnect';

	/**
	 * Option key to save the information regarding merchant status.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public static string $merchant_unauthorized_option_key = 'tickets-commerce-merchant-unauthorized';

	/**
	 * Option key to save the information regarding merchant authorization.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public static string $merchant_deauthorized_option_key = 'tickets-commerce-merchant-deauthorized';

	/**
	 * Option key to save the information regarding merchant default currency.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public static string $merchant_default_currency_option_key = 'tickets-commerce-merchant-currency-%s';

	/**
	 * Option key to save the PKCE code verifier for OAuth authentication.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public static string $code_verifier_option_key = 'tickets-commerce-square-code-verifier';

	/**
	 * Determines if Merchant is active. For Square this is the same as being connected.
	 *
	 * @since 5.24.0
	 *
	 * @param bool $recheck Whether to force a recheck of the connection.
	 *
	 * @return bool
	 */
	public function is_active( $recheck = false ): bool {
		return $this->is_connected( $recheck );
	}

	/**
	 * Determines if the Merchant is connected.
	 *
	 * @since 5.24.0
	 *
	 * @param bool $recheck Whether to force a recheck of the connection.
	 *
	 * @return bool
	 */
	public function is_connected( $recheck = false ): bool {
		$client_data = $this->to_array();

		if (
			empty( $client_data['client_id'] )
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
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_account_key(): string {
		$gateway_key = Gateway::get_key();

		return "tec_tickets_commerce_{$gateway_key}_account";
	}

	/**
	 * Returns the data retrieved from the signup process.
	 *
	 * Uses normal WP options to be saved, instead of the normal tribe_update_option.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_signup_data_key(): string {
		$gateway_key = Gateway::get_key();
		$mode        = $this->get_mode();

		return "tec_tickets_commerce_{$gateway_key}_signup_data_{$mode}";
	}

	/**
	 * Returns the Square access token stored for server-side transactions.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_access_token(): string {
		$data = get_option( $this->get_signup_data_key() );

		if ( empty( $data['access_token'] ) ) {
			return '';
		}

		return $data['access_token'];
	}

	/**
	 * Returns the Square refresh token for refreshing access tokens.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_refresh_token(): string {
		$data = get_option( $this->get_signup_data_key() );

		if ( empty( $data['refresh_token'] ) ) {
			return '';
		}

		return $data['refresh_token'];
	}

	/**
	 * Returns the Square merchant ID stored for server-side transactions.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_merchant_id(): string {
		$data = get_option( $this->get_signup_data_key() );

		if ( empty( $data['merchant_id'] ) ) {
			return '';
		}

		return $data['merchant_id'];
	}

	/**
	 * Returns the Whodat signature stored for server-side transactions.
	 *
	 * @since 5.24.0
	 *
	 * @return ?string
	 */
	public function get_whodat_signature(): ?string {
		$data = get_option( $this->get_signup_data_key() );

		return $data['whodat_signature'] ?? null;
	}

	/**
	 * Get the account ID (same as merchant ID for Square).
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_account_id(): string {
		return $this->get_merchant_id();
	}

	/**
	 * Return array of merchant details.
	 *
	 * @since 5.24.0
	 *
	 * @return array
	 */
	public function to_array(): array {
		return [
			'client_id'     => $this->get_merchant_id(),
			'access_token'  => $this->get_access_token(),
			'refresh_token' => $this->get_refresh_token(),
		];
	}

	/**
	 * Saves signup data from the redirect into permanent option.
	 *
	 * @since 5.24.0
	 *
	 * @param array $signup_data The signup data to save.
	 *
	 * @return bool
	 */
	public function save_signup_data( array $signup_data ): bool {
		unset( $signup_data['state'] );

		return update_option( $this->get_signup_data_key(), $signup_data );
	}

	/**
	 * Query the Square API to gather information about the current connected account.
	 *
	 * @since 5.24.0
	 *
	 * @param array $client_data Connection data from the database.
	 *
	 * @return array
	 */
	public function check_account_status( array $client_data = [] ): array {
		if ( empty( $client_data ) ) {
			$client_data = $this->to_array();
		}

		$return = [
			'connected'    => false,
			'errors'       => [],
			'capabilities' => [],
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
	 * @since 5.24.0
	 *
	 * @return bool
	 */
	public function delete_signup_data(): bool {
		// Also delete any stored merchant data.
		$this->delete_merchant_data();

		$result = delete_option( $this->get_signup_data_key() );

		/**
		 * Fires when merchant data is deleted.
		 *
		 * @since 5.24.0
		 */
		do_action( 'tec_tickets_commerce_square_merchant_disconnected' );

		return $result;
	}

	/**
	 * Check if the merchant is unauthorized.
	 *
	 * @since 5.24.0
	 *
	 * @return bool
	 */
	public function is_merchant_unauthorized(): bool {
		return ! empty( get_option( static::$merchant_unauthorized_option_key ) );
	}

	/**
	 * Set the merchant as unauthorized.
	 *
	 * @since 5.24.0
	 *
	 * @param string $validation_key A unique key to identify this validation.
	 *
	 * @return bool
	 */
	public function set_merchant_unauthorized( string $validation_key ): bool {
		return update_option( static::$merchant_unauthorized_option_key, sanitize_key( $validation_key ) );
	}

	/**
	 * Remove merchant unauthorized status.
	 *
	 * @since 5.24.0
	 *
	 * @return bool
	 */
	public function unset_merchant_unauthorized(): bool {
		return delete_option( static::$merchant_unauthorized_option_key );
	}

	/**
	 * Check if the merchant is deauthorized.
	 *
	 * @since 5.24.0
	 *
	 * @return bool
	 */
	public function is_merchant_deauthorized(): bool {
		return ! empty( get_option( static::$merchant_deauthorized_option_key ) );
	}

	/**
	 * Set the merchant as deauthorized.
	 *
	 * @since 5.24.0
	 *
	 * @param string $validation_key A unique key to identify this validation.
	 *
	 * @return bool
	 */
	public function set_merchant_deauthorized( string $validation_key ): bool {
		return update_option( static::$merchant_deauthorized_option_key, sanitize_key( $validation_key ) );
	}

	/**
	 * Remove merchant deauthorized status.
	 *
	 * @since 5.24.0
	 *
	 * @return bool
	 */
	public function unset_merchant_deauthorized(): bool {
		return delete_option( static::$merchant_deauthorized_option_key );
	}

	/**
	 * Get merchant's default currency.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_merchant_currency(): string {
		return (string) Commerce_Settings::get_option( static::$merchant_default_currency_option_key, [], 'USD' );
	}

	/**
	 * Generates and stores a PKCE code verifier for OAuth authentication.
	 *
	 * @since 5.24.0
	 *
	 * @return string The generated code verifier
	 */
	public function generate_code_verifier(): string {
		// Generate a code_verifier (random string between 43-128 chars).
		$code_verifier = bin2hex( random_bytes( 43 ) );

		// Store the code verifier as an option with a 2-hour expiration.
		set_transient( static::$code_verifier_option_key, $code_verifier, HOUR_IN_SECONDS * 2 );

		return $code_verifier;
	}

	/**
	 * Creates a PKCE code challenge from the stored code verifier.
	 *
	 * @since 5.24.0
	 *
	 * @return string The code challenge for OAuth authentication
	 */
	public function generate_code_challenge(): string {
		$code_verifier = $this->get_code_verifier();

		if ( empty( $code_verifier ) ) {
			$code_verifier = $this->generate_code_verifier();
		}

		// Create code_challenge using SHA256 hash of the code_verifier (PKCE).
		return rtrim( strtr( base64_encode( hash( 'sha256', $code_verifier, true ) ), '+/', '-_' ), '=' );
	}

	/**
	 * Gets the stored PKCE code verifier.
	 *
	 * @since 5.24.0
	 *
	 * @return string The stored code verifier or empty string if not found
	 */
	public function get_code_verifier(): string {
		$code_verifier = get_transient( static::$code_verifier_option_key );

		return false !== $code_verifier ? $code_verifier : '';
	}

	/**
	 * Deletes the stored PKCE code verifier.
	 *
	 * @since 5.24.0
	 *
	 * @return bool True if successful, false otherwise
	 */
	public function delete_code_verifier(): bool {
		return delete_transient( static::$code_verifier_option_key );
	}

	/**
	 * Update merchant data.
	 *
	 * @since 5.24.0
	 *
	 * @param array $data New merchant data.
	 *
	 * @return bool
	 */
	public function update( array $data ): bool {
		if ( empty( $data ) ) {
			return false;
		}

		$current_data = get_option( $this->get_signup_data_key(), [] );
		$merged_data  = array_merge( $current_data, $data );

		return update_option( $this->get_signup_data_key(), $merged_data );
	}

	/**
	 * Fetch merchant data from Square API using stored merchant ID.
	 *
	 * @since 5.24.0
	 *
	 * @param bool $force_refresh Whether to force a refresh of the data from the API.
	 *
	 * @return array The merchant data.
	 */
	public function fetch_merchant_data( bool $force_refresh = false ): array {
		// Look for cached data first if we're not forcing a refresh.
		if ( ! $force_refresh ) {
			$stored_data = get_option( $this->get_merchant_data_option_key() );
			if ( ! empty( $stored_data ) ) {
				return $stored_data;
			}
		}

		// If not connected, bail.
		if ( ! $this->is_connected() ) {
			return [];
		}

		$merchant_id = $this->get_merchant_id();

		// If we don't have a merchant ID, bail.
		if ( empty( $merchant_id ) ) {
			return [];
		}

		$response = Requests::get_with_cache( "merchants/{$merchant_id}" );

		// Handle error responses.
		if ( is_wp_error( $response ) || isset( $response['errors'] ) ) {
			$error_message = is_wp_error( $response )
				? $response->get_error_message()
				: ( ! empty( $response['errors'][0]['detail'] ) ? $response['errors'][0]['detail'] : 'Unknown error' );

			do_action(
				'tribe_log',
				'error',
				'Square API Error',
				[
					'message' => $error_message,
					'source'  => 'tickets-commerce',
				]
			);

			return [];
		}

		// Store the merchant data in a permanent option.
		update_option( $this->get_merchant_data_option_key(), $response );

		// Update some merchant fields in our local data if available.
		if ( isset( $response['merchant'] ) ) {
			$merchant = $response['merchant'];

			$update_data = [];

			if ( isset( $merchant['business_name'] ) ) {
				$update_data['merchant_name'] = $merchant['business_name'];
			}

			if ( isset( $merchant['country'] ) ) {
				$update_data['merchant_country'] = $merchant['country'];
			}

			if ( isset( $merchant['currency'] ) ) {
				$update_data['merchant_currency'] = $merchant['currency'];
				// Also update the option.
				Commerce_Settings::update_option( static::$merchant_default_currency_option_key, $merchant['currency'] );
			}

			if ( isset( $merchant['email_address'] ) ) {
				$update_data['merchant_email'] = $merchant['email_address'];
			}

			if ( ! empty( $update_data ) ) {
				$this->update( $update_data );
			}
		}

		return $response;
	}

	/**
	 * Get the option key for storing merchant data.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	private function get_merchant_data_option_key(): string {
		$gateway_key = Gateway::get_key();
		$mode        = $this->get_mode();
		$merchant_id = $this->get_merchant_id();

		return "tec_tickets_commerce_{$gateway_key}_merchant_data_{$mode}_{$merchant_id}";
	}

	/**
	 * Get merchant name from stored data or from Square API.
	 *
	 * @since 5.24.0
	 *
	 * @param bool $force_refresh Whether to force a refresh of the data from the API.
	 *
	 * @return string
	 */
	public function get_merchant_name( bool $force_refresh = false ): string {
		$data = get_option( $this->get_signup_data_key() );

		if ( ! empty( $data['merchant_name'] ) ) {
			return $data['merchant_name'];
		}

		// Try to fetch from API if we don't have it stored.
		$merchant_data = $this->fetch_merchant_data( $force_refresh );

		if ( ! empty( $merchant_data['merchant']['business_name'] ) ) {
			return $merchant_data['merchant']['business_name'];
		}

		return '';
	}

	/**
	 * Get merchant email from stored data or from Square API.
	 *
	 * @since 5.24.0
	 *
	 * @param bool $force_refresh Whether to force a refresh of the data from the API.
	 *
	 * @return string
	 */
	public function get_merchant_email( bool $force_refresh = false ): string {
		$data = get_option( $this->get_signup_data_key() );

		if ( ! empty( $data['merchant_email'] ) ) {
			return $data['merchant_email'];
		}

		// Try to fetch from API if we don't have it stored.
		$merchant_data = $this->fetch_merchant_data( $force_refresh );

		if ( ! empty( $merchant_data['merchant']['email_address'] ) ) {
			return $merchant_data['merchant']['email_address'];
		}

		return '';
	}

	/**
	 * Get merchant country from stored data or from Square API.
	 *
	 * @since 5.24.0
	 *
	 * @param bool $force_refresh Whether to force a refresh of the data from the API.
	 *
	 * @return string
	 */
	public function get_merchant_country( bool $force_refresh = false ): string {
		$data = get_option( $this->get_signup_data_key() );

		if ( ! empty( $data['merchant_country'] ) ) {
			return $data['merchant_country'];
		}

		// Try to fetch from API if we don't have it stored.
		$merchant_data = $this->fetch_merchant_data( $force_refresh );

		if ( ! empty( $merchant_data['merchant']['country'] ) ) {
			return $merchant_data['merchant']['country'];
		}

		return '';
	}

	/**
	 * Delete stored merchant data.
	 *
	 * @since 5.24.0
	 *
	 * @return bool True if deleted, false otherwise.
	 */
	public function delete_merchant_data(): bool {
		return delete_option( $this->get_merchant_data_option_key() );
	}

	/**
	 * Get the client secret for merchant, in this case the access token.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_client_secret(): string {
		return $this->get_access_token();
	}

	/**
	 * Get the test mode status.
	 *
	 * @since 5.24.0
	 *
	 * @return bool
	 */
	public function is_test_mode(): bool {
		return tribe( Gateway::class )->is_test_mode();
	}

	/**
	 * Get the client ID for the Square SDK.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_client_id(): string {
		$client_id = tribe_get_option( Settings::OPTION_CLIENT_ID );

		if ( empty( $client_id ) && tribe( Gateway::class )->is_test_mode() ) {
			$client_id = tribe_get_option( Settings::OPTION_SANDBOX_CLIENT_ID );
		}

		return $client_id;
	}

	/**
	 * Get the location ID for the Square merchant.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_location_id(): string {
		return tribe( Gateway::class )->get_location_id();
	}

	/**
	 * Gets all available locations from the merchant's Square account.
	 *
	 * @since 5.24.0
	 *
	 * @return array Array of locations.
	 * @throws Exception On failure.
	 */
	public function get_locations(): array {
		if ( ! $this->is_active() ) {
			throw new Exception( __( 'Square is not connected.', 'event-tickets' ) );
		}

		$url      = 'locations';
		$args     = [];
		$response = Requests::get_with_cache( $url, [], $args );

		if ( empty( $response['locations'] ) || ! is_array( $response['locations'] ) ) {
			return [];
		}

		return $response['locations'];
	}

	/**
	 * Whether the merchant is ready to sell.
	 *
	 * @since 5.24.0
	 *
	 * @return bool
	 */
	public function is_ready_to_sell(): bool {
		return (bool) $this->get_location_id() && $this->is_currency_matching();
	}

	/**
	 * Whether the merchant's currency matches the gateway's currency.
	 *
	 * @since 5.24.0
	 *
	 * @return bool
	 */
	public function is_currency_matching(): bool {
		return $this->get_merchant_currency() === tribe_get_option( Commerce_Settings::$option_currency_code, 'USD' );
	}
}
