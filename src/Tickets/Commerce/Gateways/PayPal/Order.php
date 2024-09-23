<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use TEC\Tickets\Commerce\Abstract_Order;
use TEC\Tickets\Commerce\Status\Status_Handler;
use Tribe__Utils__Array as Arr;

/**
 * Class Order
 *
 * @since 5.6.0
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Order extends Abstract_Order {
	/**
	 * @inheritDoc
	 *
	 * @since 5.10.0 Fixed extra trailing slash.
	 */
	public function get_gateway_dashboard_url_by_order( \WP_Post $order ): string {
		$status  = tribe( Status_Handler::class )->get_by_wp_slug( $order->post_status );
		$payload = $order->gateway_payload[ $status::SLUG ] ?? current( $order->gateway_payload );

		if ( ! is_array( $payload ) || empty( $payload ) ) {
			return '';
		}

		$capture_payload = end( $payload );
		$capture_id      = Arr::get( $capture_payload, [ 'purchase_units', 0, 'payments', 'captures', 0, 'id' ] );

		$paypal_base_url = 'https://www.paypal.com/';

		$capture_link = Arr::get( $capture_payload, [ 'links', 0, 'href' ] );
		// Check if the link contains sandbox.
		if ( strpos( $capture_link, 'sandbox' ) !== false ) {
			$paypal_base_url = 'https://sandbox.paypal.com/';
		}

		return sprintf( '%1$s/activity/payment/%2$s', untrailingslashit( $paypal_base_url ), $capture_id );
	}
}
