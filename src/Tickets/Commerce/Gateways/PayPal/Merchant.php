<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use TEC\Tickets\Commerce\Traits\Has_Mode;

/**
 * Class Merchant.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Merchant {
	use Has_Mode;

	/**
	 * Determines if the data needs to be saved to the Database
	 *
	 * @since TBD
	 *
	 * @var boolean
	 */
	protected $needs_save = false;

	/**
	 * PayPal merchant Id  (email address).
	 *
	 * @since TBD
	 *
	 * @var null|string
	 */
	protected $merchant_id;

	/**
	 * PayPal merchant id.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $merchant_id_in_paypal;

	/**
	 * Client id.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $client_id;

	/**
	 * Client Secret.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $client_secret;

	/**
	 * Client token.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected $client_token;

	/**
	 * How long till the Client token expires
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $client_token_expires_in;

	/**
	 * Access token.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $access_token;

	/**
	 * Whether or not the connected account is ready to process payments.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	protected $account_is_ready = false;

	/**
	 * Whether or not the account can make custom payments (i.e Advanced Fields & PPCP)
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	protected $supports_custom_payments;

	/**
	 * PayPal account account country.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $account_country;

	/**
	 * Fetches the current Merchant ID.
	 *
	 * @since TBD
	 *
	 * @return string|null
	 */
	public function get_merchant_id() {
		return $this->merchant_id;
	}

	/**
	 * Sets the value for Merchant ID locally, in this instance of the Merchant.
	 *
	 * @since TBD
	 *
	 * @param mixed   $value      Value used for the Merchant ID.
	 * @param boolean $needs_save Determines if the proprieties saved need to save to the DB.
	 */
	public function set_merchant_id( $value, $needs_save = true ) {
		$this->set_value( 'merchant_id', $value, $needs_save );
	}

	/**
	 * Gets the value stored for the Merchant ID in PayPal.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_merchant_id_in_paypal() {
		return $this->merchant_id_in_paypal;
	}

	/**
	 * Sets the value for Merchant ID in PayPal locally, in this instance of the Merchant.
	 *
	 * @since TBD
	 *
	 * @param mixed   $value      Value used for the Merchant ID in PayPal.
	 * @param boolean $needs_save Determines if the proprieties saved need to save to the DB.
	 */
	public function set_merchant_id_in_paypal( $value, $needs_save = true ) {
		$this->set_value( 'merchant_id_in_paypal', $value, $needs_save );
	}

	/**
	 * Gets the value stored for the Client ID.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_client_id() {
		return $this->client_id;
	}

	/**
	 * Sets the value for Merchant ID locally, in this instance of the Merchant.
	 *
	 * @since TBD
	 *
	 * @param mixed   $value      Value used for the Merchant ID.
	 * @param boolean $needs_save Determines if the proprieties saved need to save to the DB.
	 */
	public function set_client_id( $value, $needs_save = true ) {
		$this->client_id = $value;
	}

	/**
	 * Gets the value stored for the Client Secret.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_client_secret() {
		return $this->client_secret;
	}

	/**
	 * Sets the value for Client Secret locally, in this instance of the Merchant.
	 *
	 * @since TBD
	 *
	 * @param mixed   $value      Value used for the Client Secret.
	 * @param boolean $needs_save Determines if the proprieties saved need to save to the DB.
	 */
	public function set_client_secret( $value, $needs_save = true ) {
		$this->set_value( 'client_secret', $value, $needs_save );
	}

	/**
	 * Gets the value stored for the Access Token.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_access_token() {
		return $this->access_token;
	}

	/**
	 * Sets the value for Access Token locally, in this instance of the Merchant.
	 *
	 * @since TBD
	 *
	 * @param mixed   $value      Value used for the Access Token.
	 * @param boolean $needs_save Determines if the proprieties saved need to save to the DB.
	 */
	public function set_access_token( $value, $needs_save = true ) {
		$this->set_value( 'access_token', $value, $needs_save );
	}

	/**
	 * Gets the value stored for if the account is ready for usage.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_account_is_ready() {
		return $this->account_is_ready;
	}

	/**
	 * Sets the value for if this account is ready for usage locally, in this instance of the Merchant.
	 *
	 * @since TBD
	 *
	 * @param mixed   $value      Value used for the Account is Ready.
	 * @param boolean $needs_save Determines if the proprieties saved need to save to the DB.
	 */
	public function set_account_is_ready( $value, $needs_save = true ) {
		$this->set_value( 'account_is_ready', $value, $needs_save );
	}

	/**
	 * Gets the value stored for if this account supports custom payments.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function get_supports_custom_payments() {
		return $this->supports_custom_payments;
	}

	/**
	 * Sets the value determining if this supports custom payments locally, in this instance of the Merchant.
	 *
	 * @since TBD
	 *
	 * @param mixed   $value      Value used for the Support for Custom Payments.
	 * @param boolean $needs_save Determines if the proprieties saved need to save to the DB.
	 */
	public function set_supports_custom_payments( $value, $needs_save = true ) {
		$this->set_value( 'supports_custom_payments', $value, $needs_save );
	}

	/**
	 * Gets the value stored for the Country Code.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_account_country() {
		return $this->account_country;
	}

	/**
	 * Sets the value for Account Country locally, in this instance of the Merchant.
	 *
	 * @since TBD
	 *
	 * @param mixed   $value      Value used for the Account Country.
	 * @param boolean $needs_save Determines if the proprieties saved need to save to the DB.
	 */
	public function set_account_country( $value, $needs_save = true ) {
		$this->set_value( 'account_country', $value, $needs_save );
	}

	/**
	 * Determines if this instances needs to be saved to the DB.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function needs_save() {
		return tribe_is_truthy( $this->needs_save );
	}


	/**
	 * Returns the options key for the account in the merchant mode.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_account_key() {
		$gateway_key   = Gateway::get_key();
		$merchant_mode = $this->get_mode();

		return "tickets_commerce_{$gateway_key}_{$merchant_mode}_account";
	}

	/**
	 * Returns the data retrieved from the access token refreshing process.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_access_token_data_key() {
		$gateway_key   = Gateway::get_key();
		$merchant_mode = $this->get_mode();

		return "tickets_commerce_{$gateway_key}_{$merchant_mode}_access_token_data";
	}

	/**
	 * Returns the options key for the account errors in the merchant mode.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_account_errors_key() {
		$gateway_key   = Gateway::get_key();
		$merchant_mode = $this->get_mode();

		return "tickets_commerce_{$gateway_key}_{$merchant_mode}_account_errors";
	}


	/**
	 * Handle initial setup for the object singleton.
	 *
	 * @since TBD
	 */
	public function init() {
		$this->set_mode( tribe_tickets_commerce_is_test_mode() ? 'sandbox' : 'live' );
		$this->from_array( $this->get_details_data(), false );
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
			'merchant_id'              => $this->get_merchant_id(),
			'merchant_id_in_paypal'    => $this->get_merchant_id_in_paypal(),
			'client_id'                => $this->get_client_id(),
			'client_secret'            => $this->get_client_secret(),
			'account_is_ready'         => $this->get_account_is_ready(),
			'supports_custom_payments' => $this->get_supports_custom_payments(),
			'account_country'          => $this->get_account_country(),
			'access_token'             => $this->get_access_token(),
		];
	}

	/**
	 * Make Merchant object from array.
	 *
	 * @since TBD
	 *
	 * @param array   $data       Which values need to .
	 * @param boolean $needs_save Determines if the proprieties saved need to save to the DB.
	 *
	 * @return boolean
	 */
	public function from_array( array $data, $needs_save = true ) {
		if ( ! $this->validate( $data ) ) {
			return false;
		}

		$this->setup_properties( $data, $needs_save );

		return true;
	}

	protected function set_value( $key, $value, $needs_save = true ) {
		$this->{$key} = $value;

		// Determine if we will need to save in the DB.
		if ( $needs_save ) {
			$this->needs_save = true;
		}
	}

	/**
	 * Setup properties from array.
	 *
	 * @since TBD
	 *
	 * @param array   $data       Which values need to be saved.
	 * @param boolean $needs_save Determines if the proprieties saved need to save to the DB.
	 */
	protected function setup_properties( array $data, $needs_save = true ) {
		if ( isset( $data['merchant_id'] ) ) {
			$this->set_merchant_id( $data['merchant_id'], $needs_save );
		}
		if ( isset( $data['merchant_id_in_paypal'] ) ) {
			$this->set_merchant_id_in_paypal( $data['merchant_id_in_paypal'], $needs_save );
		}
		if ( isset( $data['client_id'] ) ) {
			$this->set_client_id( $data['client_id'], $needs_save );
		}
		if ( isset( $data['client_secret'] ) ) {
			$this->set_client_secret( $data['client_secret'], $needs_save );
		}
		if ( isset( $data['account_is_ready'] ) ) {
			$this->set_account_is_ready( $data['account_is_ready'], $needs_save );
		}
		if ( isset( $data['supports_custom_payments'] ) ) {
			$this->set_supports_custom_payments( $data['supports_custom_payments'], $needs_save );
		}
		if ( isset( $data['account_country'] ) ) {
			$this->set_account_country( $data['account_country'], $needs_save );
		}
		if ( isset( $data['access_token'] ) ) {
			$this->set_access_token( $data['access_token'], $needs_save );
		}
	}

	/**
	 * Validate merchant details.
	 *
	 * @since 5.1.6
	 *
	 * @param array $merchant_details
	 */
	public function validate( $merchant_details ) {
		$required = [
			'merchant_id',
			'merchant_id_in_paypal',
			'client_id',
			'client_secret',
			'account_is_ready',
			'supports_custom_payments',
			'account_country',
			'access_token',
		];

		if ( array_diff( $required, array_keys( $merchant_details ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Save merchant details.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function save() {
		if ( false === $this->needs_save() ) {
			return false;
		}

		$saved = tribe_update_option( $this->get_account_key(), $this->to_array() );

		// If we were able to save, we reset the needs save.
		if ( $saved ) {
			$this->needs_save = false;
		}

		return $saved;
	}

	/**
	 * Saves the account error message.
	 *
	 * @since TBD
	 *
	 * @param string[] $error_message
	 *
	 * @return bool
	 */
	public function save_account_errors( $error_message ) {
		return tribe_update_option( $this->get_account_errors_key(), $error_message );
	}

	/**
	 * Saves the account error message.
	 *
	 * @since TBD
	 *
	 * @param string[] $error_message
	 *
	 * @return bool
	 */
	public function save_access_token_data( $token_data ) {
		if ( empty( $token_data['access_token'] ) ) {
			return false;
		}

		$this->set_access_token( $token_data['access_token'] );
		$this->save();

		return tribe_update_option( $this->get_access_token_data_key(), $token_data );
	}

	/**
	 * Returns whether or not the account has been connected
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function account_is_connected() {
		return tribe_is_truthy( $this->merchant_id_in_paypal );
	}

	/**
	 * Get the merchant details data.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	protected function get_details_data() {
		return (array) tribe_get_option( $this->get_account_key(), [] );
	}

	/**
	 * Returns the account errors if there are any.
	 *
	 * @since TBD
	 *
	 * @return string[]|null
	 */
	public function get_account_errors() {
		return tribe_get_option( $this->get_account_errors_key(), null );
	}

	/**
	 * Delete merchant account details on the Database.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function delete_data() {
		return tribe_update_option( $this->get_account_key(), null );
	}

	/**
	 * Delete access token data.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function delete_access_token_data() {
		return tribe_update_option( $this->get_access_token_data_key(), null );
	}

	/**
	 * Deletes the errors for the account.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function delete_account_errors() {
		return tribe_update_option( $this->get_account_errors_key(), null );
	}
}