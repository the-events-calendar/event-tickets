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
	 *
	 * @return false|\WP_Post The order post object or false if the order could not be created.
	 */
	protected function create_order( array $items ) {
		$cart = new Cart();

		foreach ( $items as $id => $quantity ) {
			$cart->get_repository()->add_item( $id, $quantity );
		}

		$purchaser = [
			'purchaser_user_id'    => 0,
			'purchaser_full_name'  => 'Test Purchaser',
			'purchaser_first_name' => 'Test',
			'purchaser_last_name'  => 'Purchaser',
			'purchaser_email'      => 'test-'.uniqid().'@test.com',
		];

		$order     = tribe( Order::class )->create_from_cart( tribe( Gateway::class ), $purchaser );
		$completed = tribe( Order::class )->modify_status( $order->ID, Pending::SLUG );
		$completed = tribe( Order::class )->modify_status( $order->ID, Completed::SLUG );
		$cart->clear_cart();

		return $order;
	}
}
