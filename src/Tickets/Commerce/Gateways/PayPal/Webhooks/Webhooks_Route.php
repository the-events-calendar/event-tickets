<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks;

use Exception;
use TEC\Tickets\Commerce\Gateways\PayPal\REST;
use TEC\Tickets\Commerce\Gateways\PayPal\Merchant;
use TEC\Tickets\Commerce\Gateways\PayPal\Repositories\Webhooks;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Headers;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Webhook_Register;

class Webhooks_Route {
	/**
	 * @since 5.1.6
	 *
	 * @var Merchant
	 */
	private $merchant;

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
	 * Webhooks_Route constructor.
	 *
	 * @since 5.1.6
	 *
	 * @param Merchant $merchant
	 * @param Webhook_Register $register
	 * @param Webhooks         $webhook_repository
	 */
	public function __construct( Merchant $merchant, Webhook_Register $register, Webhooks $webhook_repository ) {
		$this->merchant           = $merchant;
		$this->webhook_register   = $register;
		$this->webhook_repository = $webhook_repository;
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
		$endpoint = tribe( REST\Webhook::class );

		return rest_url( '/' . $rest->namespace . $endpoint->get_endpoint_path(), 'https' );
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
		if ( ! $this->merchant->account_is_connected() ) {
			return false;
		}

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

		if ( ! $this->webhook_repository->verify_event_signature( $this->merchant->get_access_token(), $event, $paypal_headers ) ) {
			tribe( 'logger' )->log_error( __( 'Failed PayPal webhook event verification', 'event-tickets' ), 'tickets-commerce-paypal-commerce' );

			throw new Exception( 'Failed event verification' );
		}

		try {
			return $this->webhook_register
				->get_event_handler( $event->event_type )
				->process_event( $event );
		} catch ( Exception $exception ) {
			$event_type = empty( $event->event_type ) ? 'Unknown' : $event->event_type;

			tribe( 'logger' )->log_error( sprintf(
			// Translators: %s: The event type.
				__( 'Error processing webhook: %s', 'event-tickets' ),
				$event_type
			), 'tickets-commerce-paypal-commerce' );

			throw $exception;
		}
	}
}
