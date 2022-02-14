<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;

use TEC\Tickets\Commerce\Status\Status_Interface;

/**
 * Webhook for Payment_Intent operations
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe\Webhooks
 */
class Payment_Intent {

	/**
	 * Handler for Stripe webhook events in the payment_intent family.
	 *
	 * @since TBD
	 *
	 * @param array            $event
	 * @param Status_Interface $new_status
	 * @param \WP_REST_Request $request
	 *
	 * @return bool
	 */
	public static function handle( array $event, Status_Interface $status, \WP_REST_Request $request ): bool {
		$payment_intent    = static::get_payment_intent_data( $event );
		$payment_intent_id = $payment_intent['id'];

		$order = static::get_order_by_payment_intent_id( $payment_intent_id );

		if ( empty( $order ) ) {
			return false;
		}

		$meta = [
			'gateway_payload'  => $payment_intent,
			'gateway_order_id' => $payment_intent_id,
		];

		$updated = Handler::update_order_status( $order, $status, $meta );

		if ( is_wp_error( $updated ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the order to update.
	 *
	 * @since TBD
	 *
	 * @param string $payment_intent_id The payment intent id.
	 *
	 * @return mixed|\WP_Post|null
	 */
	public static function get_order_by_payment_intent_id( string $payment_intent_id ) {
		return tec_tc_orders()->by_args( [
			'gateway_order_id' => $payment_intent_id,
		] )->first();
	}

	/**
	 * Get the payment intent id from the webhook event data.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @param array $event Event data coming from the Webhook.
	 *
	 * @return string
	 */
	protected static function get_payment_intent_data( array $event ): array {
		return $event['data']['object'];
	}
}