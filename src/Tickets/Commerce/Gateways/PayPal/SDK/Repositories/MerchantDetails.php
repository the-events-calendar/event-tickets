<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories;

use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Models\MerchantDetail;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\PayPalClient;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\Traits\HasMode;

/**
 * Class MerchantDetails
 *
 * @since 5.1.6
 */
class MerchantDetails {

	use HasMode;

	/**
	 * Handle initial setup for the object singleton.
	 *
	 * @since 5.1.6
	 */
	public function init() {
		$this->setMode( tribe_tickets_commerce_is_test_mode() ? 'sandbox' : 'live' );
	}

	/**
	 * Returns whether or not the account has been connected
	 *
	 * @since 5.1.6
	 *
	 * @return bool
	 */
	public function accountIsConnected() {
		/* @var $merchantDetail MerchantDetail */
		$merchantDetail = tribe( MerchantDetail::class );

		return (bool) $merchantDetail->merchantIdInPayPal;
	}

	/**
	 * Get the merchant details data.
	 *
	 * @since 5.1.6
	 *
	 * @return array
	 */
	public function getDetailsData() {
		return (array) get_option( $this->getAccountKey(), [] );
	}

	/**
	 * Get merchant details.
	 *
	 * @since 5.1.6
	 *
	 * @return MerchantDetail
	 */
	public function getDetails() {
		return MerchantDetail::fromArray( $this->getDetailsData() );
	}

	/**
	 * Save merchant details.
	 *
	 * @since 5.1.6
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
	 * @since 5.1.6
	 *
	 * @return bool
	 */
	public function delete() {
		return delete_option( $this->getAccountKey() );
	}

	/**
	 * Returns the account errors if there are any
	 *
	 * @since 5.1.6
	 *
	 * @return string[]|null
	 */
	public function getAccountErrors() {
		return get_option( $this->getAccountErrorsKey(), null );
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
	public function saveAccountErrors( $errorMessage ) {
		return update_option( $this->getAccountErrorsKey(), $errorMessage );
	}

	/**
	 * Deletes the errors for the account
	 *
	 * @since 5.1.6
	 *
	 * @return bool
	 */
	public function deleteAccountErrors() {
		return delete_option( $this->getAccountErrorsKey() );
	}

	/**
	 * Deletes the client token for the account
	 *
	 * @since 5.1.6
	 *
	 * @return bool
	 */
	public function deleteClientToken() {
		return delete_transient( $this->getClientTokenKey() );
	}

	/**
	 * Get client token for hosted credit card fields.
	 *
	 * @since 5.1.6
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

		if ( ! array_key_exists( 'client_token', $response ) ) {
			return '';
		}

		// Expire token before one minute to prevent unnecessary race condition.
		set_transient( $optionName, $response['client_token'], $response['expires_in'] - 60 );

		return $response['clientToken'];
	}

	/**
	 * Returns the options key for the account in the give mode
	 *
	 * @since 5.1.6
	 *
	 * @return string
	 */
	public function getAccountKey() {
		return "tribe_tickets_paypal_commerce_{$this->mode}_account";
	}

	/**
	 * Returns the options key for the account errors in the give mode
	 *
	 * @since 5.1.6
	 *
	 * @return string
	 */
	private function getAccountErrorsKey() {
		return "tribe_tickets_paypal_commerce_{$this->mode}_account_errors";
	}

	/**
	 * Returns the options key for the client token in the give mode
	 *
	 * @since 5.1.6
	 *
	 * @return string
	 */
	private function getClientTokenKey() {
		return "tribe_tickets_paypal_commerce_{$this->mode}_client_token";
	}
}
