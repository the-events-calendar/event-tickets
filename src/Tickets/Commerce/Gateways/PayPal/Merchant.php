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
	 * @var null|string
	 */
	protected $merchant_id_in_paypal;

	/**
	 * Client id.
	 *
	 * @since TBD
	 *
	 * @var null |string
	 */
	protected $client_id;

	/**
	 * Client Secret.
	 *
	 * @since TBD
	 *
	 * @var null|string
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
	 * @var null|string
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
	 * @var bool
	 */
	protected $account_country;

	public function get_merchant_id() {
		return $this->merchant_id;
	}

	public function set_merchant_id( $value ) {
		$this->merchant_id = $value;
	}

	public function get_merchant_id_in_paypal() {
		return $this->merchant_id_in_paypal;
	}

	public function set_merchant_id_in_paypal( $value ) {
		$this->merchant_id_in_paypal = $value;
	}

	public function get_client_id() {
		return $this->client_id;
	}

	public function set_client_id( $value ) {
		$this->client_id = $value;
	}

	public function get_client_secret() {
		return $this->client_secret;
	}

	public function set_client_secret( $value ) {
		$this->client_secret = $value;
	}

	public function get_access_token() {
		return $this->access_token;
	}

	public function set_access_token( $value ) {
		$this->access_token = $value;
	}

	public function get_account_is_ready() {
		return $this->account_is_ready;
	}

	public function set_account_is_ready( $value ) {
		$this->account_is_ready = $value;
	}

	public function get_supports_custom_payments() {
		return $this->supports_custom_payments;
	}

	public function set_supports_custom_payments( $value ) {
		$this->supports_custom_payments = $value;
	}

	public function get_account_country() {
		return $this->account_country;
	}

	public function set_account_country( $value ) {
		$this->account_country = $value;
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
		$gateway_key = Gateway::get_key();
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
		$gateway_key = Gateway::get_key();
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
		$this->from_array( $this->get_details_data() );
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
	 * Make MerchantDetail object from array.
	 *
	 * @since TBD
	 *
	 * @param array $data
	 *
	 * @return boolean
	 */
	public function from_array( array $data ) {
		if ( ! $this->validate( $data ) ) {
			return false;
		}

		$this->setup_properties( $data );

		return true;
	}

	/**
	 * Setup properties from array.
	 *
	 * @since TBD
	 *
	 * @param array $data
	 *
	 */
	protected function setup_properties( array $data ) {
		$this->set_merchant_id( $data['merchant_id'] );
		$this->set_merchant_id_in_paypal( $data['merchant_id_in_paypal'] );
		$this->set_client_id( $data['client_id'] );
		$this->set_client_secret( $data['client_secret'] );
		$this->set_account_is_ready( $data['account_is_ready'] );
		$this->set_supports_custom_payments( $data['supports_custom_payments'] );
		$this->set_account_country( $data['account_country'] );
		$this->set_access_token( $data['access_token'] );
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
		return tribe_update_option( $this->get_account_key(), $this->to_array() );
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
	public function get_details_data() {
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
	 * Delete merchant details.
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