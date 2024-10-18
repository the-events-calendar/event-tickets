<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;

use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Commerce\Gateways\Contracts\Webhook_Event_Interface;
use TEC\Tickets\Commerce\Gateways\Stripe\Payment_Intent;
use TEC\Tickets\Commerce\Gateways\Stripe\Status;
use Tribe__Utils__Array as Arr;

/**
 * Webhook for Charge operations.
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe\Webhooks
 */
class Charge_Webhook implements Webhook_Event_Interface {

	/**
	 * @inheritDoc
	 */
	public static function handle( array $event, Status_Interface $new_status, \WP_REST_Request $request, \WP_REST_Response $response ) {
		$charge_data       = static::get_charge_data( $event );
		$payment_intent_id = $charge_data['payment_intent'];

		$order = tribe( Order::class )->get_from_gateway_order_id( $payment_intent_id );

		$payment_intent = Payment_Intent::get( $payment_intent_id );

		/**
		 * We were having problems for Payment Intent and Charge happening at the exact same time, so a race condition was happening
		 * this particular piece of code prevents that problem, by making the Change Webhook look at the Payment Intent status on
		 * Stripe it forces the Payment Intent to be the source of truth.
		 *
		 * See the link below for more information on the problems caused by this bug.
		 *
		 * @link https://stellarwp.atlassian.net/browse/ET-1792
		 *
		 * Second iteration see below for more information.
		 *
		 * @link https://stellarwp.atlassian.net/browse/ET-2142
		 * @link https://github.com/the-events-calendar/event-tickets/pull/3095
		 *
		 * @since 5.7.1
		 * @since 5.13.0 - Process Refunds requests always.
		 */
		if (
			$payment_intent
			&& isset( $payment_intent['status'] )
			&& Status::SUCCEEDED === $payment_intent['status']
			&& Events::CHARGE_REFUNDED !== Arr::get( $event, 'type' )
		) {
				$response->set_status( 200 );
				$response->set_data(
					sprintf(
						// Translators: %1$s is the event id and %2$s is the event type name.
						__( 'Event %1$s was received and will not be handled because the Payment Intent %2$s was already moved to the Success status.', 'event-tickets' ),
						esc_html( Arr::get( $event, 'id' ) ),
						esc_html( $payment_intent_id )
					)
				);

				return $response;
		}

		if ( empty( $order ) ) {

			if ( empty( $charge_data['metadata'][ Payment_Intent::$tc_metadata_identifier ] ) ) {
				$response->set_status( 200 );
				$response->set_data( sprintf(
				// Translators: %1$s is the event id and %2$s is the event type name.
					__( 'Event %1$s was received and will not be handled because the Payment Intent %2$s does not refer to an Event Tickets transaction.', 'event-tickets' ),
					esc_html( Arr::get( $event, 'id' ) ),
					esc_html( $payment_intent_id )
				) );

				return $response;
			}

			if ( ! empty( $charge_data['metadata'][ Payment_Intent::$test_metadata_key ] ) ) {
				$response->set_status( 200 );
				$response->set_data(
					__( 'Payment Intent Test Successful', 'event-tickets' )
				);

				return $response;
			}

			return new \WP_Error( 200, sprintf(
				// Translators: %s is the payment intent id.
				__( 'Payment Intent %s does not correspond to a known order.', 'event-tickets' ),
				esc_html( $payment_intent_id )
			) );
		}

		$meta = [
			'gateway_payload'  => $charge_data,
			'gateway_order_id' => $payment_intent_id,
		];

		return Handler::update_order_status( $order, $new_status, $meta );
	}

	/**
	 * Get the charge object array from the webhook event data.
	 *
	 * @since 5.3.0
	 *
	 * @param array $event Event data coming from the Webhook.
	 *
	 * @return string
	 */
	protected static function get_charge_data( array $event ): array {
		return $event['data']['object'];
	}
}
