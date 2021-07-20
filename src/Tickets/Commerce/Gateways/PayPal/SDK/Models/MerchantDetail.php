<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\SDK\Models;

use InvalidArgumentException;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\MerchantDetails;

/**
 * Class MerchantDetail
 *
 * @since 5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 *
 */
class MerchantDetail {

	/**
	 * PayPal merchant Id  (email address)
	 *
	 * @since 5.1.6
	 *
	 * @var null|string
	 */
	public $merchantId = null;

	/**
	 * PayPal merchant id
	 *
	 * @since 5.1.6
	 *
	 * @var null|string
	 */
	public $merchantIdInPayPal = null;

	/**
	 * Client id.
	 *
	 * @since 5.1.6
	 *
	 * @var null |string
	 */
	public $clientId = null;

	/**
	 * Client Secret
	 *
	 * @since 5.1.6
	 *
	 * @var null|string
	 */
	public $clientSecret = null;

	/**
	 * Access token.
	 *
	 * @since 5.1.6
	 *
	 * @var null|string
	 */
	public $accessToken = null;

	/**
	 * Whether or not the connected account is ready to process payments.
	 *
	 * @since 5.1.6
	 *
	 * @var bool
	 */
	public $accountIsReady = false;

	/**
	 * Whether or not the account can make custom payments (i.e Advanced Fields & PPCP)
	 *
	 * @since 5.1.6
	 *
	 * @var bool
	 */
	public $supportsCustomPayments;

	/**
	 * PayPal account accountCountry.
	 *
	 * @since 5.1.6
	 *
	 * @var bool
	 */
	public $accountCountry;

	/**
	 * Access token.
	 *
	 * @since 5.1.6
	 *
	 * @var array
	 */
	private $tokenDetails = null;

	/**
	 * Handle initial setup for the object singleton.
	 *
	 * @since 5.1.6
	 */
	public function init() {
		/** @var MerchantDetails $repository */
		$repository = tribe( MerchantDetails::class );

		$merchantDetails = $repository->getDetailsData();

		try {
			$this->validate( $merchantDetails );
		} catch ( InvalidArgumentException $exception ) {
			// Do not continue to set up the properties.
			return;
		}

		$this->setupProperties( $merchantDetails );
	}

	/**
	 * Return array of merchant details.
	 *
	 * @since 5.1.6
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
	 * @since 5.1.6
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
	 * @since 5.1.6
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
		$this->accessToken            = $this->tokenDetails['access_token'];
	}

	/**
	 * Validate merchant details.
	 *
	 * @since 5.1.6
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
	 * @since 5.1.6
	 *
	 * @param array $tokenDetails
	 */
	public function setTokenDetails( $tokenDetails ) {
		$this->tokenDetails = array_merge( $this->tokenDetails, $tokenDetails );
	}
}
