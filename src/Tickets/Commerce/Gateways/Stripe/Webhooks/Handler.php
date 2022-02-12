<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;

use TEC\Tickets\Commerce\Gateways\Stripe\Client;
use TEC\Tickets\Commerce\Status as Commerce_Status;
use TEC\Tickets\Commerce\Order;

use Tribe__Utils__Array as Arr;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class Handler
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe\Webhooks
 */
class Handler {
	/**
	 * Process a given Stripe Webhook event, possibly updating the local order with the status sent by the request.
	 *
	 * These will be directly sent to the Rest API.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request  $request
	 * @param WP_REST_Response $response
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public static function process_webhook_response( WP_REST_Request $request, WP_REST_Response $response ) {
		$event = $request->get_json_params();
		$type = Arr::get( $event, 'type' );


		// Invalid event.
		if ( empty( $type ) || 'event' !== $event['object'] ) {
			return new WP_Error( 'tec-tickets-commerce-stripe-webhook-invalid-payload', null, [ 'event' => $event ] );
		}

		// Check if the event type matches.
		if ( ! Events::is_valid( $type ) ) {
			tribe( 'logger' )->log_debug(
				sprintf(
				// Translators: %s: The Stripe payment event.
					__( 'Invalid event type for webhook event: %s', 'event-tickets' ),
					json_encode( $event )
				),
				'tickets-commerce-gateway-stripe'
			);

			return new WP_Error( 'tec-tickets-commerce-stripe-webhook-invalid-type', null, [ 'event' => $event ] );
		}

		$new_status = tribe( Events::class )->convert_to_commerce_status( $type );

		// When it's not a status we return with the callback.
		if ( ! $new_status instanceof Commerce_Status\Status_Interface ) {
			$events_map = Events::get_events();

			return $events_map[ $type ]( $request, $response );
		}

		/**
		 * @todo @moraleida We need to modify the order here based on the Payment Intent.
		 */

		return $response;
	}

	/**
	 * Process the webhook and just return a valid WP_REST_Response.
	 *
	 * These will be directly sent to the Rest API.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request  $request
	 * @param WP_REST_Response $response
	 *
	 * @return WP_REST_Response
	 */
	public static function handle_default( WP_REST_Request $request, WP_REST_Response $response ): WP_REST_Response {
		return $response;
	}

	/**
	 * @todo We need to figure out what happens when this is the case.
	 *
	 * These will be directly sent to the Rest API.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request  $request
	 * @param WP_REST_Response $response
	 *
	 * @return WP_REST_Response
	 */
	public static function handle_account_deauthorized( WP_REST_Request $request, WP_REST_Response $response ): WP_REST_Response {
		return $response;
	}
}