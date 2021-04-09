<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK_Interface\Repositories;

use TEC\Helpers\ArrayDataSet;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK\Models\MerchantDetail;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK\PayPalClient;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK\Repositories\Traits\HasMode;

/**
 * Class MerchantDetails
 *
 * @since TBD
 */
class MerchantDetails {

	use HasMode;

	/**
	 * Returns whether or not the account has been connected
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function accountIsConnected() {
		/* @var $merchantDetails MerchantDetail */
		$merchantDetails = tribe( MerchantDetail::class );

		return (bool) $merchantDetails->merchantIdInPayPal;
	}

	/**
	 * Get merchant details.
	 *
	 * @since TBD
	 *
	 * @return MerchantDetail
	 */
	public function getDetails() {
		return MerchantDetail::fromArray( get_option( $this->getAccountKey(), [] ) );
	}

	/**
	 * Save merchant details.
	 *
	 * @since TBD
	 *
	 * @param MerchantDetail $merchantDetails
	 *
	 * @return bool
	 */
	public function save( MerchantDetail $merchantDetails ) {
		return update_option( $this->getAccountKey(), $merchantDetails->toArray() );
	}

	/**
	 * Delete merchant details.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function delete() {
		return delete_option( $this->getAccountKey() );
	}

	/**
	 * Returns the account errors if there are any
	 *
	 * @since TBD
	 *
	 * @return string[]|null
	 */
	public function getAccountErrors() {
		return get_option( $this->getAccountErrorsKey(), null );
	}

	/**
	 * Saves the account error message
	 *
	 * @since TBD
	 *
	 * @param string[] $errorMessage
	 *
	 * @return bool
	 */
	public function saveAccountErrors( $errorMessage ) {
		return update_option( $this->getAccountErrorsKey(), $errorMessage );
	}

	/**
	 * Deletes the errors for the account
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function deleteAccountErrors() {
		return delete_option( $this->getAccountErrorsKey() );
	}

	/**
	 * Deletes the client token for the account
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function deleteClientToken() {
		return delete_transient( $this->getClientTokenKey() );
	}

	/**
	 * Get client token for hosted credit card fields.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function getClientToken() {
		$optionName = $this->getClientTokenKey();

		if ( $optionValue = get_transient( $optionName ) ) {
			return $optionValue;
		}

		/** @var MerchantDetail $merchant */
		$merchant = tribe( MerchantDetail::class );

		$response = wp_remote_retrieve_body(
			wp_remote_post(
				tribe( PayPalClient::class )->getApiUrl( 'v1/identity/generate-token' ),
				[
					'headers' => [
						'Accept'          => 'application/json',
						'Accept-Language' => 'en_US',
						'Authorization'   => sprintf( 'Bearer %1$s', $merchant->accessToken ),
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

		if ( ! array_key_exists( 'clientToken', $response ) ) {
			return '';
		}

		// Expire token before one minute to prevent unnecessary race condition.
		set_transient( $optionName, $response['clientToken'], $response['expiresIn'] - 60 );

		return $response['clientToken'];
	}

	/**
	 * Returns the options key for the account in the give mode
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function getAccountKey() {
		return "give_paypal_commerce_{$this->mode}_account";
	}

	/**
	 * Returns the options key for the account errors in the give mode
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	private function getAccountErrorsKey() {
		return "give_paypal_commerce_{$this->mode}_account_errors";
	}

	/**
	 * Returns the options key for the client token in the give mode
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	private function getClientTokenKey() {
		return "give_paypal_commerce_{$this->mode}_client_token";
	}
}
