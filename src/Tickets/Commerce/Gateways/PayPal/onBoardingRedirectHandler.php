<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use Exception;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Models\MerchantDetail;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\PayPalAuth;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\MerchantDetails;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\Webhooks;
use Tribe__Settings;

/**
 * Class PayPalOnBoardingRedirectHandler
 *
 * @since 5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 *
 */
class onBoardingRedirectHandler {

	/**
	 * @since 5.1.6
	 *
	 * @var PayPalAuth
	 */
	private $payPalAuth;

	/**
	 * @since 5.1.6
	 *
	 * @var Webhooks
	 */
	private $webhooksRepository;

	/**
	 * @since 5.1.6
	 *
	 * @var MerchantDetails
	 */
	private $merchantRepository;

	/**
	 * @since 5.1.6
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * onBoardingRedirectHandler constructor.
	 *
	 * @since 5.1.6
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
	 * @since 5.1.6
	 */
	public function boot() {
		if ( $this->isPayPalUserRedirected() ) {
			$merchantId = tribe_get_request_var( 'merchantId' );
			$merchantIdInPayPal = tribe_get_request_var( 'merchantIdInPayPal' );

			$details = $this->savePayPalMerchantDetails( $merchantId, $merchantIdInPayPal );

			$this->setUpWebhook( $details );
			$this->redirectAccountConnected();

			return;
		}

		if ( $this->werePayPalAccountDetailsSaved() ) {
			$this->registerPayPalSSLNotice();
			$this->registerPayPalAccountConnectedNotice();
		}

		if ( $this->isStatusRefresh() ) {
			$this->refreshAccountStatus();
			$this->redirectAccountConnected();

			return;
		}
	}

	/**
	 * Save PayPal merchant details
	 *
	 * @since 5.1.6
	 *
	 * @param string $merchantId         The merchant ID.
	 * @param string $merchantIdInPayPal The merchant ID in PayPal.
	 *
	 * @return MerchantDetail
	 */
	private function savePayPalMerchantDetails( $merchantId, $merchantIdInPayPal ) {
		$partnerLinkInfo = $this->settings->get_partner_link_details();
		$tokenInfo       = $this->settings->get_access_token();

		$payPalAccount = [
			'merchantId'         => $merchantId,
			'merchantIdInPayPal' => $merchantIdInPayPal,
		];

		$errors = [];

		if ( empty( $payPalAccount['merchantIdInPayPal'] ) ) {
			$errors[] = [
				'type'    => 'url',
				'message' => esc_html__( 'There was a problem with PayPal return url and we could not find valid merchant ID. Paypal return URL is:', 'event-tickets' ) . "\n",
				'value'   => urlencode( $_SERVER['QUERY_STRING'] ),
			];

			// Log error messages.
			array_map( static function( $errorMessage ) {
				$errorMessage = is_array( $errorMessage ) ? $errorMessage['message'] . ' ' . $errorMessage['value'] : $errorMessage;
				tribe( 'logger' )->log_error( $errorMessage, 'tickets-commerce-paypal-commerce' );
			}, $errors );

			$this->merchantRepository->saveAccountErrors( $errors );

			$this->redirectWhenOnBoardingFail();
		}

		$restApiCredentials = (array) $this->payPalAuth->getSellerRestAPICredentials( $tokenInfo ? $tokenInfo['access_token'] : '' );

		$this->didWeGetValidSellerRestApiCredentials( $restApiCredentials );

		$tokenInfo = $this->payPalAuth->getTokenFromClientCredentials( $restApiCredentials['client_id'], $restApiCredentials['client_secret'] );
		//$this->settings->update_access_token( $tokenInfo );

		$payPalAccount['clientId']               = $restApiCredentials['client_id'];
		$payPalAccount['clientSecret']           = $restApiCredentials['client_secret'];
		$payPalAccount['token']                  = $tokenInfo;
		$payPalAccount['supportsCustomPayments'] = 'PPCP' === $partnerLinkInfo['product'];
		$payPalAccount['accountIsReady']         = true;
		$payPalAccount['accountCountry']         = $this->settings->get_account_country();

		$merchantDetails = MerchantDetail::fromArray( $payPalAccount );
		$this->merchantRepository->save( $merchantDetails );

		return $merchantDetails;
	}

	/**
	 * Redirects the user to the account connected url
	 *
	 * @since 5.1.6
	 */
	private function redirectAccountConnected() {
		$this->refreshAccountStatus();

		/** @var Tribe__Settings $settings */
		$settings = tribe( 'settings' );

		// Get link to Tickets Tab.
		$settings_url = $settings->get_url(
			[
				'page'                              => 'tribe-common',
				'tab'                               => 'event-tickets',
				'paypal-commerce-account-connected' => '1',
			]
		) . '#tribe-field-tickets-commerce-paypal-commerce';

		wp_redirect( $settings_url );
		die();
	}

	/**
	 * Sets up the webhook for the connected account
	 *
	 * @since 5.1.6
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
			tribe( 'logger' )->log_error( $ex->getMessage(), 'tickets-commerce-paypal-commerce' );

			$errors = [];

			$errors[] = esc_html__( 'There was a problem with creating webhook on PayPal. A gateway error log also added to get details information about PayPal response.', 'event-tickets' );

			// Log error messages.
			array_map( static function( $errorMessage ) {
				$errorMessage = is_array( $errorMessage ) ? $errorMessage['message'] . ' ' . $errorMessage['value'] : $errorMessage;
				tribe( 'logger' )->log_error( $errorMessage, 'tickets-commerce-paypal-commerce' );
			}, $errors );

			$this->merchantRepository->saveAccountErrors( $errors );
			$this->redirectWhenOnBoardingFail();
		}
	}

	/**
	 * Register notice if account connect success fully.
	 *
	 * @since 5.1.6
	 */
	private function registerPayPalAccountConnectedNotice() {
		tribe_notice(
			'paypal-commerce-account-connected',
			sprintf(
				'<p>%s</p>',
				esc_html__( 'PayPal Commerce account connected successfully.', 'event-tickets' )
			),
			[
				'type' => 'success',
			]
		);
	}

	/**
	 * Check whether we are on the settings page.
	 *
	 * @since 5.1.6
	 *
	 * @return bool Whether we are on the settings page.
	 */
	private function isSettingsPage() {
		$page = tribe_get_request_var( 'page' );
		$tab  = tribe_get_request_var( 'tab' );

		return 'tribe-common' === $page && 'event-tickets' === $tab;
	}

	/**
	 * Returns whether or not the current request is for refreshing the account status
	 *
	 * @since 5.1.6
	 *
	 * @return bool
	 */
	private function isStatusRefresh() {
		return isset( $_GET['paypalStatusCheck'] ) && $this->isSettingsPage();
	}

	/**
	 * Return whether or not PayPal user redirect to setting page after successful onboarding.
	 *
	 * @since 5.1.6
	 *
	 * @return bool
	 */
	private function isPayPalUserRedirected() {
		return isset( $_GET['merchantIdInPayPal'] ) && $this->isSettingsPage();
	}

	/**
	 * Return whether or not PayPal account details were saved.
	 *
	 * @since 5.1.6
	 *
	 * @return bool
	 */
	private function werePayPalAccountDetailsSaved() {
		return isset( $_GET['paypal-commerce-account-connected'] ) && $this->isSettingsPage();
	}

	/**
	 * validate rest api credential.
	 *
	 * @since 5.1.6
	 *
	 * @param array $array
	 *
	 */
	private function didWeGetValidSellerRestApiCredentials( $array ) {
		$required = [ 'client_id', 'client_secret' ];
		$array    = array_filter( $array ); // Remove empty values.

		$errors = [];

		if ( array_diff( $required, array_keys( $array ) ) ) {
			$errors[] = [
				'type'    => 'json',
				'message' => esc_html__( 'PayPal client access token API request response is:', 'event-tickets' ),
				'value'   => wp_json_encode( $this->settings->get_access_token() ),
			];

			$errors[] = [
				'type'    => 'json',
				'message' => esc_html__( 'PayPal client rest api credentials API request response is:', 'event-tickets' ),
				'value'   => wp_json_encode( $array ),
			];

			$errors[] = esc_html__( 'There was a problem with PayPal client rest API request and we could not find valid client id and secret.', 'event-tickets' );

			// Log error messages.
			array_map( static function( $errorMessage ) {
				$errorMessage = is_array( $errorMessage ) ? $errorMessage['message'] . ' ' . $errorMessage['value'] : $errorMessage;
				tribe( 'logger' )->log_error( $errorMessage, 'tickets-commerce-paypal-commerce' );
			}, $errors );

			$this->merchantRepository->saveAccountErrors( $errors );
			$this->redirectWhenOnBoardingFail();
		}
	}

	/**
	 * Handles the request for refreshing the account status
	 *
	 * @since 5.1.6
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

		$details = $this->savePayPalMerchantDetails( $merchantDetails->merchantId, $merchantDetails->merchantIdInPayPal );

		$this->setUpWebhook( $details );
	}

	/**
	 * Validate seller on Boarding status
	 *
	 * @since 5.1.6
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
			$errorMessages[] = esc_html__( 'A valid SSL certificate is required to accept payments and set up your PayPal account. Once a
					certificate is installed and the site is using https, please disconnect and reconnect your account.', 'event-tickets' );
		}

		if ( array_diff( [ 'payments_receivable', 'primary_email_confirmed' ], array_keys( $onBoardedData ) ) ) {
			$errorMessages[] = esc_html__( 'There was a problem with the status check for your account. Please try disconnecting and connecting again. If the problem persists, please contact support.', 'event-tickets' );

			// Log error messages.
			array_map( static function( $errorMessage ) {
				$errorMessage = is_array( $errorMessage ) ? $errorMessage['message'] . ' ' . $errorMessage['value'] : $errorMessage;
				tribe( 'logger' )->log_error( $errorMessage, 'tickets-commerce-paypal-commerce' );
			}, $errorMessages );

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
	 * @since 5.1.6
	 */
	private function redirectWhenOnBoardingFail() {
		/** @var Tribe__Settings $settings */
		$settings = tribe( 'settings' );

		// Get link to Tickets Tab.
		$settings_url = $settings->get_url( [
            'page'         => 'tribe-common',
            'tab'          => 'event-tickets',
            'paypal-error' => '1',
		] ) . '#tribe-field-tickets-commerce-paypal-commerce';

		wp_redirect( $settings_url );
		die();
	}

	/**
	 * Displays a notice of the site is not using SSL
	 *
	 * @since 5.1.6
	 */
	private function registerPayPalSSLNotice() {
		if ( ! is_ssl() || ! empty( $this->webhooksRepository->getWebhookConfig() ) ) {
			return;
		}

		/** @var Tribe__Settings $settings */
		$settings = tribe( 'settings' );

		// Get link to Help page.
		$log_url = $settings->get_url( [
            'page' => 'tribe-help',
		] ) . '#tribe-event-log';

		$logLink = sprintf(
			'<a href="%1$s">%2$s</a>',
			$log_url,
			esc_html__( 'logged data', 'event-tickets' )
		);

		tribe_error(
			'paypal-webhook-error',
			sprintf(
				// Translators: %1$s: The logged data link.
				esc_html__( 'There was a problem setting up the webhooks for your PayPal account. Please try disconnecting and reconnecting your PayPal account. If the problem persists, please contact support and provide them with the latest %1$s', 'event-tickets' ),
				$logLink
			),
			[
				'type' => 'error',
			]
		);
	}
}
