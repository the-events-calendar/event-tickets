<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_WhoDat;
use TEC\Tickets\Commerce\Gateways\PayPal\REST\On_Boarding_Endpoint;

/**
 * Class Connect_Client
 *
 * @since   5.1.6
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class WhoDat extends Abstract_WhoDat {

	/**
	 * The API Path.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public $api_endpoint = 'paypal';

	/**
	 * Fetch the signup link from PayPal.
	 *
	 * @since 5.1.9
	 *
	 * @param string $hash    Which unique hash we are passing to the PayPal system.
	 * @param string $country Which country code we are using.
	 *
	 * @return array|string
	 */
	public function get_seller_signup_data( $hash, $country ) {
		if ( empty( $hash ) ) {
			$hash = tribe( Signup::class )->generate_unique_signup_hash();
		}

		$return_url = tribe( On_Boarding_Endpoint::class )->get_return_url( $hash );
		$query_args = [
			'mode'        => tribe( Merchant::class )->get_mode(),
			'nonce'       => $hash,
			'tracking_id' => urlencode( tribe( Gateway::class )->generate_unique_tracking_id() ),
			'return_url'  => esc_url( $return_url ),
			'country'     => $country,
		];

		return $this->get( 'seller/signup', $query_args );
	}

	/**
	 * Fetch the seller referral Data from WhoDat/PayPal.
	 *
	 * @since 5.1.9
	 *
	 * @param string $url Which URL WhoDat needs to request.
	 *
	 * @return array
	 */
	public function get_seller_referral_data( $url ) {
		$query_args = [
			'mode' => tribe( Merchant::class )->get_mode(),
			'url'  => $url,
		];

		return $this->get( 'seller/referral-data', $query_args );
	}

	/**
	 * Verify if the seller was successfully onboarded.
	 *
	 * @since 5.1.9
	 * @since 5.18.0 Added runtime cache.
	 *
	 * @param string $saved_merchant_id The ID we are looking at Paypal with.
	 *
	 * @return array
	 */
	public function get_seller_status( $saved_merchant_id ) {
		$cache = tribe_cache();

		$cache_key = 'paypal_seller_status_' . md5( $saved_merchant_id );

		// Adding cache changed my checkout page from 12s to 2s. Lets keep it!
		if ( isset( $cache[ $cache_key ] ) && is_array( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		$query_args = [
			'mode'        => tribe( Merchant::class )->get_mode(),
			'merchant_id' => $saved_merchant_id,
		];

		$cache[ $cache_key ] = $this->post( 'seller/status', $query_args );

		return $cache[ $cache_key ];
	}

	/**
	 * Get seller rest API credentials
	 *
	 * @since 5.1.9
	 *
	 * @param string $access_token
	 *
	 * @return array|null
	 */
	public function get_seller_credentials( $access_token ) {
		$query_args = [
			'mode'         => tribe( Merchant::class )->get_mode(),
			'access_token' => $access_token,
		];

		return $this->post( 'seller/credentials', $query_args );
	}

}
