<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce;

use Exception;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK\Models\MerchantDetail;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\Repositories\MerchantDetails;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\Repositories\PayPalAuth;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\Repositories\Settings;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\Repositories\Webhooks;
use Give_Admin_Settings;

/**
 * Class PayPalOnBoardingRedirectHandler
 *
 * @since TBD
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce
 *
 */
class onBoardingRedirectHandler {

	/**
	 * @since TBD
	 *
	 * @var PayPalAuth
	 */
	private $payPalAuth;

	/**
	 * @since TBD
	 *
	 * @var Webhooks
	 */
	private $webhooksRepository;

	/**
	 * @since TBD
	 *
	 * @var MerchantDetails
	 */
	private $merchantRepository;

	/**
	 * @since TBD
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * onBoardingRedirectHandler constructor.
	 *
	 * @since TBD
	 *
	 * @param Webhooks        $webhooks
	 * @param MerchantDetails $merchantRepository
	 * @param Settings        $settings
	 * @param PayPalAuth      $payPalAuth
	 */
	public function __construct( Webhooks $webhooks, MerchantDetails $merchantRepository, Settings $settings, PayPalAuth $payPalAuth ) {
		$this->webhooksRepository = $webhooks;
		$this->merchantRepository = $merchantRepository;
		$this->settings           = $settings;
		$this->payPalAuth         = $payPalAuth;
	}

	/**
	 * Bootstrap class
	 *
	 * @since TBD
	 */
	public function boot() {
		if ( $this->isPayPalUserRedirected() ) {
			$details = $this->savePayPalMerchantDetails();
			$this->setUpWebhook( $details );
			$this->redirectAccountConnected();
		}

		if ( $this->isPayPalAccountDetailsSaved() ) {
			$this->registerPayPalSSLNotice();
			$this->registerPayPalAccountConnectedNotice();
		}

		if ( $this->isStatusRefresh() ) {
			$this->refreshAccountStatus();
		}
	}

	/**
	 * Save PayPal merchant details
	 *
	 * @since TBD
	 *
	 * @return MerchantDetail
	 */
	private function savePayPalMerchantDetails() {
		$paypalGetData   = wp_parse_args( $_SERVER['QUERY_STRING'] );
		$partnerLinkInfo = $this->settings->getPartnerLinkDetails();
		$tokenInfo       = $this->settings->getAccessToken();

		$allowedPayPalData = [
			'merchantId',
			'merchantIdInPayPal',
		];

		$payPalAccount = array_intersect_key( $paypalGetData, array_flip( $allowedPayPalData ) );

		if ( ! array_key_exists( 'merchantIdInPayPal', $payPalAccount ) || empty( $payPalAccount['merchantIdInPayPal'] ) ) {
			$errors[] = [
				'type'    => 'url',
				'message' => esc_html__( 'There was a problem with PayPal return url and we could not find valid merchant ID. Paypal return URL is:', 'event-tickets' ) . "\n",
				'value'   => urlencode( $_SERVER['QUERY_STRING'] ),
			];

			$this->merchantRepository->saveAccountErrors( $errors );
			$this->redirectWhenOnBoardingFail();
		}

		$restApiCredentials = (array) $this->payPalAuth->getSellerRestAPICredentials( $tokenInfo ? $tokenInfo['accessToken'] : '' );
		$this->didWeGetValidSellerRestApiCredentials( $restApiCredentials );

		$tokenInfo = $this->payPalAuth->getTokenFromClientCredentials( $restApiCredentials['client_id'], $restApiCredentials['client_secret'] );
		$this->settings->updateAccessToken( $tokenInfo );

		$payPalAccount['clientId']               = $restApiCredentials['client_id'];
		$payPalAccount['clientSecret']           = $restApiCredentials['client_secret'];
		$payPalAccount['token']                  = $tokenInfo;
		$payPalAccount['supportsCustomPayments'] = 'PPCP' === $partnerLinkInfo['product'];
		$payPalAccount['accountIsReady']         = true;
		$payPalAccount['accountCountry']         = $this->settings->getAccountCountry();

		$merchantDetails = MerchantDetail::fromArray( $payPalAccount );
		$this->merchantRepository->save( $merchantDetails );

		$this->deleteTempOptions();

		return $merchantDetails;
	}

	/**
	 * Redirects the user to the account connected url
	 *
	 * @since TBD
	 */
	private function redirectAccountConnected() {
		$this->refreshAccountStatus();

		// @todo Replace this URL.
		wp_redirect( admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=paypal&group=paypal-commerce&paypal-commerce-account-connected=1' ) );

		exit();
	}

	/**
	 * Sets up the webhook for the connected account
	 *
	 * @since TBD
	 *
	 * @param MerchantDetail $merchant_details
	 */
	private function setUpWebhook( MerchantDetail $merchant_details ) {
		if ( ! is_ssl() ) {
			return;
		}

		try {
			$webhookConfig = $this->webhooksRepository->createWebhook( $merchant_details->accessToken );
			$this->webhooksRepository->saveWebhookConfig( $webhookConfig );
		} catch ( Exception $ex ) {
			$errors[] = esc_html__( 'There was a problem with creating webhook on PayPal. A gateway error log also added to get details information about PayPal response.', 'event-tickets' );

			$this->merchantRepository->saveAccountErrors( $errors );
			$this->redirectWhenOnBoardingFail();
		}
	}

	/**
	 * Delete temp data
	 *
	 * @since TBD
	 * @return void
	 */
	private function deleteTempOptions() {
		$this->settings->deletePartnerLinkDetails();
		$this->settings->deleteAccessToken();
	}

	/**
	 * Register notice if account connect success fully.
	 *
	 * @since TBD
	 */
	private function registerPayPalAccountConnectedNotice() {
		// @todo Figure out what this does more precisely different from tribe_notice() and replace it.
		Give_Admin_Settings::add_message( 'paypal-commerce-account-connected', esc_html__( 'PayPal account connected successfully.', 'event-tickets' ) );
	}

	/**
	 * Returns whether or not the current request is for refreshing the account status
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	private function isStatusRefresh() {
		// @todo Check if on the admin script page.
		return isset( $_GET['paypalStatusCheck'] ) && Give_Admin_Settings::is_setting_page( 'gateways', 'paypal' );
	}

	/**
	 * Return whether or not PayPal user redirect to GiveWP setting page after successful onboarding.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	private function isPayPalUserRedirected() {
		// @todo Check if on the admin script page.
		return isset( $_GET['merchantIdInPayPal'] ) && Give_Admin_Settings::is_setting_page( 'gateways', 'paypal' );
	}

	/**
	 * Return whether or not PayPal account details saved.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	private function isPayPalAccountDetailsSaved() {
		// @todo Check if on the admin script page.
		return isset( $_GET['paypal-commerce-account-connected'] ) && Give_Admin_Settings::is_setting_page( 'gateways', 'paypal' );
	}

	/**
	 * validate rest api credential.
	 *
	 * @since TBD
	 *
	 * @param array $array
	 *
	 */
	private function didWeGetValidSellerRestApiCredentials( $array ) {
		$required = [ 'client_id', 'client_secret' ];
		$array    = array_filter( $array ); // Remove empty values.

		if ( array_diff( $required, array_keys( $array ) ) ) {
			$errors[] = [
				'type'    => 'json',
				'message' => esc_html__( 'PayPal client access token API request response is:', 'event-tickets' ),
				'value'   => wp_json_encode( $this->settings->getAccessToken() ),
			];

			$errors[] = [
				'type'    => 'json',
				'message' => esc_html__( 'PayPal client rest api credentials API request response is:', 'event-tickets' ),
				'value'   => wp_json_encode( $array ),
			];

			$errors[] = esc_html__( 'There was a problem with PayPal client rest API request and we could not find valid client id and secret.', 'event-tickets' );

			$this->merchantRepository->saveAccountErrors( $errors );
			$this->redirectWhenOnBoardingFail();
		}
	}

	/**
	 * Handles the request for refreshing the account status
	 *
	 * @since TBD
	 */
	private function refreshAccountStatus() {
		$merchantDetails = $this->merchantRepository->getDetails();

		$statusErrors = $this->isAdminSuccessfullyOnBoarded( $merchantDetails->merchantIdInPayPal, $merchantDetails->accessToken, $merchantDetails->supportsCustomPayments );
		if ( $statusErrors !== true ) {
			$merchantDetails->accountIsReady = false;
			$this->merchantRepository->saveAccountErrors( $statusErrors );
		} else {
			$merchantDetails->accountIsReady = true;
			$this->merchantRepository->deleteAccountErrors();
		}

		$this->merchantRepository->save( $merchantDetails );
	}

	/**
	 * Validate seller on Boarding status
	 *
	 * @since TBD
	 *
	 * @param string $merchantId
	 * @param string $accessToken
	 * @param bool   $usesCustomPayments
	 *
	 * @return true|string[]
	 */
	private function isAdminSuccessfullyOnBoarded( $merchantId, $accessToken, $usesCustomPayments ) {
		$onBoardedData   = (array) $this->payPalAuth->getSellerOnBoardingDetailsFromPayPal( $merchantId, $accessToken );
		$onBoardedData   = array_filter( $onBoardedData ); // Remove empty values.
		$errorMessages[] = [
			'type'    => 'json',
			'message' => esc_html__( 'PayPal merchant status check API request response is:', 'event-tickets' ),
			'value'   => wp_json_encode( $onBoardedData ),
		];

		if ( ! is_ssl() ) {
			$errorMessages[] = esc_html__( 'A valid SSL certificate is required to accept donations and set up your PayPal account. Once a
					certificate is installed and the site is using https, please disconnect and reconnect your account.', 'event-tickets' );
		}

		if ( array_diff( [ 'payments_receivable', 'primary_email_confirmed' ], array_keys( $onBoardedData ) ) ) {
			$errorMessages[] = esc_html__( 'There was a problem with the status check for your account. Please try disconnecting and connecting again. If the problem persists, please contact support.', 'event-tickets' );

			// Return here since the rest of the validations will definitely fail
			return $errorMessages;
		}

		if ( ! $onBoardedData['payments_receivable'] ) {
			$errorMessages[] = esc_html__( 'Set up an account to receive payment from PayPal', 'event-tickets' );
		}

		if ( ! $onBoardedData['primary_email_confirmed'] ) {
			$errorMessage[] = esc_html__( 'Confirm your primary email address', 'event-tickets' );
		}

		if ( ! $usesCustomPayments ) {
			return count( $errorMessages ) > 1 ? $errorMessages : true;
		}

		if ( array_diff( [ 'products', 'capabilities' ], array_keys( $onBoardedData ) ) ) {
			$errorMessages[] = esc_html__( 'Your account was expected to be able to accept custom payments, but is not. Please make sure your
				account country matches the country setting. If the problem persists, please contact PayPal.', 'event-tickets' );

			// Return here since the rest of the validations will definitely fail
			return $errorMessages;
		}

		// Grab the PPCP_CUSTOM product from the status data
		$customProduct = current(
			array_filter(
				$onBoardedData['products'],
				static function ( $product ) {
					return 'PPCP_CUSTOM' === $product['name'];
				}
			)
		);

		if ( empty( $customProduct ) || $customProduct['vetting_status'] !== 'SUBSCRIBED' ) {
			$errorMessages[] = esc_html__( 'Reach out to PayPal to enable PPCP_CUSTOM for your account', 'event-tickets' );
		}

		// Loop through the capabilities and see if any are not active
		$invalidCapabilities = [];
		foreach ( $onBoardedData['capabilities'] as $capability ) {
			if ( $capability['status'] !== 'ACTIVE' ) {
				$invalidCapabilities[] = $capability['name'];
			}
		}

		if ( ! empty( $invalidCapabilities ) ) {
			$errorMessages[] = esc_html__( 'Reach out to PayPal to resolve the following capabilities:', 'event-tickets' ) . ' ' . implode( ', ', $invalidCapabilities );
		}

		// If there were errors then redirect the user with notices
		return count( $errorMessages ) > 1 ? $errorMessages : true;
	}

	/**
	 * Redirect admin to setting section with error.
	 *
	 * @since TBD
	 */
	private function redirectWhenOnBoardingFail() {
		wp_redirect( admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=paypal&group=paypal-commerce&paypal-error=1' ) );

		exit();
	}

	/**
	 * Displays a notice of the site is not using SSL
	 *
	 * @since TBD
	 */
	private function registerPayPalSSLNotice() {
		if ( ! is_ssl() || ! empty( $this->webhooksRepository->getWebhookConfig() ) ) {
			return;
		}

		// @todo Replace the URL.
		$logLink = sprintf(
			'<a href="%1$s">%2$s</a>',
			admin_url( '/edit.php?post_type=give_forms&page=give-tools&tab=logs' ),
			esc_html__( 'logs data', 'event-tickets' )
		);

		// @todo Add the translator doc.
		Give_Admin_Settings::add_error(
			'paypal-webhook-error',
			sprintf(
				esc_html__( 'There was a problem setting up the webhooks for your PayPal account. Please try disconnecting and reconnecting your PayPal account. If the problem persists, please contact support and provide them with the latest %1$s', 'event-tickets' ),
				$logLink
			)
		);
	}
}
