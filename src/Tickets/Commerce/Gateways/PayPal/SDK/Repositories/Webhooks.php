<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories;

use Exception;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\DataTransferObjects\PayPalWebhookHeaders;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Models\WebhookConfig;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\PayPalClient;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\Traits\HasMode;
use TEC\Tickets\Commerce\Gateways\PayPal\Settings;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\WebhookRegister;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\WebhooksRoute;

class Webhooks {

	use HasMode;

	/**
	 * @since 5.1.6
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
	 * @var Settings
	 */
	private $settings;

	/**
	 * Webhooks constructor.
	 *
	 * @since 5.1.6
	 *
	 * @param PayPalClient    $payPalClient
	 * @param WebhookRegister $webhooksRegister
	 * @param Settings        $settings
	 */
	public function __construct( PayPalClient $payPalClient, WebhookRegister $webhooksRegister, Settings $settings ) {
		$this->payPalClient     = $payPalClient;
		$this->webhooksRegister = $webhooksRegister;
		$this->settings         = $settings;
	}

	/**
	 * Handle initial setup for the object singleton.
	 *
	 * @since 5.1.6
	 */
	public function init() {
		$this->setMode( tribe_tickets_commerce_is_test_mode() ? 'sandbox' : 'live' );
	}

	/**
	 * Verifies with PayPal that the given event is securely from PayPal and not some sneaking sneaker
	 *
	 * @see   https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature
	 * @since 5.1.6
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

		$request = wp_remote_post(
			$apiUrl,
			[
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => "Bearer {$token}",
				],
				'body'    => wp_json_encode(
					[
						'transmission_id'   => $payPalHeaders->transmissionId,
						'transmission_time' => $payPalHeaders->transmissionTime,
						'transmission_sig'  => $payPalHeaders->transmissionSig,
						'cert_url'          => $payPalHeaders->certUrl,
						'auth_algo'         => $payPalHeaders->authAlgo,
						'webhook_id'        => $webhookConfig->id,
						'webhook_event'     => $event,
					]
				),
			]
		);

		if ( is_wp_error( $request ) ) {
			tribe( 'logger' )->log_error( sprintf(
				// Translators: %s: The error message.
				__( 'PayPal request error: %s', 'event-tickets' ),
				$request->get_error_message()
			), 'tickets-commerce-paypal-commerce' );

			return false;
		}

		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response );

		if ( ! $response || ! isset( $response->verification_status ) ) {
			tribe( 'logger' )->log_error( __( 'Unexpected PayPal response when verifying signature', 'event-tickets' ), 'tickets-commerce-paypal-commerce' );

			return false;
		}

		return $response->verification_status === 'SUCCESS';

	}

	/**
	 * Get the list of webhooks.
	 *
	 * @see   https://developer.paypal.com/docs/api/webhooks/v1/#webhooks_list
	 * @since 5.1.6
	 *
	 * @param string $token The PayPal auth token.
	 *
	 * @throws Exception
	 *
	 * @return object[] The list of PayPal webhooks.
	 */
	public function listWebhooks( $token ) {
		// @todo Move this to the SDK.
		$apiUrl = $this->payPalClient->getApiUrl( 'v1/notifications/webhooks' );

		$request = wp_remote_get(
			$apiUrl,
			[
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => "Bearer {$token}",
				],
			]
		);

		if ( is_wp_error( $request ) ) {
			tribe( 'logger' )->log_error( sprintf(
				// Translators: %s: The error message.
				__( 'PayPal request error: %s', 'event-tickets' ),
				$request->get_error_message()
			), 'tickets-commerce-paypal-commerce' );

			return false;
		}

		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response );

		if ( ! $response || empty( $response->webhooks ) ) {
			tribe( 'logger' )->log_error( __( 'Unexpected PayPal response when getting list of webhooks', 'event-tickets' ), 'tickets-commerce-paypal-commerce' );

			throw new Exception( 'Failed to get list of webhooks' );
		}

		return $response->webhooks;
	}

	/**
	 * Get the webhook data from a specific webhook ID.
	 *
	 * @see   https://developer.paypal.com/docs/api/webhooks/v1/#webhooks_get
	 * @since 5.1.6
	 *
	 * @param string $token     The PayPal auth token.
	 * @param string $webhookId The webhook ID.
	 *
	 * @throws Exception
	 *
	 * @return object The PayPal webhook data.
	 */
	public function getWebhook( $token, $webhookId ) {
		// @todo Move this to the SDK.
		$apiUrl = $this->payPalClient->getApiUrl( "v1/notifications/webhooks/{$webhookId}" );

		$request = wp_remote_get(
			$apiUrl,
			[
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => "Bearer {$token}",
				],
			]
		);

		if ( is_wp_error( $request ) ) {
			tribe( 'logger' )->log_error( sprintf(
				// Translators: %s: The error message.
				__( 'PayPal request error: %s', 'event-tickets' ),
				$request->get_error_message()
			), 'tickets-commerce-paypal-commerce' );

			return false;
		}

		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response );

		if ( ! $response || empty( $response->id ) ) {
			if ( 'INVALID_RESOURCE_ID' === $response->name ) {
				// The webhook was not found.
				tribe( 'logger' )->log_warning( __( 'The PayPal webhook does not exist', 'event-tickets' ), 'tickets-commerce-paypal-commerce' );
			} else {
				// Other unexpected response.
				tribe( 'logger' )->log_warning( __( 'Unexpected PayPal response when getting webhook', 'event-tickets' ), 'tickets-commerce-paypal-commerce' );
			}

			throw new Exception( 'Failed to get webhook' );
		}

		return $response;
	}

	/**
	 * Creates a webhook with the given event types registered.
	 *
	 * @see   https://developer.paypal.com/docs/api/webhooks/v1/#webhooks_post
	 * @since 5.1.6
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
		$webhookUrl = tribe( WebhooksRoute::class )->getRouteUrl();

		$request = wp_remote_post(
			$apiUrl,
			[
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => "Bearer {$token}",
				],
				'body'    => json_encode(
					[
						'url'         => $webhookUrl,
						'event_types' => array_map(
							static function ( $eventType ) {
								return [
									'name' => $eventType,
								];
							},
							$events
						),
					]
				),
			]
		);

		if ( is_wp_error( $request ) ) {
			tribe( 'logger' )->log_error( sprintf(
				// Translators: %s: The error message.
				__( 'PayPal request error: %s', 'event-tickets' ),
				$request->get_error_message()
			), 'tickets-commerce-paypal-commerce' );

			return false;
		}

		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response );

		if ( ! $response || empty( $response->id ) ) {
			if ( ! empty( $response->name ) ) {
				if ( 'WEBHOOK_URL_ALREADY_EXISTS' === $response->name ) {
					// The webhook already exists, this is fine!
					$webhooks = $this->listWebhooks( $token );

					if ( $webhooks ) {
						$webhooks = wp_list_pluck( $webhooks, 'id', 'url' );

						if ( isset( $webhooks[ $webhookUrl ] ) ) {
							return new WebhookConfig( $webhooks[ $webhookUrl ], $webhookUrl, $events );
						}
					}
				} elseif ( 'WEBHOOK_NUMBER_LIMIT_EXCEEDED' === $response->name ) {
					// Limit has been reached, we cannot just delete all webhooks without permission.
					tribe( 'logger' )->log_error( __( 'PayPal webhook limit has been reached, you need to go into your developer.paypal.com account and remove webhooks from the associated account', 'event-tickets' ), 'tickets-commerce-paypal-commerce' );
				}
			}

			tribe( 'logger' )->log_error( __( 'Unexpected PayPal response when creating webhook', 'event-tickets' ), 'tickets-commerce-paypal-commerce' );

			throw new Exception( 'Failed to create webhook' );
		}

		return new WebhookConfig( $response->id, $webhookUrl, $events );
	}

	/**
	 * Updates the webhook url and events
	 *
	 * @since 5.1.6
	 *
	 * @param string $token
	 * @param string $webhookId
	 *
	 * @throws Exception
	 *
	 * @return bool
	 */
	public function updateWebhook( $token, $webhookId ) {
		// @todo Move this to the SDK.
		$apiUrl = $this->payPalClient->getApiUrl( "v1/notifications/webhooks/{$webhookId}" );

		$events     = $this->webhooksRegister->getRegisteredEvents();
		$webhookUrl = tribe( WebhooksRoute::class )->getRouteUrl();

		$request = wp_remote_request(
			$apiUrl,
			[
				'method'  => 'PATCH',
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => "Bearer {$token}",
				],
				'body'    => json_encode(
					[
						[
							'op'    => 'replace',
							'path'  => '/url',
							'value' => $webhookUrl,
						],
						[
							'op'    => 'replace',
							'path'  => '/event_types',
							'value' => array_map(
								static function ( $eventType ) {
									return [
										'name' => $eventType,
									];
								},
								$events
							),
						],
					]
				),
			]
		);

		if ( is_wp_error( $request ) ) {
			tribe( 'logger' )->log_error( sprintf(
				// Translators: %s: The error message.
				__( 'PayPal request error: %s', 'event-tickets' ),
				$request->get_error_message()
			), 'tickets-commerce-paypal-commerce' );

			return false;
		}

		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response );

		if ( ! $response || empty( $response->id ) ) {
			if ( ! empty( $response->name ) ) {
				if ( 'INVALID_RESOURCE_ID' === $response->name ) {
					// The webhook was not found, let's create it.
					$webhookConfig = $this->createWebhook( $token );

					tribe( 'logger' )->log_warning( __( 'The PayPal webhook was not able to be updated because it did not exist, attempting to create it now', 'event-tickets' ), 'tickets-commerce-paypal-commerce' );

					if ( $webhookConfig ) {
						return true;
					}
				}
			}

			tribe( 'logger' )->log_error( __( 'Unexpected PayPal response when updating webhook', 'event-tickets' ), 'tickets-commerce-paypal-commerce' );

			throw new Exception( 'Failed to update PayPal Commerce webhook' );
		}

		return true;
	}

	/**
	 * Deletes the webhook with the given id.
	 *
	 * @since 5.1.6
	 *
	 * @param string $token
	 * @param string $webhookId
	 *
	 * @return bool Whether or not the deletion was successful
	 */
	public function deleteWebhook( $token, $webhookId ) {
		// @todo Move this to the SDK.
		$apiUrl = $this->payPalClient->getApiUrl( "v1/notifications/webhooks/{$webhookId}" );

		$request = wp_remote_request(
			$apiUrl,
			[
				'method'  => 'DELETE',
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => "Bearer {$token}",
				],
			]
		);

		$code = wp_remote_retrieve_response_code( $request );

		return 200 <= $code && $code < 300;
	}

	/**
	 * Saves the webhook config in the database
	 *
	 * @since 5.1.6
	 *
	 * @param WebhookConfig $config
	 */
	public function saveWebhookConfig( WebhookConfig $config ) {
		$this->settings->update_webhook_config( $this->mode, $config );
	}

	/**
	 * Retrieves the WebhookConfig from the database
	 *
	 * @since 5.1.6
	 *
	 * @return WebhookConfig|null
	 */
	public function getWebhookConfig() {
		return $this->settings->get_webhook_config( $this->mode );
	}

	/**
	 * Deletes the stored webhook config
	 *
	 * @since 5.1.6
	 */
	public function deleteWebhookConfig() {
		$this->settings->delete_webhook_config( $this->mode );
	}
}
