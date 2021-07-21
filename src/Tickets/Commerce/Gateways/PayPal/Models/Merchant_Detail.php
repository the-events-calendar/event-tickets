<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Models;

use InvalidArgumentException;
use TEC\Tickets\Commerce\Gateways\PayPal\Repositories\Merchant_Details;

/**
 * Class Merchant_Detail
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Models
 *
 */
class Merchant_Detail {

	/**
	 * PayPal merchant Id  (email address)
	 *
	 * @since 5.1.6
	 *
	 * @var null|string
	 */
	public $merchant_id = null;

	/**
	 * PayPal merchant id
	 *
	 * @since 5.1.6
	 *
	 * @var null|string
	 */
	public $merchant_id_in_paypal = null;

	/**
	 * Client id.
	 *
	 * @since 5.1.6
	 *
	 * @var null |string
	 */
	public $client_id = null;

	/**
	 * Client Secret
	 *
	 * @since 5.1.6
	 *
	 * @var null|string
	 */
	public $client_secret = null;

	/**
	 * Access token.
	 *
	 * @since 5.1.6
	 *
	 * @var null|string
	 */
	public $access_token = null;

	/**
	 * Whether or not the connected account is ready to process payments.
	 *
	 * @since 5.1.6
	 *
	 * @var bool
	 */
	public $account_is_ready = false;

	/**
	 * Whether or not the account can make custom payments (i.e Advanced Fields & PPCP)
	 *
	 * @since 5.1.6
	 *
	 * @var bool
	 */
	public $supports_custom_payments;

	/**
	 * PayPal account accountCountry.
	 *
	 * @since 5.1.6
	 *
	 * @var bool
	 */
	public $account_country;

	/**
	 * Access token.
	 *
	 * @since 5.1.6
	 *
	 * @var array
	 */
	private $token_details = null;

	/**
	 * Handle initial setup for the object singleton.
	 *
	 * @since 5.1.6
	 */
	public function init() {
		/** @var Merchant_Details $repository */
		$repository = tribe( Merchant_Details::class );

		$merchant_details = $repository->get_details_data();

		try {
			$this->validate( $merchant_details );
		} catch ( InvalidArgumentException $exception ) {
			// Do not continue to set up the properties.
			return;
		}

		$this->setup_properties( $merchant_details );
	}

	/**
	 * Return array of merchant details.
	 *
	 * @since 5.1.6
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'merchant_id'              => $this->merchant_id,
			'merchant_id_in_paypal'    => $this->merchant_id_in_paypal,
			'client_id'                => $this->client_id,
			'client_secret'            => $this->client_secret,
			'token'                    => $this->token_details,
			'account_is_ready'         => $this->account_is_ready,
			'supports_custom_payments' => $this->supports_custom_payments,
			'account_country'          => $this->account_country,
		];
	}

	/**
	 * Make MerchantDetail object from array.
	 *
	 * @since 5.1.6
	 *
	 * @param array $merchant_details
	 *
	 * @return Merchant_Detail
	 */
	public static function from_array( $merchant_details ) {
		$obj = new static();

		if ( ! $merchant_details ) {
			return $obj;
		}

		$obj->validate( $merchant_details );
		$obj->setup_properties( $merchant_details );

		return $obj;
	}

	/**
	 * Setup properties from array.
	 *
	 * @since 5.1.6
	 *
	 * @param $merchant_details
	 *
	 */
	private function setup_properties( $merchant_details ) {
		$this->merchant_id              = $merchant_details['merchant_id'];
		$this->merchant_id_in_paypal    = $merchant_details['merchant_id_in_paypal'];
		$this->client_id                = $merchant_details['client_id'];
		$this->client_secret            = $merchant_details['client_secret'];
		$this->token_details            = $merchant_details['token'];
		$this->account_is_ready         = $merchant_details['account_is_ready'];
		$this->supports_custom_payments = $merchant_details['supports_custom_payments'];
		$this->account_country          = $merchant_details['account_country'];
		$this->access_token             = $this->token_details['access_token'];
	}

	/**
	 * Validate merchant details.
	 *
	 * @since 5.1.6
	 *
	 * @param array $merchant_details
	 */
	private function validate( $merchant_details ) {
		$required = [
			'merchant_id',
			'merchant_id_in_paypal',
			'client_id',
			'client_secret',
			'token',
			'account_is_ready',
			'supports_custom_payments',
			'account_country',
		];

		if ( array_diff( $required, array_keys( $merchant_details ) ) ) {
			throw new InvalidArgumentException( esc_html__( 'To create a Merchant_Detail object, please provide the following: ' . implode( ', ', $required ), 'event-tickets' ) );
		}
	}

	/**
	 * Get refresh token code.
	 *
	 * @since 5.1.6
	 *
	 * @param array $token_details
	 */
	public function set_token_details( $token_details ) {
		$this->token_details = array_merge( $this->token_details, $token_details );
	}
}
