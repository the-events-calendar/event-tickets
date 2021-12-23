<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Abstract_WhoDat;
use TEC\Tickets\Commerce\Gateways\Stripe\REST\On_Boarding_Endpoint;

/**
 * Class Connect_Client
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class WhoDat extends Abstract_WhoDat {

	/**
	 * The API URL.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $api_url = 'https://whodat.theeventscalendar.com/commerce/v1/stripe';

	/**
	 * Creates a new account link for the client and redirects the user to setup the account details.
	 *
	 * @since TBD
	 */
	public function connect_account() {
		$token_url = tribe( Gateway::class )->generate_unique_tracking_id();

		$return_url = tribe( On_Boarding_Endpoint::class )->get_return_url();
		$query_args = [
			'token'       => esc_url( $token_url ),
			'return_url' => esc_url( $return_url ),
		];

		return $this->get( 'connect', $query_args );
	}

	/**
	 * @todo Once the user finishes onboarding in the Stripe screens, WhoDat should send back the user
	 *        account to this method, which will fetch the information and store it.
	 */
	public function onboard_account( $account ) {
		$data = $this->get_seller_signup_data( $account );

		$this->store_seller_data();
	}

	/**
	 * @todo  calls WhoDat to get the onboarded seller data
	 *
	 * @since TBD
	 *
	 * @param $account_id
	 */
	public function get_seller_signup_data( $account_id ) {
		$data = wp_remote_get();

		return $data;
	}

	/**
	 * Verify if the seller was successfully onboarded.
	 *
	 * @since TBD
	 *
	 * @param string $saved_merchant_id The ID we are looking at Paypal with.
	 *
	 * @return array
	 */
	public function get_seller_status( $saved_merchant_id ) {
		$query_args = [
			'mode'        => tribe( Merchant::class )->get_mode(),
			'merchant_id' => $saved_merchant_id,
		];

		return $this->post( 'seller/status', $query_args );
	}
}
