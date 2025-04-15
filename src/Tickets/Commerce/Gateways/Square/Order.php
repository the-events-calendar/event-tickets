<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use WP_Post;
use TEC\Tickets\Commerce\Order as Commerce_Order;
use TEC\Tickets\Commerce\Abstract_Order;
use TEC\Tickets\Commerce\Status\Created;
use Tribe__Utils__Array as Arr;

/**
 * Class Order.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Order extends Abstract_Order {

	/**
	 * Create a Square order from a Commerce order.
	 *
	 * @since TBD
	 *
	 * @param Commerce_Order $order The order object.
	 *
	 * @return Commerce_Order
	 */
	public function create_order( Commerce_Order $order ) {
		// Implement Square order creation
		return $order;
	}

	/**
	 * Get the URL to view the order in Square dashboard.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $order The order object.
	 *
	 * @return string
	 */
	public function get_gateway_dashboard_url_by_order( WP_Post $order ) : ?string {
		$merchant = tribe( Merchant::class );

		if ( ! $merchant->is_active() ) {
			return '';
		}

		$order_id = $this->get_square_order_id( $order );

		if ( empty( $order_id ) ) {
			return '';
		}

		// Use different URLs based on the mode.
		$is_test_mode = tribe( Gateway::class )->is_test_mode();

		if ( $is_test_mode ) {
			return sprintf( 'https://app.squareupsandbox.com/dashboard/orders/overview/%s', $order_id );
		}

		return sprintf( 'https://app.squareup.com/dashboard/orders/overview/%s', $order_id );
	}

	/**
	 * Get the Square order ID from the order.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $order The order object.
	 *
	 * @return ?string
	 */
	public function get_square_order_id( WP_Post $order ) : ?string {
		if ( ! $order instanceof WP_Post ) {
			return null;
		}

		$payload = Arr::get( $order->gateway_payload, [ Created::SLUG, 0 ], null );

		if ( ! is_array( $payload ) ) {
			return null;
		}

		return Arr::get( $payload, 'order_id' );
	}
}

