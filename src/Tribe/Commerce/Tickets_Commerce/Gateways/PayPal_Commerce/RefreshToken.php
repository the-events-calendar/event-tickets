<?php

namespace TEC\PaymentGateways\PayPalCommerce;

use TEC\PaymentGateways\PayPalCommerce\SDK\Models\MerchantDetail;
use TEC\PaymentGateways\PayPalCommerce\Repositories\MerchantDetails;
use TEC\PaymentGateways\PayPalCommerce\Repositories\PayPalAuth;

/**
 * Class RefreshToken
 *
 * @since TBD
 */
class RefreshToken {

	/* @var MerchantDetail */
	private $merchantDetail;

	/**
	 * @since TBD
	 *
	 * @var MerchantDetails
	 */
	private $detailsRepository;

	/**
	 * @since TBD
	 *
	 * @var PayPalAuth
	 */
	private $payPalAuth;

	/**
	 * RefreshToken constructor.
	 *
	 * @since TBD
	 *
	 * @param MerchantDetails $detailsRepository
	 * @param PayPalAuth      $payPalAuth
	 * @param MerchantDetail  $merchantDetail
	 */
	public function __construct( MerchantDetails $detailsRepository, PayPalAuth $payPalAuth, MerchantDetail $merchantDetail ) {
		$this->detailsRepository = $detailsRepository;
		$this->payPalAuth        = $payPalAuth;
		$this->merchantDetail    = $merchantDetail;
	}

	/**
	 * Return cron json name which uses to refresh token.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	private function getCronJobHookName() {
		return 'give_paypal_commerce_refresh_token';
	}

	/**
	 * Register cron job to refresh access token.
	 * Note: only for internal use.
	 *
	 * @since TBD
	 *
	 * @param string $tokenExpires
	 *
	 */
	public function registerCronJobToRefreshToken( $tokenExpires ) {
		wp_schedule_single_event( time() + ( $tokenExpires - 1800 ), // Refresh token before half hours of expires date.
			$this->getCronJobHookName() );
	}

	/**
	 * Delete cron job which refresh access token.
	 * Note: only for internal use.
	 *
	 * @since TBD
	 *
	 */
	public function deleteRefreshTokenCronJob() {
		wp_clear_scheduled_hook( $this->getCronJobHookName() );
	}

	/**
	 * Refresh token.
	 * Note: only for internal use
	 *
	 * @since TBD
	 */
	public function refreshToken() {
		// Exit if account is not connected.
		if ( ! $this->detailsRepository->accountIsConnected() ) {
			return;
		}

		$tokenDetails = $this->payPalAuth->getTokenFromClientCredentials( $this->merchantDetail->clientId, $this->merchantDetail->clientSecret );

		$this->merchantDetail->setTokenDetails( $tokenDetails );
		$this->detailsRepository->save( $this->merchantDetail );

		$this->registerCronJobToRefreshToken( $tokenDetails['expiresIn'] );
	}
}
