<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Repositories;

use Exception;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Headers;
use TEC\Tickets\Commerce\Gateways\PayPal\Models\Webhook_Config;
use TEC\Tickets\Commerce\Gateways\PayPal\Client;
use TEC\Tickets\Commerce\Gateways\PayPal\Merchant;
use TEC\Tickets\Commerce\Gateways\PayPal\Settings;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Webhook_Register;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Webhooks_Route;

/**
 * Class Webhooks
 *
 * @since 5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Repositories
 */
class Webhooks {
	/**
	 * @since 5.1.6
	 *
	 * @var Webhooks_Route
	 */
	private $webhook_route;

	/**
	 * @var Webhook_Register
	 */
	private $webhooks_register;

	/**
	 * @var Client
	 */
	private $paypal_client;

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * Webhooks constructor.
	 *
	 * @since 5.1.6
	 *
	 * @param Client           $paypal_client
	 * @param Webhook_Register $webhooks_register
	 * @param Settings         $settings
	 */
	public function __construct( Client $paypal_client, Webhook_Register $webhooks_register, Settings $settings ) {
		$this->paypal_client     = $paypal_client;
		$this->webhooks_register = $webhooks_register;
		$this->settings          = $settings;
	}

	/**
	 * Verifies with PayPal that the given event is securely from PayPal and not some sneaking sneaker
	 *
	 * @see   https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature
	 * @since 5.1.6
	 *
	 * @param string  $token
	 * @param object  $event The event to verify
	 * @param Headers $paypal_headers
	 *
	 * @return bool
	 */
	public function verify_event_signature( $token, $event, $paypal_headers ) {
		// @todo Move this to the SDK.
		$api_url = $this->paypal_client->get_api_url( 'v1/notifications/verify-webhook-signature' );

		$webhook_config = $this->get_webhook_config();

		$request = wp_remote_post(
			$api_url,
			[
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => "Bearer {$token}",
				],
				'body'    => wp_json_encode(
					[
						'transmission_id'   => $paypal_headers->transmission_id,
						'transmission_time' => $paypal_headers->transmission_time,
						'transmission_sig'  => $paypal_headers->transmission_sig,
						'cert_url'          => $paypal_headers->cert_url,
						'auth_algo'         => $paypal_headers->auth_algo,
						'webhook_id'        => $webhook_config->id,
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
	public function list_webhooks( $token ) {
		// @todo Move this to the SDK.
		$api_url = $this->paypal_client->get_api_url( 'v1/notifications/webhooks' );

		$request = wp_remote_get(
			$api_url,
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
	 * @param string $token      The PayPal auth token.
	 * @param string $webhook_id The webhook ID.
	 *
	 * @throws Exception
	 *
	 * @return object The PayPal webhook data.
	 */
	public function get_webhook( $token, $webhook_id ) {
		// @todo Move this to the SDK.
		$api_url = $this->paypal_client->get_api_url( "v1/notifications/webhooks/{$webhook_id}" );

		$request = wp_remote_get(
			$api_url,
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
	 * @return Webhook_Config
	 * @throws Exception
	 */
	public function create_webhook( $token ) {
		// @todo Move this to the SDK.
		$apiUrl = $this->paypal_client->get_api_url( 'v1/notifications/webhooks' );

		$events      = $this->webhooks_register->get_registered_events();
		$webhook_url = tribe( Webhooks_Route::class )->get_route_url();

		$request = wp_remote_post(
			$apiUrl,
			[
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => "Bearer {$token}",
				],
				'body'    => json_encode(
					[
						'url'         => $webhook_url,
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
					$webhooks = $this->list_webhooks( $token );

					if ( $webhooks ) {
						$webhooks = wp_list_pluck( $webhooks, 'id', 'url' );

						if ( isset( $webhooks[ $webhook_url ] ) ) {
							return new Webhook_Config( $webhooks[ $webhook_url ], $webhook_url, $events );
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

		return new Webhook_Config( $response->id, $webhook_url, $events );
	}

	/**
	 * Updates the webhook url and events
	 *
	 * @since 5.1.6
	 *
	 * @throws Exception
	 *
	 * @param string $webhook_id
	 *
	 * @param string $token
	 *
	 * @return bool
	 */
	public function update_webhook( $token, $webhook_id ) {
		// @todo Move this to the SDK.
		$api_url = $this->paypal_client->get_api_url( "v1/notifications/webhooks/{$webhook_id}" );

		$events      = $this->webhooks_register->get_registered_events();
		$webhook_url = tribe( Webhooks_Route::class )->get_route_url();

		$request = wp_remote_request(
			$api_url,
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
							'value' => $webhook_url,
						],
						[
							'op'    => 'replace',
							'path'  => '/event_types',
							'value' => array_map(
								static function ( $event_type ) {
									return [
										'name' => $event_type,
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
					$webhook_config = $this->create_webhook( $token );

					tribe( 'logger' )->log_warning( __( 'The PayPal webhook was not able to be updated because it did not exist, attempting to create it now', 'event-tickets' ), 'tickets-commerce-paypal-commerce' );

					if ( $webhook_config ) {
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
	 * @param string $webhook_id
	 *
	 * @return bool Whether or not the deletion was successful
	 */
	public function delete_webhook( $token, $webhook_id ) {
		// @todo Move this to the SDK.
		$api_url = $this->paypal_client->get_api_url( "v1/notifications/webhooks/{$webhook_id}" );

		$request = wp_remote_request(
			$api_url,
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
	 * @param Webhook_Config $config
	 */
	public function save_webhook_config( Webhook_Config $config ) {
		$this->settings->update_webhook_config( tribe( Merchant::class )->get_mode(), $config );
	}

	/**
	 * Retrieves the WebhookConfig from the database
	 *
	 * @since 5.1.6
	 *
	 * @return Webhook_Config|null
	 */
	public function get_webhook_config() {
		return $this->settings->get_webhook_config( tribe( Merchant::class )->get_mode() );
	}

	/**
	 * Deletes the stored webhook config
	 *
	 * @since 5.1.6
	 */
	public function delete_webhook_config() {
		$this->settings->delete_webhook_config( tribe( Merchant::class )->get_mode() );
	}
}
