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
	 */
	public function get_gateway_dashboard_url_by_order( \WP_Post $order ): string {
		$status          = tribe( Status_Handler::class )->get_by_wp_slug( $order->post_status );
		$payload         = $order->gateway_payload[ $status::SLUG ] ?? end( $order->gateway_payload );
		$capture_payload = end( $payload );
		$live            = Arr::get( $capture_payload, 'livemode' );

		if ( tribe_is_truthy( $live ) ) {
			return sprintf( 'https://dashboard.stripe.com/payments/%s', Arr::get( $capture_payload, 'id' ) );
		}

		return sprintf( 'https://dashboard.stripe.com/test/payments/%s', Arr::get( $capture_payload, 'id' ) );
	}
}