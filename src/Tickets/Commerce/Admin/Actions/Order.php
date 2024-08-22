<?php
/**
 * Handles Order operations.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin\Actions;
 */

namespace TEC\Tickets\Commerce\Admin\Actions;

use TEC\Tickets\Commerce\Gateways\Stripe\Requests;

/**
 * Class Order.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin\Actions;
 */
class Order {
	public static function refund( $order_id, $amount ): bool {
		//Requests::post( $url, $query_args, $args )
		do_action('start', $order_id, $amount);
		$response = $orm->refund($order_id);
		if(!$response) {
			do_action('status_failed', $order_id, $amount);
		} else {
			do_action('status_success', $order_id, $amount);
		}
		$response = $gateway->refund($order_id);
		if(!$response) {
			do_action('gateway_failed', $order_id, $amount);
		} else {
			do_action('gateway_success', $order_id, $amount);
		}
		do_action('end', $order_id, $amount);
	}

}
