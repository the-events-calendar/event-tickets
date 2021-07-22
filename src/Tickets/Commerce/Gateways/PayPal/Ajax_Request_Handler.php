<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use TEC\Tickets\Commerce\Gateways\PayPal\Repositories\Authorization;
use TEC\Tickets\Commerce\Gateways\PayPal\Repositories\_Order;
use TEC\Tickets\Commerce\Gateways\PayPal\Repositories\Webhooks;

// @todo Bring this over.

/**
 * Class Ajax_Request_Handler
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 *
 */
class Ajax_Request_Handler {

	/**
	 * @since 5.1.6
	 *
	 * @var Webhooks
	 */
	private $webhooks_repository;

	/**
	 * @since 5.1.6
	 *
	 * @var Merchant
	 */
	private $merchant;

	/**
	 * @since 5.1.6
	 *
	 * @var Authorization
	 */
	private $paypal_auth;

	/**
	 * @since 5.1.6
	 *
	 * @var Connect_Client
	 */
	private $refresh_token;

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
	 * @param Webhooks      $webhooks_repository
	 * @param Merchant      $merchant
	 * @param Refresh_Token $refresh_token
	 * @param Settings      $settings
	 * @param Authorization $paypal_auth
	 */
	public function __construct(
		Webhooks $webhooks_repository,
		Merchant $merchant,
		Refresh_Token $refresh_token,
		Settings $settings,
		Authorization $paypal_auth
	) {
		$this->webhooks_repository = $webhooks_repository;
		$this->merchant            = $merchant;
		$this->refresh_token       = $refresh_token;
		$this->settings            = $settings;
		$this->paypal_auth         = $paypal_auth;
	}

	/**
	 *  give_paypal_commerce_user_onboarded ajax action handler
	 *
	 * @since 5.1.6
	 */
	public function on_boarded_user_ajax_request_handler() {
		$this->validate_admin_request();

		$partnerLinkInfo = $this->settings->get_partner_link_details();

		$paypal_response = $this->paypal_auth->get_token_from_authorization_code(
			tribe_get_request_var( 'sharedId' ),
			tribe_get_request_var( 'authCode' ),
			$partnerLinkInfo['nonce']
		);

		if ( ! $paypal_response || array_key_exists( 'error', $paypal_response ) ) {
			wp_send_json_error( __( 'Unexpected response from PayPal when onboarding', 'event-tickets' ) );
		}

		$this->settings->update_access_token( $paypal_response );

		tribe( Refresh_Token::class )->register_cron_job_to_refresh_token( $paypal_response['expires_in'] );

		wp_send_json_success( __( 'PayPal account onboarded', 'event-tickets' ) );
	}

	/**
	 * give_paypal_commerce_get_partner_url action handler
	 *
	 * @since 5.1.6
	 */
	public function on_get_partner_url_ajax_request_handler() {
		$this->validate_admin_request();

		$country_code = tribe_get_request_var( 'country_code' );

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

		$partner_link_details = $this->paypal_auth->get_seller_partner_link(
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
	public function remove_paypal_account() {
		$this->validate_admin_request();

		// Remove the webhook from PayPal if there is one
		if ( $webhook_config = $this->webhooks_repository->get_webhook_config() ) {
			$this->webhooks_repository->delete_webhook( $this->merchant->get_access_token(), $webhook_config->id );
			$this->webhooks_repository->delete_webhook_config();
		}

		$this->merchant->delete_data();
		$this->merchant->delete_account_errors();
		$this->merchant->delete_access_token_data();
		$this->refresh_token->delete_refresh_token_cron_job();

		wp_send_json_success( __( 'PayPal account disconnected', 'event-tickets' ) );
	}

	/**
	 * Create order.
	 *
	 * @todo  : handle payment create error on frontend.
	 *
	 * @since 5.1.6
	 */
	public function create_order() {
		// @todo Set up the order with our own custom code.

		$this->validate_frontend_request();

		$postData = give_clean( $_POST );
		$formId   = absint( tribe_get_request_var( 'give-form-id' ) );

		$data = [
			'formId'              => $formId,
			'formTitle'           => give_payment_gateway_item_title( [ 'post_data' => $postData ], 127 ),
			'paymentAmount'       => isset( $postData['give-amount'] ) ? (float) apply_filters( 'give_payment_total', give_maybe_sanitize_amount( $postData['give-amount'], [ 'currency' => give_get_currency( $formId ) ] ) ) : '0.00',
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
			$result = tribe( _Order::class )->create_order( $data );

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
	 * @todo  : handle payment capture error on frontend.
	 *
	 * @since 5.1.6
	 */
	public function approve_order() {
		$this->validate_frontend_request();

		$orderId = absint( tribe_get_request_var( 'order' ) );

		// @todo Handle our own order approval process.

		try {
			$result = tribe( _Order::class )->approve_order( $orderId );

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
	public function on_boarding_trouble_notice() {
		$this->validate_admin_request();

		$action_list = sprintf(
			'<ol><li>%1$s</li><li>%2$s</li><li>%3$s %4$s</li></ol>',
			esc_html__( 'Make sure to complete the entire PayPal process. Do not close the window you have finished the process.', 'event-tickets' ),
			esc_html__( 'The last screen of the PayPal connect process includes a button to be sent back to your site. It is important you click this and do not close the window yourself.', 'event-tickets' ),
			esc_html__( 'If you’re still having problems connecting:', 'event-tickets' ),
			$this->settings->get_guidance_html()
		);

		$standard_error = sprintf(
			'<div id="give-paypal-onboarding-trouble-notice" class="tribe-common-a11y-hidden"><p class="error-message">%1$s</p><p>%2$s</p></div>',
			esc_html__( 'Having trouble connecting to PayPal?', 'event-tickets' ),
			$action_list
		);

		wp_send_json_success( $standard_error );
	}

	/**
	 * Validate admin ajax request.
	 *
	 * @since 5.1.6
	 */
	private function validate_admin_request() {
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
	private function validate_frontend_request() {
		$formId = absint( $_POST['give-form-id'] );

		if ( ! $formId || ! give_verify_payment_form_nonce( give_clean( $_POST['give-form-hash'] ), $formId ) ) {
			wp_die();
		}
	}
}
