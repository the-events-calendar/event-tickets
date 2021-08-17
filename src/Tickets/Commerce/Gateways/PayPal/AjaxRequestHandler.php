<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Models\MerchantDetail;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\PayPalAuth;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\PayPalOrder;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\RefreshToken;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\MerchantDetails;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\Webhooks;

// @todo Bring this over.

/**
 * Class AjaxRequestHandler
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 *
 * @since 5.1.6
 */
class AjaxRequestHandler {

	/**
	 * @since 5.1.6
	 *
	 * @var Webhooks
	 */
	private $webhooksRepository;

	/**
	 * @since 5.1.6
	 *
	 * @var MerchantDetail
	 */
	private $merchantDetails;

	/**
	 * @since 5.1.6
	 *
	 * @var PayPalAuth
	 */
	private $payPalAuth;

	/**
	 * @since 5.1.6
	 *
	 * @var MerchantDetails
	 */
	private $merchantRepository;

	/**
	 * @since 5.1.6
	 *
	 * @var Connect_Client
	 */
	private $refreshToken;

	/**
	 * @since 5.1.6
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * AjaxRequestHandler constructor.
	 *
	 * @since 5.1.6
	 *
	 * @param Webhooks        $webhooksRepository
	 * @param MerchantDetail  $merchantDetails
	 * @param MerchantDetails $merchantRepository
	 * @param RefreshToken    $refreshToken
	 * @param Settings        $settings
	 * @param PayPalAuth      $payPalAuth
	 */
	public function __construct(
		Webhooks $webhooksRepository,
		MerchantDetail $merchantDetails,
		MerchantDetails $merchantRepository,
		RefreshToken $refreshToken,
		Settings $settings,
		PayPalAuth $payPalAuth
	) {
		$this->webhooksRepository = $webhooksRepository;
		$this->merchantDetails    = $merchantDetails;
		$this->merchantRepository = $merchantRepository;
		$this->refreshToken       = $refreshToken;
		$this->settings           = $settings;
		$this->payPalAuth         = $payPalAuth;
	}

	/**
	 *  give_paypal_commerce_user_onboarded ajax action handler
	 *
	 * @since 5.1.6
	 */
	public function onBoardedUserAjaxRequestHandler() {
		$this->validateAdminRequest();

		$partnerLinkInfo = $this->settings->get_partner_link_details();

		$payPalResponse = $this->payPalAuth->getTokenFromAuthorizationCode(
			tribe_get_request_var( 'sharedId' ),
			tribe_get_request_var( 'authCode' ),
			$partnerLinkInfo['nonce']
		);

		if ( ! $payPalResponse || array_key_exists( 'error', $payPalResponse ) ) {
			wp_send_json_error( __( 'Unexpected response from PayPal when onboarding', 'event-tickets' ) );
		}

		$this->settings->update_access_token( $payPalResponse );

		tribe( RefreshToken::class )->registerCronJobToRefreshToken( $payPalResponse['expires_in'] );

		wp_send_json_success( __( 'PayPal account onboarded', 'event-tickets' ) );
	}

	/**
	 * give_paypal_commerce_get_partner_url action handler
	 *
	 * @since 5.1.6
	 */
	public function onGetPartnerUrlAjaxRequestHandler() {
		$this->validateAdminRequest();

		$country_code = tribe_get_request_var( 'countryCode' );

		/** @var \Tribe__Languages__Locations $locations */
		$locations = tribe( 'languages.locations' );
		$countries = $locations->get_countries();

		// Check for a valid country.
		if ( empty( $country_code ) || ! isset( $countries[ $country_code ] ) ) {
			wp_send_json_error( __( 'Must include valid 2-character country code', 'event-tickets' ) );
		}

		/** @var Tribe__Settings $settings */
		$settings = tribe( 'settings' );

		// Get link to Tickets Tab.
		$settings_url = $settings->get_url( [
            'page'                       => 'tribe-common',
            'tab'                        => 'event-tickets',
            'tickets-commerce-connected' => '1',
		] );

		// @todo They ultimately need to get here.
		// . '#tribe-field-tickets-commerce-paypal-commerce';

		$partner_link_details = $this->payPalAuth->getSellerPartnerLink(
			// @todo Replace this URL.
			$settings_url,
			$country_code
		);

		if ( ! $partner_link_details ) {
			wp_send_json_error( __( 'Partner details not found', 'event-tickets' ) );
		}

		$this->settings->update_account_country( $country_code );
		$this->settings->update_partner_link_details( $partner_link_details );

		wp_send_json_success( $partner_link_details );
	}

	/**
	 * give_paypal_commerce_disconnect_account ajax request handler.
	 *
	 * @since 5.1.6
	 */
	public function removePayPalAccount() {
		$this->validateAdminRequest();

		// Remove the webhook from PayPal if there is one
		if ( $webhookConfig = $this->webhooksRepository->getWebhookConfig() ) {
			$this->webhooksRepository->deleteWebhook( $this->merchantDetails->accessToken, $webhookConfig->id );
			$this->webhooksRepository->deleteWebhookConfig();
		}

		$this->merchantRepository->delete();
		$this->merchantRepository->deleteAccountErrors();
		$this->merchantRepository->deleteClientToken();
		$this->refreshToken->deleteRefreshTokenCronJob();

		wp_send_json_success( __( 'PayPal account disconnected', 'event-tickets' ) );
	}

	/**
	 * Create order.
	 *
	 * @since 5.1.6
	 * @todo : handle payment create error on frontend.
	 *
	 */
	public function createOrder() {
		// @todo Set up the order with our own custom code.

		$this->validateFrontendRequest();

		$postData = give_clean( $_POST );
		$formId   = absint( tribe_get_request_var( 'give-form-id' ) );

		$data = [
			'formId'              => $formId,
			'formTitle'           => give_payment_gateway_item_title( [ 'post_data' => $postData ], 127 ),
			'paymentAmount'      => isset( $postData['give-amount'] ) ? (float) apply_filters( 'give_payment_total', give_maybe_sanitize_amount( $postData['give-amount'], [ 'currency' => give_get_currency( $formId ) ] ) ) : '0.00',
			'payer'               => [
				'firstName' => $postData['give_first'],
				'lastName'  => $postData['give_last'],
				'email'     => $postData['give_email'],
			],
			'application_context' => [
				'shipping_preference' => 'NO_SHIPPING',
			],
		];

		try {
			$result = tribe( PayPalOrder::class )->createOrder( $data );

			wp_send_json_success(
				[
					'id' => $result,
				]
			);
		} catch ( \Exception $ex ) {
			wp_send_json_error(
				[
					'error' => json_decode( $ex->getMessage(), true ),
				]
			);
		}
	}

	/**
	 * Approve order.
	 *
	 * @since 5.1.6
	 * @todo  : handle payment capture error on frontend.
	 *
	 */
	public function approveOrder() {
		$this->validateFrontendRequest();

		$orderId = absint( tribe_get_request_var( 'order' ) );

		// @todo Handle our own order approval process.

		try {
			$result = tribe( PayPalOrder::class )->approveOrder( $orderId );

			wp_send_json_success(
				[
					'order' => $result,
				]
			);
		} catch ( \Exception $ex ) {
			wp_send_json_error(
				[
					'error' => json_decode( $ex->getMessage(), true ),
				]
			);
		}
	}

	/**
	 * Return on boarding trouble notice.
	 *
	 * @since 5.1.6
	 */
	public function onBoardingTroubleNotice() {
		$this->validateAdminRequest();

		$actionList = sprintf(
			'<ol><li>%1$s</li><li>%2$s</li><li>%3$s %4$s</li></ol>',
			esc_html__( 'Make sure to complete the entire PayPal process. Do not close the window you have finished the process.', 'event-tickets' ),
			esc_html__( 'The last screen of the PayPal connect process includes a button to be sent back to your site. It is important you click this and do not close the window yourself.', 'event-tickets' ),
			esc_html__( 'If you’re still having problems connecting:', 'event-tickets' ),
			$this->settings->get_guidance_html()
		);

		$standardError = sprintf(
			'<div id="give-paypal-onboarding-trouble-notice" class="tribe-common-a11y-hidden"><p class="error-message">%1$s</p><p>%2$s</p></div>',
			esc_html__( 'Having trouble connecting to PayPal?', 'event-tickets' ),
			$actionList
		);

		wp_send_json_success( $standardError );
	}

	/**
	 * Validate admin ajax request.
	 *
	 * @since 5.1.6
	 */
	private function validateAdminRequest() {
		// @todo Add our own capacity check.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Access not allowed', 'event-tickets' ), 401 );
		}
	}

	/**
	 * Validate frontend ajax request.
	 *
	 * @since 5.1.6
	 */
	private function validateFrontendRequest() {
		$formId = absint( $_POST['give-form-id'] );

		if ( ! $formId || ! give_verify_payment_form_nonce( give_clean( $_POST['give-form-hash'] ), $formId ) ) {
			wp_die();
		}
	}
}
