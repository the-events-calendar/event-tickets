<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks;

use Exception;
use TEC\Tickets\Commerce\Gateways\PayPal\REST;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\Merchant_Details;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\Webhooks;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Webhooks\Headers;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Webhook_Register;
use Tribe\Tickets\REST\V1\Endpoints\Commerce\PayPal_Webhook;

class Webhooks_Route {
	/**
	 * @since 5.1.6
	 *
	 * @var Merchant_Details
	 */
	private $merchantRepository;

	/**
	 * @since 5.1.6
	 *
	 * @var Webhooks
	 */
	private $webhookRepository;

	/**
	 * @since 5.1.6
	 *
	 * @var Webhook_Register
	 */
	private $webhookRegister;

	/**
	 * WebhooksRoute constructor.
	 *
	 * @since 5.1.6
	 *
	 * @param Merchant_Details $merchantRepository
	 * @param Webhook_Register $register
	 * @param Webhooks         $webhookRepository
	 */
	public function __construct( Merchant_Details $merchantRepository, Webhook_Register $register, Webhooks $webhookRepository ) {
		$this->merchantRepository = $merchantRepository;
		$this->webhookRegister    = $register;
		$this->webhookRepository  = $webhookRepository;
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
		$rest = tribe( REST::class );

		/** @var Webhook $endpoint */
		$endpoint = tribe( PayPal_Webhook::class );

		return rest_url( '/' . $rest->namespace . $endpoint->path, 'https' );
	}

	/**
	 * Handles all webhook event requests. First it verifies that authenticity of the event with
	 * PayPal, and then it passes the event along to the appropriate listener to finish.
	 *
	 * @since 5.1.6
	 *
	 * @param string|object $event   The PayPal payment event object.
	 * @param array         $headers The list of HTTP headers for the request.
	 *
	 * @return bool Whether the event was processed.
	 *
	 * @throws Exception
	 */
	public function handle( $event, $headers = [] ) {
		if ( ! $this->merchantRepository->account_is_connected() ) {
			return false;
		}

		$merchantDetails = $this->merchantRepository->get_details();

		// Try to decode the event.
		if ( ! is_object( $event ) ) {
			$event = @json_decode( $event );

			// The event is not valid.
			if ( ! $event ) {
				return false;
			}
		}

		// If we receive an event that we're not expecting, just ignore it
		if ( ! $this->webhookRegister->hasEventRegistered( $event->event_type ) ) {
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

		$payPalHeaders = Headers::from_headers( $headers );

		if ( ! $this->webhookRepository->verify_event_signature( $merchantDetails->access_token, $event, $payPalHeaders ) ) {
			tribe( 'logger' )->log_error( __( 'Failed PayPal webhook event verification', 'event-tickets' ), 'tickets-commerce-paypal-commerce' );

			throw new Exception( 'Failed event verification' );
		}

		try {
			return $this->webhookRegister
				->getEventHandler( $event->event_type )
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
