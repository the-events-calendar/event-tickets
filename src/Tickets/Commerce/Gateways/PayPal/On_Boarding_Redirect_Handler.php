<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use Exception;
use TEC\Tickets\Commerce\Gateways\PayPal\Models\Merchant_Detail;
use TEC\Tickets\Commerce\Gateways\PayPal\Repositories\PayPal_Auth;
use TEC\Tickets\Commerce\Gateways\PayPal\Repositories\Merchant_Details;
use TEC\Tickets\Commerce\Gateways\PayPal\Repositories\Webhooks;
use Tribe__Settings;

/**
 * Class PayPalOnBoardingRedirectHandler
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 *
 */
class On_Boarding_Redirect_Handler {

	/**
	 * @since 5.1.6
	 *
	 * @var PayPal_Auth
	 */
	private $paypal_auth;

	/**
	 * @since 5.1.6
	 *
	 * @var Webhooks
	 */
	private $webhooks_repository;

	/**
	 * @since 5.1.6
	 *
	 * @var Merchant_Details
	 */
	private $merchant_repository;

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
	 * @param Webhooks         $webhooks
	 * @param Merchant_Details $merchant_repository
	 * @param Settings         $settings
	 * @param PayPal_Auth      $paypal_auth
	 */
	public function __construct( Webhooks $webhooks, Merchant_Details $merchant_repository, Settings $settings, PayPal_Auth $paypal_auth ) {
		$this->webhooks_repository = $webhooks;
		$this->merchant_repository = $merchant_repository;
		$this->settings            = $settings;
		$this->paypal_auth         = $paypal_auth;
	}

	/**
	 * Bootstrap class
	 *
	 * @since 5.1.6
	 */
	public function boot() {
		if ( $this->is_paypal_user_redirected() ) {
			$merchant_id           = tribe_get_request_var( 'merchantId' );
			$merchant_id_in_paypal = tribe_get_request_var( 'merchantIdInPayPal' );

			$details = $this->save_paypal_merchant_details( $merchant_id, $merchant_id_in_paypal );

			$this->set_up_webhook( $details );
			$this->redirect_account_connected();

			return;
		}

		if ( $this->were_paypal_account_details_saved() ) {
			$this->register_paypal_ssl_notice();
			$this->register_paypal_account_connected_notice();
		}

		if ( $this->is_status_refresh() ) {
			$this->refresh_account_status();
			$this->redirect_account_connected();

			return;
		}
	}

	/**
	 * Save PayPal merchant details
	 *_
	 *
	 * @since 5.1.6
	 *
	 * @param string $merchant_id           The merchant ID.
	 * @param string $merchant_id_in_paypal The merchant ID in PayPal.
	 *
	 * @return Merchant_Detail
	 */
	private function save_paypal_merchant_details( $merchant_id, $merchant_id_in_paypal ) {
		$partner_link_info = $this->settings->get_partner_link_details();
		$token_info        = $this->settings->get_access_token();

		$paypal_account = [
			'merchant_id'           => $merchant_id,
			'merchant_id_in_paypal' => $merchant_id_in_paypal,
		];

		$errors = [];

		if ( empty( $paypal_account['merchant_id_in_paypal'] ) ) {
			$errors[] = [
				'type'    => 'url',
				'message' => esc_html__( 'There was a problem with PayPal return url and we could not find valid merchant ID. Paypal return URL is:', 'event-tickets' ) . "\n",
				'value'   => urlencode( $_SERVER['QUERY_STRING'] ),
			];

			// Log error messages.
			array_map( static function ( $error_message ) {
				$error_message = is_array( $error_message ) ? $error_message['message'] . ' ' . $error_message['value'] : $error_message;
				tribe( 'logger' )->log_error( $error_message, 'tickets-commerce-paypal-commerce' );
			}, $errors );

			$this->merchant_repository->save_account_errors( $errors );

			$this->redirect_when_on_boarding_fail();
		}

		$rest_api_credentials = (array) $this->paypal_auth->get_seller_rest_api_credentials( $token_info ? $token_info['access_token'] : '' );

		$this->did_we_get_valid_seller_rest_api_credentials( $rest_api_credentials );

		$token_info = $this->paypal_auth->get_token_from_client_credentials( $rest_api_credentials['client_id'], $rest_api_credentials['client_secret'] );
		//$this->settings->update_access_token( $tokenInfo );

		$paypal_account['client_id']                = $rest_api_credentials['client_id'];
		$paypal_account['client_secret']            = $rest_api_credentials['client_secret'];
		$paypal_account['token']                    = $token_info;
		$paypal_account['supports_custom_payments'] = 'PPCP' === $partner_link_info['product'];
		$paypal_account['account_is_ready']         = true;
		$paypal_account['account_country']          = $this->settings->get_account_country();

		$merchantDetails = Merchant_Detail::from_array( $paypal_account );
		$this->merchant_repository->save( $merchantDetails );

		return $merchantDetails;
	}

	/**
	 * Redirects the user to the account connected url
	 *
	 * @since 5.1.6
	 */
	private function redirect_account_connected() {
		$this->refresh_account_status();

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
	 * @param Merchant_Detail $merchant_details
	 */
	private function set_up_webhook( Merchant_Detail $merchant_details ) {
		if ( ! is_ssl() ) {
			return;
		}

		try {
			$webhook_config = $this->webhooks_repository->create_webhook( $merchant_details->access_token );

			$this->webhooks_repository->save_webhook_config( $webhook_config );
		} catch ( Exception $ex ) {
			tribe( 'logger' )->log_error( $ex->getMessage(), 'tickets-commerce-paypal-commerce' );

			$errors = [];

			$errors[] = esc_html__( 'There was a problem with creating webhook on PayPal. A gateway error log also added to get details information about PayPal response.', 'event-tickets' );

			// Log error messages.
			array_map( static function ( $error_message ) {
				$error_message = is_array( $error_message ) ? $error_message['message'] . ' ' . $error_message['value'] : $error_message;
				tribe( 'logger' )->log_error( $error_message, 'tickets-commerce-paypal-commerce' );
			}, $errors );

			$this->merchant_repository->save_account_errors( $errors );
			$this->redirect_when_on_boarding_fail();
		}
	}

	/**
	 * Register notice if account connect success fully.
	 *
	 * @since 5.1.6
	 */
	private function register_paypal_account_connected_notice() {
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
	private function is_settings_page() {
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
	private function is_status_refresh() {
		return isset( $_GET['paypalStatusCheck'] ) && $this->is_settings_page();
	}

	/**
	 * Return whether or not PayPal user redirect to setting page after successful onboarding.
	 *
	 * @since 5.1.6
	 *
	 * @return bool
	 */
	private function is_paypal_user_redirected() {
		return isset( $_GET['merchantIdInPayPal'] ) && $this->is_settings_page();
	}

	/**
	 * Return whether or not PayPal account details were saved.
	 *
	 * @since 5.1.6
	 *
	 * @return bool
	 */
	private function were_paypal_account_details_saved() {
		return isset( $_GET['paypal-commerce-account-connected'] ) && $this->is_settings_page();
	}

	/**
	 * validate rest api credential.
	 *
	 * @since 5.1.6
	 *
	 * @param array $array
	 *
	 */
	private function did_we_get_valid_seller_rest_api_credentials( $array ) {
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
			array_map( static function ( $errorMessage ) {
				$errorMessage = is_array( $errorMessage ) ? $errorMessage['message'] . ' ' . $errorMessage['value'] : $errorMessage;
				tribe( 'logger' )->log_error( $errorMessage, 'tickets-commerce-paypal-commerce' );
			}, $errors );

			$this->merchant_repository->save_account_errors( $errors );
			$this->redirect_when_on_boarding_fail();
		}
	}

	/**
	 * Handles the request for refreshing the account status
	 *
	 * @since 5.1.6
	 */
	private function refresh_account_status() {
		$merchantDetails = $this->merchant_repository->get_details();

		$status_errors = $this->is_admin_successfully_on_boarded( $merchantDetails->merchant_id_in_paypal, $merchantDetails->access_token, $merchantDetails->supports_custom_payments );
		if ( $status_errors !== true ) {
			$merchantDetails->account_is_ready = false;
			$this->merchant_repository->save_account_errors( $status_errors );
		} else {
			$merchantDetails->account_is_ready = true;
			$this->merchant_repository->delete_account_errors();
		}

		$this->merchant_repository->save( $merchantDetails );

		$details = $this->save_paypal_merchant_details( $merchantDetails->merchant_id, $merchantDetails->merchant_id_in_paypal );

		$this->set_up_webhook( $details );
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
	private function is_admin_successfully_on_boarded( $merchantId, $accessToken, $usesCustomPayments ) {
		$on_boarded_data  = (array) $this->paypal_auth->get_seller_on_boarding_details_from_paypal( $merchantId, $accessToken );
		$on_boarded_data  = array_filter( $on_boarded_data ); // Remove empty values.
		$error_messages[] = [
			'type'    => 'json',
			'message' => esc_html__( 'PayPal merchant status check API request response is:', 'event-tickets' ),
			'value'   => wp_json_encode( $on_boarded_data ),
		];

		if ( ! is_ssl() ) {
			$error_messages[] = esc_html__( 'A valid SSL certificate is required to accept payments and set up your PayPal account. Once a
					certificate is installed and the site is using https, please disconnect and reconnect your account.', 'event-tickets' );
		}

		if ( array_diff( [ 'payments_receivable', 'primary_email_confirmed' ], array_keys( $on_boarded_data ) ) ) {
			$error_messages[] = esc_html__( 'There was a problem with the status check for your account. Please try disconnecting and connecting again. If the problem persists, please contact support.', 'event-tickets' );

			// Log error messages.
			array_map( static function ( $error_message ) {
				$error_message = is_array( $error_message ) ? $error_message['message'] . ' ' . $error_message['value'] : $error_message;
				tribe( 'logger' )->log_error( $error_message, 'tickets-commerce-paypal-commerce' );
			}, $error_messages );

			// Return here since the rest of the validations will definitely fail
			return $error_messages;
		}

		if ( ! $on_boarded_data['payments_receivable'] ) {
			$error_messages[] = esc_html__( 'Set up an account to receive payment from PayPal', 'event-tickets' );
		}

		if ( ! $on_boarded_data['primary_email_confirmed'] ) {
			$errorMessage[] = esc_html__( 'Confirm your primary email address', 'event-tickets' );
		}

		if ( ! $usesCustomPayments ) {
			return count( $error_messages ) > 1 ? $error_messages : true;
		}

		if ( array_diff( [ 'products', 'capabilities' ], array_keys( $on_boarded_data ) ) ) {
			$error_messages[] = esc_html__( 'Your account was expected to be able to accept custom payments, but is not. Please make sure your
				account country matches the country setting. If the problem persists, please contact PayPal.', 'event-tickets' );

			// Return here since the rest of the validations will definitely fail
			return $error_messages;
		}

		// Grab the PPCP_CUSTOM product from the status data
		$custom_product = current(
			array_filter(
				$on_boarded_data['products'],
				static function ( $product ) {
					return 'PPCP_CUSTOM' === $product['name'];
				}
			)
		);

		if ( empty( $custom_product ) || $custom_product['vetting_status'] !== 'SUBSCRIBED' ) {
			$error_messages[] = esc_html__( 'Reach out to PayPal to enable PPCP_CUSTOM for your account', 'event-tickets' );
		}

		// Loop through the capabilities and see if any are not active
		$invalid_capabilities = [];
		foreach ( $on_boarded_data['capabilities'] as $capability ) {
			if ( $capability['status'] !== 'ACTIVE' ) {
				$invalid_capabilities[] = $capability['name'];
			}
		}

		if ( ! empty( $invalid_capabilities ) ) {
			$error_messages[] = esc_html__( 'Reach out to PayPal to resolve the following capabilities:', 'event-tickets' ) . ' ' . implode( ', ', $invalid_capabilities );
		}

		// If there were errors then redirect the user with notices
		return count( $error_messages ) > 1 ? $error_messages : true;
	}

	/**
	 * Redirect admin to setting section with error.
	 *
	 * @since 5.1.6
	 */
	private function redirect_when_on_boarding_fail() {
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
	private function register_paypal_ssl_notice() {
		if ( ! is_ssl() || ! empty( $this->webhooks_repository->get_webhook_config() ) ) {
			return;
		}

		/** @var Tribe__Settings $settings */
		$settings = tribe( 'settings' );

		// Get link to Help page.
		$log_url = $settings->get_url( [
				'page' => 'tribe-help',
			] ) . '#tribe-event-log';

		$log_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			$log_url,
			esc_html__( 'logged data', 'event-tickets' )
		);

		tribe_error(
			'paypal-webhook-error',
			sprintf(
			// Translators: %1$s: The logged data link.
				esc_html__( 'There was a problem setting up the webhooks for your PayPal account. Please try disconnecting and reconnecting your PayPal account. If the problem persists, please contact support and provide them with the latest %1$s', 'event-tickets' ),
				$log_link
			),
			[
				'type' => 'error',
			]
		);
	}
}
