<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK\Models;

use InvalidArgumentException;

/**
 * Class MerchantDetail
 *
 * @since TBD
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce
 *
 */
class MerchantDetail {

	/**
	 * PayPal merchant Id  (email address)
	 *
	 * @since TBD
	 *
	 * @var null|string
	 */
	public $merchantId = null;

	/**
	 * PayPal merchant id
	 *
	 * @since TBD
	 *
	 * @var null|string
	 */
	public $merchantIdInPayPal = null;

	/**
	 * Client id.
	 *
	 * @since TBD
	 *
	 * @var null |string
	 */
	public $clientId = null;

	/**
	 * Client Secret
	 *
	 * @since TBD
	 *
	 * @var null|string
	 */
	public $clientSecret = null;

	/**
	 * Access token.
	 *
	 * @since TBD
	 *
	 * @var null|string
	 */
	public $accessToken = null;

	/**
	 * Whether or not the connected account is ready to process donations.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	public $accountIsReady = false;

	/**
	 * Whether or not the account can make custom payments (i.e Advanced Fields & PPCP)
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	public $supportsCustomPayments;

	/**
	 * PayPal account accountCountry.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	public $accountCountry;

	/**
	 * Access token.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	private $tokenDetails = null;

	/**
	 * Return array of merchant details.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function toArray() {
		return [
			'merchantId'             => $this->merchantId,
			'merchantIdInPayPal'     => $this->merchantIdInPayPal,
			'clientId'               => $this->clientId,
			'clientSecret'           => $this->clientSecret,
			'token'                  => $this->tokenDetails,
			'accountIsReady'         => $this->accountIsReady,
			'supportsCustomPayments' => $this->supportsCustomPayments,
			'accountCountry'         => $this->accountCountry,
		];
	}

	/**
	 * Make MerchantDetail object from array.
	 *
	 * @since TBD
	 *
	 * @param array $merchantDetails
	 *
	 * @return MerchantDetail
	 */
	public static function fromArray( $merchantDetails ) {
		$obj = new static();

		if ( ! $merchantDetails ) {
			return $obj;
		}

		$obj->validate( $merchantDetails );
		$obj->setupProperties( $merchantDetails );

		return $obj;
	}

	/**
	 * Setup properties from array.
	 *
	 * @since TBD
	 *
	 * @param $merchantDetails
	 *
	 */
	private function setupProperties( $merchantDetails ) {
		$this->merchantId         = $merchantDetails['merchantId'];
		$this->merchantIdInPayPal = $merchantDetails['merchantIdInPayPal'];

		$this->clientId               = $merchantDetails['clientId'];
		$this->clientSecret           = $merchantDetails['clientSecret'];
		$this->tokenDetails           = $merchantDetails['token'];
		$this->accountIsReady         = $merchantDetails['accountIsReady'];
		$this->supportsCustomPayments = $merchantDetails['supportsCustomPayments'];
		$this->accountCountry         = $merchantDetails['accountCountry'];
		$this->accessToken            = $this->tokenDetails['accessToken'];
	}

	/**
	 * Validate merchant details.
	 *
	 * @since TBD
	 *
	 * @param array $merchantDetails
	 */
	private function validate( $merchantDetails ) {
		$required = [
			'merchantId',
			'merchantIdInPayPal',
			'clientId',
			'clientSecret',
			'token',
			'accountIsReady',
			'supportsCustomPayments',
			'accountCountry',
		];

		if ( array_diff( $required, array_keys( $merchantDetails ) ) ) {
			throw new InvalidArgumentException( esc_html__( 'To create a MerchantDetail object, please provide the following: ' . implode( ', ', $required ), 'event-tickets' ) );
		}
	}

	/**
	 * Get refresh token code.
	 *
	 * @since TBD
	 *
	 * @param array $tokenDetails
	 */
	public function setTokenDetails( $tokenDetails ) {
		$this->tokenDetails = array_merge( $this->tokenDetails, $tokenDetails );
	}
}
