<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Order as Commerce_Order;

/**
 * Class Order
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Order {

	/**
	 * Create a Square order from a Commerce order.
	 *
	 * @since 5.3.0
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
	 * @since 5.3.0
	 *
	 * @param Commerce_Order $order The order object.
	 *
	 * @return string
	 */
	public function get_gateway_dashboard_url_by_order( Commerce_Order $order ) {
		$merchant = tribe( Merchant::class );

		if ( ! $merchant->is_active() ) {
			return '';
		}

		// Implement Square dashboard URL for the order
		return '';
	}
}
