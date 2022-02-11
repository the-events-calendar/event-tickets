<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Merchant;
use Tribe__Utils__Array as Arr;

/**
 * Class Merchant
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Merchant extends Abstract_Merchant {

	/**
	 * List of countries that are unauthorized to work with the TEC Provider for regulatory reasons.
	 *
	 * @var array
	 */
	const UNAUTHORIZED_COUNTRIES = [
		'BR'
	];

	/**
	 * Option key to save the information regarding merchant status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $merchant_unauthorized_option_key = 'tickets-commerce-merchant-unauthorized';

	/**
	 * Option key to save the information regarding merchant default currency.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $merchant_default_currency_option_key = 'tickets-commerce-merchant-currency';

	/**
	 * Determines if Merchant is active. For Stripe this is the same as being connected.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_active( $recheck = false ) {
		return $this->is_connected( $recheck );
	}

	/**
	 * Determines if the Merchant is connected.
	 *
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_signup_data_key() {
		$gateway_key = Gateway::get_key();

		return "tec_tickets_commerce_{$gateway_key}_signup_data";
	}

	/**
	 * Returns the stripe client secret stored for server-side transactions.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_client_secret() {
		$keys = get_option( $this->get_signup_data_key() );

		if ( empty( $keys[ $this->get_mode() ]->access_token ) ) {
			return '';
		}

		return $keys[ $this->get_mode() ]->access_token;
	}

	/**
	 * Fetch the Publishable key for the user.
	 *
	 * @since TBD
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
	 * Returns the stripe client id stored for server-side transactions.
	 *
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
	 *
	 * @param array $client_data connection data from the database
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

		$account_id = urlencode( $client_data['client_id'] );
		$url        = '/accounts/{account_id}';
		$url        = str_replace( '{account_id}', $account_id, $url );

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
	 * @since TBD
	 *
	 * @return bool
	 */
	public function delete_signup_data() {
		return update_option( $this->get_signup_data_key(), [] );
	}

	/**
	 * Validate if this Merchant is allowed to connect to the TEC Provider.
	 *
	 * @since TBD
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
	 * Determine if a stripe account is listed in an unauthorized country.
	 *
	 * @since TBD
	 *
	 * @param array $status the connection status array.
	 *
	 * @return bool
	 */
	public function country_is_unauthorized( $status ) {
		return in_array( $status['country'], static::UNAUTHORIZED_COUNTRIES, true );
	}

	/**
	 * Check if merchant is set as unauthorized.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_merchant_unauthorized() {
		return get_option( static::$merchant_unauthorized_option_key, false );
	}

	/**
	 * Set merchant as unauthorized.
	 *
	 * @since TBD
	 *
	 * @param string $validation_key refusal reason, must be the same as the notice slug for the corresponding error.
	 */
	public function set_merchant_unauthorized( $validation_key ) {
		\Tribe__Admin__Notices::instance()->undismiss_for_all( $validation_key );
		update_option( static::$merchant_unauthorized_option_key, $validation_key );
	}

	/**
	 * Unset merchant as unauthorized.
	 *
	 * @since TBD
	 */
	public function unset_merchant_unauthorized() {
		delete_option( static::$merchant_unauthorized_option_key );
	}

	/**
	 * Get the merchant default currency.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_merchant_currency() {
		return get_option( static::$merchant_default_currency_option_key );
	}
}