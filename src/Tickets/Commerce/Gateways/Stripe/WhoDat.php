<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_WhoDat;
use TEC\Tickets\Commerce\Gateways\Stripe\REST\On_Boarding_Endpoint;
use TEC\Tickets\Commerce\Gateways\Stripe\REST\Webhook_Endpoint;

/**
 * Class WhoDat. Handles connection to Stripe when the platform keys are needed.
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class WhoDat extends Abstract_WhoDat {

	/**
	 * The API Path.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public $api_endpoint = 'stripe';

	/**
	 * Creates a new account link for the client and redirects the user to setup the account details.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function connect_account() {
		$query_args = [
			'token'      => urlencode( tribe( Gateway::class )->generate_unique_tracking_id() ),
			'return_url' => tribe( On_Boarding_Endpoint::class )->get_return_url(),
		];

		$connection_url = $this->get( 'connect', $query_args );

		wp_safe_redirect( $connection_url );
		exit();
	}

	/**
	 * De-authorize the current seller account in Stripe oAuth.
	 *
	 * @since 5.3.0
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
	 * Register a newly connected Stripe account to the website.
	 *
	 * @since 5.3.0
	 *
	 * @param array $account_data array of data returned from Stripe after a successful connection.
	 */
	public function onboard_account( $account_data ) {

		$query_args = [
			'grant_type' => 'authorization_code',
			'code' => $account_data['code'],
		];

		return $this->get( 'token', $query_args );
	}

	/**
	 * Requests WhoDat to refresh the oAuth tokens.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function refresh_token() {
		$refresh_token = tribe( Gateway::class )->get_current_refresh_token();

		$query_args = [
			'grant_type' => 'refresh_token',
			'refresh_token' => $refresh_token,
		];

		return $this->get( 'token', $query_args );
	}
}
