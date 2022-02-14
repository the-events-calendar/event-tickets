<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;

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
		$type  = Arr::get( $event, 'type' );

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
			$events_map = Events::get_event_transition_status();

			return $events_map[ $type ]( $request, $response );
		}

		// Define where this request should be processed and call that method
		$event_handler = static::get_handler_method_for_event( $type );

		// Stripe webhooks don't care for anything other than our response codes
		// 200 we're good. Anything else we're not.
		if ( is_wp_error( $event_handler ) ) {
			return $event_handler;
		}

		$handler_response = call_user_func_array( $event_handler, [ $event, $new_status, $request ] );

		// Stripe webhooks don't care for anything other than our response codes
		// 200 we're good. Anything else we're not.
		if ( is_wp_error( $handler_response ) ) {
			return $handler_response;
		}

		return $response;
	}

	/**
	 * Get the class and method to call to handle this event.
	 *
	 * @since TBD
	 *
	 * @param string $type The event type from Stripe.
	 *
	 * @return string
	 */
	public static function get_handler_method_for_event( $type ) {
		$handlers = Events::get_event_handlers();

		return isset( $handlers[ $type ] ) ?
			$handlers[ $type ] :
			new WP_Error( 200, sprintf( __( 'Webhook event was retrieved properly but %s is not a handled event.', 'event-tickets' ), esc_html( $type ) ) );
	}


	/**
	 * Generic handler to update order statuses to a defined Status.
	 *
	 * @since TBD
	 *
	 * @param \WP_Post                         $order    The order to update.
	 * @param Commerce_Status\Status_Interface $status   The new status to use.
	 * @param array                            $metadata Any new meta to save with the order.
	 *
	 * @throws \Tribe__Repository__Usage_Error
	 *
	 * @return bool|WP_Error|null
	 */
	public static function update_order_status( \WP_Post $order, Commerce_Status\Status_Interface $status, array $metadata = [] ) {
		return tribe( Order::class )->modify_status( $order->ID, $status->get_slug(), $metadata );
	}
}