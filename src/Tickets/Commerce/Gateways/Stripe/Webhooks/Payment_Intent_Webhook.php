<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;

use TEC\Tickets\Commerce\Gateways\Contracts\Webhook_Event_Interface;
use TEC\Tickets\Commerce\Gateways\Stripe\Payment_Intent;
use TEC\Tickets\Commerce\Gateways\Stripe\Status;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Action_Required;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Status_Interface;
use Tribe__Utils__Array as Arr;

/**
 * Webhook for Payment_Intent operations
 *
 * @since 5.3.0
 * @since 5.14.0 Remove check if payment intent should be updated.
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe\Webhooks
 */
class Payment_Intent_Webhook implements Webhook_Event_Interface {

	/**
	 * @inheritDoc
	 */
	public static function handle( array $event, Status_Interface $new_status, \WP_REST_Request $request, \WP_REST_Response $response ) {
		$payment_intent    = static::get_payment_intent_data( $event );
		$payment_intent_id = $payment_intent['id'];

		if ( ! empty( $payment_intent['metadata']['order_id'] ) ) {
			$order = tec_tc_get_order( $payment_intent['metadata']['order_id'] );
		}

		if ( empty( $order ) ) {
			$order = tribe( Order::class )->get_from_gateway_order_id( $payment_intent_id );
		}

		if ( empty( $order ) ) {

			if ( empty( $payment_intent['metadata'][ Payment_Intent::$tc_metadata_identifier ] ) ) {
				$response->set_status( 200 );
				$response->set_data( sprintf(
				// Translators: %1$s is the event id and %2$s is the event type name.
					__( 'Event %1$s was received and will not be handled because the Payment Intent %2$s does not refer to an Event Tickets transaction.', 'event-tickets' ),
					esc_html( Arr::get( $event, 'id' ) ),
					esc_html( $payment_intent_id )
				) );

				return $response;
			}

			if ( ! empty( $payment_intent['metadata'][ Payment_Intent::$test_metadata_key ] ) ) {
				$response->set_status( 200 );
				$response->set_data(
					__( 'Payment Intent Test Successful', 'event-tickets' )
				);

				return $response;
			}

			return new \WP_Error( 400, sprintf(
				// Translators: %s is the payment intent id.
				__( 'Payment Intent %s does not correspond to a known order.', 'event-tickets' ),
				esc_html( $payment_intent_id )
			) );
		}

		$meta = [
			'gateway_payload'  => $payment_intent,
			'gateway_order_id' => $payment_intent_id,
		];

		return Handler::update_order_status( $order, $new_status, $meta );
	}


	/**
	 * Get the payment intent id from the webhook event data.
	 *
	 * @since 5.3.0
	 *
	 * @param array $event Event data coming from the Webhook.
	 *
	 * @return string
	 */
	protected static function get_payment_intent_id( array $event ): string {
		$payment_intent = static::get_payment_intent_data( $event );

		return $payment_intent['id'];
	}

	/**
	 * Get the payment intent object array from the webhook event data.
	 *
	 * @since 5.3.0
	 *
	 * @param array $event Event data coming from the Webhook.
	 *
	 * @return string
	 */
	protected static function get_payment_intent_data( array $event ): array {
		return $event['data']['object'];
	}

	/**
	 * Checks if the payment intent contained in the event received has already been processed.
	 *
	 * @since 5.3.0
	 * @since 5.16.0   Remove deprecation notice.
	 * @since 5.18.0   Only check matching payment intent ids if they are not pending or action required.
	 * @since 5.18.0.1 Removed the check for the payment intent status for pending or action required.
	 *
	 * @deprecated 5.18.1
	 *
	 * @param array   $payment_intent_received The payment intent data received.
	 * @param array[] $payment_intents_stored  The payment intent data stored from each update, keyed by status.
	 *
	 * @return bool
	 */
	public static function should_payment_intent_be_updated( $payment_intent_received, $payment_intents_stored ) {
		_deprecated_function( __METHOD__, '5.18.1' );
		// This payment intent was reset, or processing has re-started without invalidating.
		if ( 1 < count( $payment_intents_stored ) && $payment_intent_received['status'] === Status::REQUIRES_PAYMENT_METHOD ) {
			return true;
		}

		foreach ( $payment_intents_stored as $status => $intents ) {
			// Skip if the status is pending or action required.
			if ( in_array( $status, [ Pending::SLUG, Action_Required::SLUG ], true ) ) {
				continue;
			}

			foreach ( $intents as $intent ) {
				// This payment intent has already been processed and updated.
				if ( $payment_intent_received['id'] === $intent['id'] ) {
					return false;
				}
			}
		}

		return true;
	}
}
