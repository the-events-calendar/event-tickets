<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;

use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Commerce\Gateways\Contracts\Webhook_Event_Interface;

/**
 * Webhook for Charge operations.
 *
 * @since   TBD
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

		$order = static::get_order_by_payment_intent_id( $payment_intent_id );

		if ( empty( $order ) ) {
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
	 * @since TBD
	 *
	 * @param array $event Event data coming from the Webhook.
	 *
	 * @return string
	 */
	protected static function get_charge_data( array $event ): array {
		return $event['data']['object'];
	}
}