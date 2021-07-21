<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use TEC\Tickets\Commerce\Gateways\PayPal\Models\Merchant_Detail;
use TEC\Tickets\Commerce\Gateways\PayPal\Repositories\PayPal_Auth;
use TEC\Tickets\Commerce\Gateways\PayPal\Repositories\Merchant_Details;

/**
 * Class RefreshToken
 *
 * @since 5.1.6
 */
class Refresh_Token {

	/*
	 * @since 5.1.6
	 *
	 * @var MerchantDetail
	 */
	private $merchant_detail;

	/**
	 * @since 5.1.6
	 *
	 * @var Merchant_Details
	 */
	private $details_repository;

	/**
	 * @since 5.1.6
	 *
	 * @var PayPal_Auth
	 */
	private $paypal_auth;

	/**
	 * RefreshToken constructor.
	 *
	 * @since 5.1.6
	 *
	 * @param Merchant_Details $details_repository
	 * @param PayPal_Auth      $paypal_auth
	 * @param Merchant_Detail  $merchant_detail
	 */
	public function __construct(
		Merchant_Details $details_repository,
		PayPal_Auth $paypal_auth,
		Merchant_Detail $merchant_detail
	) {
		$this->details_repository = $details_repository;
		$this->paypal_auth       = $paypal_auth;
		$this->merchant_detail    = $merchant_detail;
	}

	/**
	 * Return cron json name which uses to refresh token.
	 *
	 * @since 5.1.6
	 *
	 * @return string
	 */
	private function get_cron_job_hook_name() {
		return 'tribe_tickets_commerce_paypal_commerce_refresh_token';
	}

	/**
	 * Register cron job to refresh access token.
	 * Note: only for internal use.
	 *
	 * @since 5.1.6
	 *
	 * @param string $tokenExpires What time the token expires.
	 */
	public function register_cron_job_to_refresh_token( $tokenExpires ) {
		// @todo Verify we need this as a cron, do we lose total API access if it expires (no visitors)?
		wp_schedule_single_event(
			// Refresh token before half hours of expires date.
			time() + ( $tokenExpires - 1800 ),
			$this->get_cron_job_hook_name()
		);
	}

	/**
	 * Delete cron job which refresh access token.
	 * Note: only for internal use.
	 *
	 * @since 5.1.6
	 */
	public function delete_refresh_token_cron_job() {
		wp_clear_scheduled_hook( $this->get_cron_job_hook_name() );
	}

	/**
	 * Refresh token.
	 * Note: only for internal use
	 *
	 * @since 5.1.6
	 */
	public function refresh_token() {
		// Exit if account is not connected.
		if ( ! $this->details_repository->account_is_connected() ) {
			return;
		}

		$token_details = $this->paypal_auth->get_token_from_client_credentials( $this->merchant_detail->client_id, $this->merchant_detail->client_secret );

		$this->merchant_detail->set_token_details( $token_details );
		$this->details_repository->save( $this->merchant_detail );

		$this->register_cron_job_to_refresh_token( $token_details['expires_in'] );
	}
}
