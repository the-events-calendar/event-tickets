<?php

namespace TEC\PaymentGateways\PayPalCommerce\Repositories;

use Exception;
use TEC\PaymentGateways\PayPalCommerce\DataTransferObjects\PayPalWebhookHeaders;
use TEC\PaymentGateways\PayPalCommerce\PayPalClient;
use TEC\PaymentGateways\PayPalCommerce\Repositories\Traits\HasMode;
use TEC\PaymentGateways\PayPalCommerce\SDK\Models\WebhookConfig;
use TEC\PaymentGateways\PayPalCommerce\Webhooks\WebhookRegister;
use TEC\Route\PayPalWebhooks as WebhooksRoute;

class Webhooks {

	use HasMode;

	/**
	 * @since TBD
	 *
	 * @var WebhooksRoute
	 */
	private $webhookRoute;

	/**
	 * @var WebhookRegister
	 */
	private $webhooksRegister;

	/**
	 * @var PayPalClient
	 */
	private $payPalClient;

	/**
	 * Webhooks constructor.
	 *
	 * @since TBD
	 *
	 * @param WebhooksRoute   $webhookRoute
	 * @param PayPalClient    $payPalClient
	 * @param WebhookRegister $webhooksRegister
	 */
	public function __construct( WebhooksRoute $webhookRoute, PayPalClient $payPalClient, WebhookRegister $webhooksRegister ) {
		$this->webhookRoute     = $webhookRoute;
		$this->payPalClient     = $payPalClient;
		$this->webhooksRegister = $webhooksRegister;
	}

	/**
	 * Verifies with PayPal that the given event is securely from PayPal and not some sneaking sneaker
	 *
	 * @see   https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature
	 * @since TBD
	 *
	 * @param string               $token
	 * @param object               $event The event to verify
	 * @param PayPalWebhookHeaders $payPalHeaders
	 *
	 * @return bool
	 */
	public function verifyEventSignature( $token, $event, $payPalHeaders ) {
		// @todo Move this to the SDK.
		$apiUrl = $this->payPalClient->getApiUrl( 'v1/notifications/verify-webhook-signature' );

		$webhookConfig = $this->getWebhookConfig();

		$response = wp_remote_post( $apiUrl, [
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => "Bearer $token",
			],
			'body'    => wp_json_encode( [
				'transmission_id'   => $payPalHeaders->transmissionId,
				'transmission_time' => $payPalHeaders->transmissionTime,
				'transmission_sig'  => $payPalHeaders->transmissionSig,
				'cert_url'          => $payPalHeaders->certUrl,
				'auth_algo'         => $payPalHeaders->authAlgo,
				'webhook_id'        => $webhookConfig->id,
				'webhook_event'     => $event,
			] ),
		] );

		if ( is_wp_error( $response ) ) {
			give_record_gateway_error( 'Webhook signature failure response', print_r( $response, true ) );

			return false;
		}

		$response = json_decode( $response['body'], false );

		return $response->verification_status === 'SUCCESS';
	}

	/**
	 * Creates a webhook with the given event types registered.
	 *
	 * @see   https://developer.paypal.com/docs/api/webhooks/v1/#webhooks_post
	 * @since TBD
	 *
	 * @param string $token
	 *
	 * @return WebhookConfig
	 * @throws Exception
	 */
	public function createWebhook( $token ) {
		// @todo Move this to the SDK.
		$apiUrl = $this->payPalClient->getApiUrl( 'v1/notifications/webhooks' );

		$events     = $this->webhooksRegister->getRegisteredEvents();
		$webhookUrl = $this->webhookRoute->getRouteUrl();

		esc_html_e();

		$response = wp_remote_post( $apiUrl, [
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => "Bearer $token",
			],
			'body'    => json_encode( [
				'url'         => $webhookUrl,
				'event_types' => array_map( static function ( $eventType ) {
					return [
						'name' => $eventType,
					];
				}, $events ),
			] ),
		] );

		$response = json_decode( $response['body'], false );

		if ( ! isset( $response->id ) ) {
			give_record_gateway_error( 'Create PayPal Commerce Webhook Failure', print_r( $response, true ) );

			throw new Exception( 'Failed to create webhook' );
		}

		return new WebhookConfig( $response->id, $webhookUrl, $events );
	}

	/**
	 * Updates the webhook url and events
	 *
	 * @since TBD
	 *
	 * @param string $token
	 * @param string $webhookId
	 *
	 * @throws Exception
	 */
	public function updateWebhook( $token, $webhookId ) {
		// @todo Move this to the SDK.
		$apiUrl = $this->payPalClient->getApiUrl( "v1/notifications/webhooks/$webhookId" );

		$webhookUrl = $this->webhookRoute->getRouteUrl();

		$response = wp_remote_request( $apiUrl, [
			'method'  => 'PATCH',
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => "Bearer $token",
			],
			'body'    => json_encode( [
				[
					'op'    => 'replace',
					'path'  => '/url',
					'value' => $webhookUrl,
				],
				[
					'op'    => 'replace',
					'path'  => '/event_types',
					'value' => array_map( static function ( $eventType ) {
						return [
							'name' => $eventType,
						];
					}, $this->webhooksRegister->getRegisteredEvents() ),
				],
			] ),
		] );

		$response = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $response ) || ! isset( $response['id'] ) ) {
			give_record_gateway_error( 'Failed to update PayPal Commerce webhook', print_r( $response, true ) );

			throw new Exception( 'Failed to update PayPal Commerce webhook' );
		}
	}

	/**
	 * Deletes the webhook with the given id.
	 *
	 * @since TBD
	 *
	 * @param string $token
	 * @param string $webhookId
	 *
	 * @return bool Whether or not the deletion was successful
	 */
	public function deleteWebhook( $token, $webhookId ) {
		// @todo Move this to the SDK.
		$apiUrl = $this->payPalClient->getApiUrl( "v1/notifications/webhooks/$webhookId" );

		$response = wp_remote_request( $apiUrl, [
			'method'  => 'DELETE',
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => "Bearer {$token}",
			],
		] );

		$code = wp_remote_retrieve_response_code( $response );

		return $code >= 200 && $code < 300;
	}

	/**
	 * Saves the webhook config in the database
	 *
	 * @since TBD
	 *
	 * @param WebhookConfig $config
	 */
	public function saveWebhookConfig( WebhookConfig $config ) {
		// @todo Move this to the SDK Interface.
		update_option( $this->getOptionKey(), $config->toArray(), false );
	}

	/**
	 * Retrieves the WebhookConfig from the database
	 *
	 * @since TBD
	 *
	 * @return WebhookConfig|null
	 */
	public function getWebhookConfig() {
		// @todo Move this to the SDK Interface.
		$data = get_option( $this->getOptionKey(), null );

		return $data ? WebhookConfig::fromArray( $data ) : null;
	}

	/**
	 * Deletes the stored webhook config
	 *
	 * @since TBD
	 */
	public function deleteWebhookConfig() {
		// @todo Move this to the SDK Interface.
		delete_option( $this->getOptionKey() );
	}

	/**
	 * Returns the option key for the given mode
	 *
	 * @return string
	 */
	private function getOptionKey() {
		// @todo Move this to the SDK Interface.
		return "give_paypal_commerce_{$this->mode}_webhook_config";
	}
}
