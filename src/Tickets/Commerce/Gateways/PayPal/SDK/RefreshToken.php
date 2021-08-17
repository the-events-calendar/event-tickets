<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\SDK;

use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Models\MerchantDetail;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\PayPalAuth;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\MerchantDetails;

/**
 * Class RefreshToken
 *
 * @since 5.1.6
 */
class RefreshToken {

	/*
	 * @since 5.1.6
	 *
	 * @var MerchantDetail
	 */
	private $merchantDetail;

	/**
	 * @since 5.1.6
	 *
	 * @var MerchantDetails
	 */
	private $detailsRepository;

	/**
	 * @since 5.1.6
	 *
	 * @var PayPalAuth
	 */
	private $payPalAuth;

	/**
	 * RefreshToken constructor.
	 *
	 * @since 5.1.6
	 *
	 * @param MerchantDetails $detailsRepository
	 * @param PayPalAuth      $payPalAuth
	 * @param MerchantDetail  $merchantDetail
	 */
	public function __construct(
		MerchantDetails $detailsRepository,
		PayPalAuth $payPalAuth,
		MerchantDetail $merchantDetail
	) {
		$this->detailsRepository = $detailsRepository;
		$this->payPalAuth        = $payPalAuth;
		$this->merchantDetail    = $merchantDetail;
	}

	/**
	 * Return cron json name which uses to refresh token.
	 *
	 * @since 5.1.6
	 *
	 * @return string
	 */
	private function getCronJobHookName() {
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
	public function registerCronJobToRefreshToken( $tokenExpires ) {
		// @todo Verify we need this as a cron, do we lose total API access if it expires (no visitors)?
		wp_schedule_single_event(
			// Refresh token before half hours of expires date.
			time() + ( $tokenExpires - 1800 ),
			$this->getCronJobHookName()
		);
	}

	/**
	 * Delete cron job which refresh access token.
	 * Note: only for internal use.
	 *
	 * @since 5.1.6
	 */
	public function deleteRefreshTokenCronJob() {
		wp_clear_scheduled_hook( $this->getCronJobHookName() );
	}

	/**
	 * Refresh token.
	 * Note: only for internal use
	 *
	 * @since 5.1.6
	 */
	public function refreshToken() {
		// Exit if account is not connected.
		if ( ! $this->detailsRepository->accountIsConnected() ) {
			return;
		}

		$tokenDetails = $this->payPalAuth->getTokenFromClientCredentials( $this->merchantDetail->clientId, $this->merchantDetail->clientSecret );

		$this->merchantDetail->setTokenDetails( $tokenDetails );
		$this->detailsRepository->save( $this->merchantDetail );

		$this->registerCronJobToRefreshToken( $tokenDetails['expires_in'] );
	}
}
