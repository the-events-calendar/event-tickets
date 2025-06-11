<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Merchant;
use Tribe__Utils__Array as Arr;

/**
 * Class Merchant
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Merchant extends Abstract_Merchant {
	/**
	 * Stores the nonce action for disconnecting Stripe.
	 *
	 * @since 5.11.0.5
	 *
	 * @var string
	 */
	protected string $disconnect_action = 'stripe-disconnect';

	/**
	 * List of countries that are unauthorized to work with the TEC Provider for regulatory reasons.
	 *
	 * @var array
	 */
	const UNAUTHORIZED_COUNTRIES = [
		'BR',
		'IN',
		'MX',
	];

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
	 * Determines if Merchant is active. For Stripe this is the same as being connected.
	 *
	 * @since 5.3.0
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
	 * @return bool
	 */
	public function is_connected( $recheck = false ) {
		$client_data = $this->to_array();

		if ( empty( $client_data['client_id'] )
			 || empty( $client_data['client_secret'] )
			 || empty( $client_data['publishable_key'] )
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
	 * Returns the Stripe client secret stored for server-side transactions.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_client_secret(): string {
		$keys = get_option( $this->get_signup_data_key() );

		if ( empty( $keys[ $this->get_mode() ]->access_token ) ) {
			return '';
		}

		return $keys[ $this->get_mode() ]->access_token;
	}

	/**
	 * Fetch the Publishable key for the user.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_publishable_key() {
		$keys = get_option( $this->get_signup_data_key() );

		if ( empty( $keys[ $this->get_mode() ]->publishable_key ) ) {
			return '';
		}

		return $keys[ $this->get_mode() ]->publishable_key;
	}

	/**
	 * Returns the Stripe client id stored for server-side transactions.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_client_id() {
		$keys = get_option( $this->get_signup_data_key() );

		if ( empty( $keys['stripe_user_id'] ) ) {
			return '';
		}

		return $keys['stripe_user_id'];
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
			'client_id'       => $this->get_client_id(),
			'client_secret'   => $this->get_client_secret(),
			'publishable_key' => $this->get_client_id(),
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
	 * Returns the list of enabled payment method types for the Payment Element, or the Card type
	 * for the Card Element.
	 *
	 * @since 5.3.0
	 *
	 * @return string[]
	 */
	public function get_payment_method_types( $fallback = false ) {

		if ( $fallback || Settings::CARD_ELEMENT_SLUG === tribe_get_option( Settings::$option_checkout_element ) ) {
			return [ 'card' ];
		}

		return tribe_get_option( Settings::$option_checkout_element_payment_methods, [ 'card' ] );
	}

	/**
	 * Query the Stripe API to gather information about the current connected account.
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
			'charges_enabled' => false,
			'errors'          => [],
			'capabilities'    => [],
		];

		if ( empty( $client_data['client_id'] )
			 || empty( $client_data['client_secret'] )
			 || empty( $client_data['publishable_key'] )
		) {
			return $return;
		}

		$url = sprintf( '/accounts/%s', urlencode( $client_data['client_id'] ) );

		$response = Requests::get( $url, [], [] );

		if ( ! empty( $response['object'] ) && 'account' === $response['object'] ) {
			$return['connected']            = true;
			$return['charges_enabled']      = tribe_is_truthy( Arr::get( $response, 'charges_enabled', false ) );
			$return['country']              = Arr::get( $response, 'country', false );
			$return['default_currency']     = Arr::get( $response, 'default_currency', false );
			$return['capabilities']         = Arr::get( $response, 'capabilities', false );
			$return['statement_descriptor'] = Arr::get( $response, 'statement_descriptor', false );

			if ( empty( $return['statement_descriptor'] ) && ! empty( $response['settings']['payments']['statement_descriptor'] ) ) {
				$return['statement_descriptor'] = $response['settings']['payments']['statement_descriptor'];
			}

			if ( ! empty( $response['requirements']['errors'] ) ) {
				$return['errors']['requirements'] = $response['requirements']['errors'];
			}

			if ( ! empty( $response['future_requirements']['errors'] ) ) {
				$return['errors']['future_requirements'] = $response['future_requirements']['errors'];
			}
		}

		if ( ! empty( $response['type'] ) && in_array( $response['type'], [
				'api_error',
				'card_error',
				'idempotency_error',
				'invalid_request_error',
			], true ) ) {

			$return['request_error'] = $response;
		}

		return $return;
	}

	/**
	 * Empty the signup data option and void the connection.
	 *
	 * @since 5.3.0
	 *
	 * @return bool
	 */
	public function delete_signup_data() {
		return update_option( $this->get_signup_data_key(), [] );
	}

	/**
	 * Validate if this Merchant is allowed to connect to the TEC Provider.
	 *
	 * @since 5.3.0
	 *
	 * @return string 'valid' if the account is permitted, or a string with the notice slug if not.
	 */
	public function validate_account_is_permitted() {
		$status = tribe( Settings::class )->connection_status;
		if ( empty( $status ) ) {
			tribe( Settings::class )->set_connection_status();
			$status = tribe( Settings::class )->connection_status;
		}

		$is_licensed = \TEC\Tickets\Commerce\Settings::is_licensed_plugin();

		if ( $is_licensed ) {
			return 'valid';
		}

		if ( $this->country_is_unauthorized( $status ) ) {
			return 'tc-stripe-country-denied';
		}

		return 'valid';
	}

	/**
	 * Determine if a Stripe account is listed in an unauthorized country.
	 *
	 * @since 5.3.0
	 *
	 * @param array $status The connection status array.
	 *
	 * @return bool
	 */
	public function country_is_unauthorized( $status ) {
		return in_array( $status['country'], static::UNAUTHORIZED_COUNTRIES, true );
	}

	/**
	 * Check if merchant is set as unauthorized.
	 *
	 * Unauthorized accounts are accounts that cannot be authorized to connect, usually due to regulatory reasons.
	 *
	 * @since 5.3.0
	 *
	 * @return bool
	 */
	public function is_merchant_unauthorized() {
		return get_option( static::$merchant_unauthorized_option_key, false );
	}

	/**
	 * Set merchant as unauthorized.
	 *
	 * @since 5.3.0
	 *
	 * @param string $validation_key Refusal reason, must be the same as the notice slug for the corresponding error.
	 */
	public function set_merchant_unauthorized( $validation_key ) {
		\Tribe__Admin__Notices::instance()->undismiss_for_all( $validation_key );
		update_option( static::$merchant_unauthorized_option_key, $validation_key );
	}

	/**
	 * Unset merchant as unauthorized.
	 *
	 * @since 5.3.0
	 */
	public function unset_merchant_unauthorized() {
		delete_option( static::$merchant_unauthorized_option_key );
	}

	/**
	 * Check if merchant is set as de-authorized.
	 *
	 * De-authorized accounts are accounts that were previously connected and whose connection has been revoked in the
	 * Stripe Dashboard. These accounts can be re-connected with the proper credentials.
	 *
	 * @since 5.3.0
	 *
	 * @return bool
	 */
	public function is_merchant_deauthorized() {
		return get_option( static::$merchant_deauthorized_option_key, false );
	}

	/**
	 * Set merchant as de-authorized.
	 *
	 * @since 5.3.0
	 *
	 * @param string $validation_key De-authorization reason, must be the same as the notice slug for the corresponding error.
	 */
	public function set_merchant_deauthorized( $validation_key ) {
		\Tribe__Admin__Notices::instance()->undismiss_for_all( $validation_key );
		update_option( static::$merchant_deauthorized_option_key, $validation_key );
	}

	/**
	 * Unset merchant as de-authorized.
	 *
	 * @since 5.3.0
	 */
	public function unset_merchant_deauthorized() {
		delete_option( static::$merchant_deauthorized_option_key );
	}

	/**
	 * Get the merchant default currency.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_merchant_currency() {
		return get_option( static::$merchant_default_currency_option_key );
	}

	/**
	 * Updates an existing merchant account.
	 *
	 * @since 5.3.0
	 *
	 * @param array $data Array of data to be passed directly to the body of the update request.
	 *
	 * @return array|\WP_Error|null
	 */
	public function update( $data ) {
		$query_args = [];
		$args       = [
			'body' => $data,
		];

		$url = sprintf( '/accounts/%s', urlencode( $this->get_client_id() ) );

		return Requests::post( $url, $query_args, $args );
	}
}
