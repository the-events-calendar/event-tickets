<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks;

use Exception;
use TEC\Tickets\Commerce\Gateways\PayPal\REST;
use TEC\Tickets\Commerce\Gateways\PayPal\Repositories\Merchant_Details;
use TEC\Tickets\Commerce\Gateways\PayPal\Repositories\Webhooks;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Headers;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Webhook_Register;
use Tribe\Tickets\REST\V1\Endpoints\Commerce\PayPal_Webhook;

class Webhooks_Route {
	/**
	 * @since 5.1.6
	 *
	 * @var Merchant_Details
	 */
	private $merchant_repository;

	/**
	 * @since 5.1.6
	 *
	 * @var Webhooks
	 */
	private $webhook_repository;

	/**
	 * @since 5.1.6
	 *
	 * @var Webhook_Register
	 */
	private $webhook_register;

	/**
	 * WebhooksRoute constructor.
	 *
	 * @since 5.1.6
	 *
	 * @param Merchant_Details $merchant_repository
	 * @param Webhook_Register $register
	 * @param Webhooks         $webhook_repository
	 */
	public function __construct( Merchant_Details $merchant_repository, Webhook_Register $register, Webhooks $webhook_repository ) {
		$this->merchant_repository = $merchant_repository;
		$this->webhook_register    = $register;
		$this->webhook_repository  = $webhook_repository;
	}

	/**
	 * Get the REST API route URL.
	 *
	 * @since 5.1.6
	 *
	 * @return string The REST API route URL.
	 */
	public function get_route_url() {
		/** @var REST $rest */
		$rest     = tribe( REST::class );
		$endpoint = tribe( PayPal_Webhook::class );

		return rest_url( '/' . $rest->namespace . $endpoint->path, 'https' );
	}

	/**
	 * Handles all webhook event requests. First it verifies that authenticity of the event with
	 * PayPal, and then it passes the event along to the appropriate listener to finish.
	 *
	 * @since 5.1.6
	 *
	 * @throws Exception
	 *
	 * @param array         $headers The list of HTTP headers for the request.
	 *
	 * @param string|object $event   The PayPal payment event object.
	 *
	 * @return bool Whether the event was processed.
	 *
	 */
	public function handle( $event, $headers = [] ) {
		if ( ! $this->merchant_repository->account_is_connected() ) {
			return false;
		}

		$merchantDetails = $this->merchant_repository->get_details();

		// Try to decode the event.
		if ( ! is_object( $event ) ) {
			$event = @json_decode( $event );

			// The event is not valid.
			if ( ! $event ) {
				return false;
			}
		}

		// If we receive an event that we're not expecting, just ignore it
		if ( ! $this->webhook_register->has_event_registered( $event->event_type ) ) {
			tribe( 'logger' )->log_debug(
				sprintf(
				// Translators: %s: The event type.
					__( 'PayPal webhook event type not registered or supported: %s', 'event-tickets' ),
					$event->event_type
				),
				'tickets-commerce-paypal-commerce'
			);

			return false;
		}

		tribe( 'logger' )->log_debug(
			sprintf(
			// Translators: %s: The event type.
				__( 'Received PayPal webhook event for type: %s', 'event-tickets' ),
				$event->event_type
			),
			'tickets-commerce-paypal-commerce'
		);

		$paypal_headers = Headers::from_headers( $headers );

		if ( ! $this->webhook_repository->verify_event_signature( $merchantDetails->access_token, $event, $paypal_headers ) ) {
			tribe( 'logger' )->log_error( __( 'Failed PayPal webhook event verification', 'event-tickets' ), 'tickets-commerce-paypal-commerce' );

			throw new Exception( 'Failed event verification' );
		}

		try {
			return $this->webhook_register
				->get_event_handler( $event->event_type )
				->process_event( $event );
		} catch ( Exception $exception ) {
			$eventType = empty( $event->event_type ) ? 'Unknown' : $event->event_type;

			tribe( 'logger' )->log_error( sprintf(
			// Translators: %s: The event type.
				__( 'Error processing webhook: %s', 'event-tickets' ),
				$eventType
			), 'tickets-commerce-paypal-commerce' );

			throw $exception;
		}
	}
}
