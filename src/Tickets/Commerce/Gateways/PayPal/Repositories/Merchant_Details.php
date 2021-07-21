<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Repositories;

use TEC\Tickets\Commerce\Gateways\PayPal\Models\Merchant_Detail;
use TEC\Tickets\Commerce\Gateways\PayPal\PayPal_Client;
use TEC\Tickets\Commerce\Gateways\PayPal\Repositories\Traits\Has_Mode;

/**
 * Class MerchantDetails
 *
 * @since 5.1.6
 */
class Merchant_Details {

	use Has_Mode;

	/**
	 * Handle initial setup for the object singleton.
	 *
	 * @since 5.1.6
	 */
	public function init() {
		$this->set_mode( tribe_tickets_commerce_is_test_mode() ? 'sandbox' : 'live' );
	}

	/**
	 * Returns whether or not the account has been connected
	 *
	 * @since 5.1.6
	 *
	 * @return bool
	 */
	public function account_is_connected() {
		/* @var $merchant_detail Merchant_Detail */
		$merchant_detail = tribe( Merchant_Detail::class );

		return (bool) $merchant_detail->merchant_id_in_paypal;
	}

	/**
	 * Get the merchant details data.
	 *
	 * @since 5.1.6
	 *
	 * @return array
	 */
	public function get_details_data() {
		return (array) get_option( $this->get_account_key(), [] );
	}

	/**
	 * Get merchant details.
	 *
	 * @since 5.1.6
	 *
	 * @return Merchant_Detail
	 */
	public function get_details() {
		return Merchant_Detail::from_array( $this->get_details_data() );
	}

	/**
	 * Save merchant details.
	 *
	 * @since 5.1.6
	 *
	 * @param Merchant_Detail $merchant_details
	 *
	 * @return bool
	 */
	public function save( Merchant_Detail $merchant_details ) {
		return update_option( $this->get_account_key(), $merchant_details->to_array() );
	}

	/**
	 * Delete merchant details.
	 *
	 * @since 5.1.6
	 *
	 * @return bool
	 */
	public function delete() {
		return delete_option( $this->get_account_key() );
	}

	/**
	 * Returns the account errors if there are any
	 *
	 * @since 5.1.6
	 *
	 * @return string[]|null
	 */
	public function get_account_errors() {
		return get_option( $this->get_account_errors_key(), null );
	}

	/**
	 * Saves the account error message
	 *
	 * @since 5.1.6
	 *
	 * @param string[] $errorMessage
	 *
	 * @return bool
	 */
	public function save_account_errors( $errorMessage ) {
		return update_option( $this->get_account_errors_key(), $errorMessage );
	}

	/**
	 * Deletes the errors for the account
	 *
	 * @since 5.1.6
	 *
	 * @return bool
	 */
	public function delete_account_errors() {
		return delete_option( $this->get_account_errors_key() );
	}

	/**
	 * Deletes the client token for the account
	 *
	 * @since 5.1.6
	 *
	 * @return bool
	 */
	public function delete_client_token() {
		return delete_transient( $this->get_client_token_key() );
	}

	/**
	 * Get client token for hosted credit card fields.
	 *
	 * @since 5.1.6
	 *
	 * @return string
	 */
	public function get_client_token() {
		$option_name = $this->get_client_token_key();

		if ( $option_value = get_transient( $option_name ) ) {
			return $option_value;
		}

		/** @var Merchant_Detail $merchant */
		$merchant = tribe( Merchant_Detail::class );

		$response = wp_remote_retrieve_body(
			wp_remote_post(
				tribe( PayPal_Client::class )->get_api_url( 'v1/identity/generate-token' ),
				[
					'headers' => [
						'Accept'          => 'application/json',
						'Accept-Language' => 'en_US',
						'Authorization'   => sprintf( 'Bearer %1$s', $merchant->access_token ),
						'Content-Type'    => 'application/json',
					],
				]
			)
		);

		if ( ! $response ) {
			return '';
		}

		// @todo Replace this with a new method somewhere else.
		$response = ArrayDataSet::camelCaseKeys( json_decode( $response, true ) );

		if ( ! array_key_exists( 'client_token', $response ) ) {
			return '';
		}

		// Expire token before one minute to prevent unnecessary race condition.
		set_transient( $option_name, $response['client_token'], $response['expires_in'] - 60 );

		return $response['clientToken'];
	}

	/**
	 * Returns the options key for the account in the give mode
	 *
	 * @since 5.1.6
	 *
	 * @return string
	 */
	public function get_account_key() {
		return "tribe_tickets_paypal_commerce_{$this->mode}_account";
	}

	/**
	 * Returns the options key for the account errors in the give mode
	 *
	 * @since 5.1.6
	 *
	 * @return string
	 */
	private function get_account_errors_key() {
		return "tribe_tickets_paypal_commerce_{$this->mode}_account_errors";
	}

	/**
	 * Returns the options key for the client token in the give mode
	 *
	 * @since 5.1.6
	 *
	 * @return string
	 */
	private function get_client_token_key() {
		return "tribe_tickets_paypal_commerce_{$this->mode}_client_token";
	}
}
