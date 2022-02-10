<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Merchant;

/**
 * Class Merchant
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Merchant extends Abstract_Merchant {

	/**
	 * Option key to save the information regarding merchant status
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $merchant_denied_option_key = 'tickets-commerce-merchant-denied';

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
			$status = tribe( Client::class )->check_account_status( $client_data );

			if ( false === $status['connected'] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Gather connections status from the Stripe API
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_connection_status() {
		$client_data = $this->to_array();
		return tribe( Client::class )->check_account_status( $client_data );
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
	 * Empty the signup data option and void the connection
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function delete_signup_data() {
		return update_option( $this->get_signup_data_key(), [] );
	}

	/**
	 * Validate if this Merchant is allowed to connect to the TEC Provider
	 *
	 * @since TBD
	 *
	 * @return string 'valid' if the account is permitted, or a string with the notice slug if not
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

		if ( ! $this->country_is_permitted( $status ) ) {
			return 'tc-stripe-country-denied';
		}

		return 'valid';
	}

	/**
	 * Determine if a stripe account is listed in a permitted country
	 *
	 * @since TBD
	 *
	 * @param array $status the connection status array
	 *
	 * @return bool
	 */
	public function country_is_permitted( $status ) {
		// @todo figure out any other exclusions
		return 'BR' !== $status['country'];
	}

	/**
	 * Check if merchant is set as denied
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_merchant_denied() {
		return get_option( static::$merchant_denied_option_key, false );
	}

	/**
	 * Set merchant as denied
	 *
	 * @since TBD
	 *
	 * @param string $validation_key refusal reason, must be the same as the notice slug for the corresponding error
	 */
	public function set_merchant_denied( $validation_key ) {
		\Tribe__Admin__Notices::instance()->undismiss_for_all( $validation_key );
		update_option( static::$merchant_denied_option_key, $validation_key );
	}

	/**
	 * Unset merchant as denied
	 *
	 * @since TBD
	 */
	public function unset_merchant_denied() {
		delete_option( static::$merchant_denied_option_key );
	}
}