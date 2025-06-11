<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;

use TEC\Tickets\Commerce\Status as Commerce_Status;
use TEC\Tickets\Commerce\Order;

use Tribe__Utils__Array as Arr;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;

/**
 * Class Handler
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe\Webhooks
 */
class Handler {

	/**
	 * Process a given Stripe Webhook event, possibly updating the local order with the status sent by the request.
	 *
	 * These will be directly sent to the Rest API.
	 *
	 * @since 5.3.0
	 *
	 * @param WP_REST_Request  $request
	 * @param WP_REST_Response $response
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public static function process_webhook_response( WP_REST_Request $request, WP_REST_Response $response ) {
		$event  = $request->get_json_params();
		$object = Arr::get( $event, 'object' );
		$type   = Arr::get( $event, 'type' );
		$id     = Arr::get( $event, 'id' );

		// Invalid event.
		if ( empty( $type ) || 'event' !== $object ) {
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

			$response->set_status( 200 );
			$response->set_data(
				sprintf(
					// Translators: %1$s is the event id and %2$s is the event type name.
					__( 'Event %1$s was received but events of type %2$s are not currently handled.', 'event-tickets' ),
					esc_html( $id ),
					esc_html( $type )
				)
			);

			return $response;
		}

		$new_status = tribe( Events::class )->convert_to_commerce_status( $type );

		// When it's not a status we return with the callback.
		if ( ! $new_status instanceof Commerce_Status\Status_Interface ) {
			$handlers_map = Events::get_event_handlers();

			return $handlers_map[ $type ]( $request, $response );
		}

		// Define where this request should be processed and call that method.
		$event_handler = static::get_handler_method_for_event( $type );

		// Stripe webhooks don't care for anything other than our response codes
		// 200 we're good. Anything else we're not.
		if ( is_wp_error( $event_handler ) ) {
			return $event_handler;
		}

		return call_user_func_array( $event_handler, [ $event, $new_status, $request, $response ] );
	}

	/**
	 * Get the class and method to call to handle this event.
	 *
	 * @since 5.3.0
	 *
	 * @param string $type The event type from Stripe.
	 *
	 * @return string|WP_REST_Response
	 */
	public static function get_handler_method_for_event( $type ) {
		$handlers = Events::get_event_handlers();

		if ( ! isset( $handlers[ $type ] ) ) {
			return new WP_REST_Response(
				sprintf(
					// Translators: %1$s is the event type name.
					__( 'Event was received but events of type %1$s are not currently handled.', 'event-tickets' ),
					esc_html( $type )
				),
				200
			);
		}

		return $handlers[ $type ];
	}


	/**
	 * Generic handler to update order statuses to a defined Status.
	 *
	 * @since 5.3.0
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
		if ( tribe( Order::class )->has_on_checkout_screen_hold( $order->ID ) ) {

			tribe( Webhooks::class )->add_pending_webhook( $order->ID, $status->get_wp_slug(), $order->post_status, $metadata );

			/**
			 * We can't return WP_Error because that will make Stripe think that
			 * we failed to process the Webhook and as a result will resend it.
			 *
			 * Returning bool is the best option here. False since we didn't update, but we will!
			 */
			return false;
		}

		return tribe( Order::class )->modify_status( $order->ID, $status->get_slug(), $metadata );
	}
}
