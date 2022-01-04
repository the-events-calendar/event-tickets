<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_WhoDat;
use TEC\Tickets\Commerce\Gateways\Stripe\REST\On_Boarding_Endpoint;

/**
 * Class WhoDat. Handles connection to Stripe when the platform keys are needed
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
	 *
	 * @return string
	 */
	public function connect_account() {
		$token_url = tribe( Gateway::class )->generate_unique_tracking_id();

		$return_url = tribe( On_Boarding_Endpoint::class )->get_return_url();
		$query_args = [
			'token'      => $token_url,
			'return_url' => esc_url( $return_url ),
		];

		$connection_url = $this->get( 'connect', $query_args );

		wp_safe_redirect( $connection_url );
		exit();
	}

	/**
	 * De-authorize the current seller account in Stripe oAuth
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function disconnect_account() {
		$account_id = tribe( Merchant::class )->get_account_id();

		$return_url = tribe( On_Boarding_Endpoint::class )->get_return_url();
		$query_args = [
			'stripe_user_id' => $account_id,
			'return_url'     => esc_url( $return_url ),
		];

		return $this->get( 'disconnect', $query_args );
	}

	/**
	 * Register a newly connected stripe account to the website
	 *
	 * @since TBD
	 *
	 * @param array $account_data array of data returned from stripe after a successful connection
	 */
	public function onboard_account( $account_data ) {
		$this->store_seller_data(); // @todo implement
	}

	/**
	 * Requests WhoDat to refresh the oAuth tokens
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function refresh_token() {
		$token_url = tribe( Gateway::class )->generate_unique_tracking_id();

		$query_args = [
			'token' => $token_url,
		];

		return $this->get( 'token', $query_args );
	}
}
