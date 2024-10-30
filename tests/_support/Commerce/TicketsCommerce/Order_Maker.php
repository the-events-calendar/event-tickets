<?php
namespace Tribe\Tickets\Test\Commerce\TicketsCommerce;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Tickets\Test\Commerce\Ticket_Maker as Ticket_Maker_Base;
use TEC\Tickets\Commerce\Module as Module;

trait Order_Maker {

	use Ticket_Maker_Base;

	/**
	 * Takes a list of tickets and creates an order for them.
	 *
	 * @param array $items An array of ticket_id => quantity pairs.
	 * @param array $overrides An array of overrides for the purchaser.
	 *
	 * @return false|\WP_Post The order post object or false if the order could not be created.
	 */
	protected function create_order( array $items, array $overrides = [] ) {
		$cart = new Cart();

		foreach ( $items as $id => $quantity ) {
			$cart->get_repository()->add_item( $id, $quantity );
		}

		$default_purchaser = [
			'purchaser_user_id'    => 0,
			'purchaser_full_name'  => 'Test Purchaser',
			'purchaser_first_name' => 'Test',
			'purchaser_last_name'  => 'Purchaser',
			'purchaser_email'      => 'test-'.uniqid().'@test.com',
		];

		$gateway = $overrides['gateway'] ?? tribe( Gateway::class );

		$order_status = $overrides['order_status'] ?? Completed::SLUG;
		$purchaser = wp_parse_args( $overrides, $default_purchaser );
		$orders    = tribe( Order::class );
		$order     = $orders->create_from_cart( $gateway, $purchaser );

		if ( ! $orders->modify_status( $order->ID, Pending::SLUG ) ) {
			return false;
		}

		if ( ! $orders->modify_status( $order->ID, $order_status ) ) {
			return $order;
		}

		clean_post_cache( $order->ID );

		$cart->clear_cart();

		return $order;
	}
}
