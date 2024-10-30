<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Abstract_Order;
use TEC\Tickets\Commerce\Status\Status_Handler;
use Tribe__Utils__Array as Arr;

/**
 * Class Order
 *
 * @since 5.6.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Order extends Abstract_Order {
	/**
	 * @inheritDoc
	 *
	 * @since 5.9.1 Added logic to handle when $payload and $capture_payload are not the expected data.
	 */
	public function get_gateway_dashboard_url_by_order( \WP_Post $order ): string {
		$status  = tribe( Status_Handler::class )->get_by_wp_slug( $order->post_status );
		$payload = $order->gateway_payload[ $status::SLUG ] ?? end( $order->gateway_payload );

		if ( ! is_array( $payload ) ) {
			return '';
		}

		$capture_payload = end( $payload );

		if ( empty( $capture_payload ) ) {
			return '';
		}

		$live = Arr::get( $capture_payload, 'livemode' );

		if ( tribe_is_truthy( $live ) ) {
			return sprintf( 'https://dashboard.stripe.com/payments/%s', Arr::get( $capture_payload, 'id' ) );
		}

		return sprintf( 'https://dashboard.stripe.com/test/payments/%s', Arr::get( $capture_payload, 'id' ) );
	}
}