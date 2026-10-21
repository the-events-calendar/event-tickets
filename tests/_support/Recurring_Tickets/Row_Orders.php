<?php
/**
 * Creates Tickets Commerce orders for table tickets in tests.
 *
 * @package TEC\Tickets\Tests\Recurring_Tickets
 */

namespace TEC\Tickets\Tests\Recurring_Tickets;

use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Utils\Currency;
use TEC\Tickets\Commerce\Utils\Value;
use WP_Post;

/**
 * Trait Row_Orders.
 *
 * The cart does not take a table ticket yet (soft-4836, SOFT-4842), so the order is created from the items
 * `Order::create_from_cart()` would build, then moved through its statuses so the order's flag actions run.
 *
 * @package TEC\Tickets\Tests\Recurring_Tickets
 */
trait Row_Orders {
	/**
	 * Creates an order and moves it to a status.
	 *
	 * @param array<int,int> $quantities Quantity by ticket ID.
	 * @param string         $status     The status to move the order to, through Pending.
	 *
	 * @return WP_Post The order.
	 */
	protected function create_row_order( array $quantities, string $status = Completed::SLUG ): WP_Post {
		$items = [];
		$total = Value::create( 0 );

		foreach ( $quantities as $ticket_id => $quantity ) {
			$ticket   = tribe( Ticket::class )->get_ticket( $ticket_id );
			$price    = Value::create( $ticket->price );
			$subtotal = $price->sub_total( $quantity );
			$total    = Value::create( $total->get_decimal() + $subtotal->get_decimal() );
			$items[]  = [
				'event_id'          => tribe( Ticket::class )->get_related_event_id( $ticket_id ),
				'extra'             => [],
				'price'             => $price->get_decimal(),
				'quantity'          => $quantity,
				'regular_price'     => Value::create( $ticket->regular_price )->get_decimal(),
				'regular_sub_total' => Value::create( $ticket->regular_price )->sub_total( $quantity )->get_decimal(),
				'sub_total'         => $subtotal->get_decimal(),
				'ticket_id'         => $ticket_id,
				'type'              => 'ticket',
			];
		}

		$purchaser = [
			'purchaser_user_id'    => 0,
			'purchaser_full_name'  => 'Row Buyer',
			'purchaser_first_name' => 'Row',
			'purchaser_last_name'  => 'Buyer',
			'purchaser_email'      => 'row-buyer-' . uniqid() . '@test.com',
		];

		// As Order::create_from_cart() does: fees and coupons join the order here.
		$items = apply_filters( 'tec_tickets_commerce_create_order_from_cart_items', $items, $total, tribe( Gateway::class ), $purchaser );
		$total = Value::create( array_sum( array_map( static fn( array $item ) => (float) $item['sub_total'], $items ) ) );

		$order = tribe( Order::class )->create(
			tribe( Gateway::class ),
			[
				'title'                => 'Row order',
				'total_value'          => $total->get_decimal(),
				'subtotal'             => $total->get_decimal(),
				'items'                => $items,
				'gateway'              => Gateway::get_key(),
				'hash'                 => uniqid( 'row-order-', true ),
				'currency'             => Currency::get_currency_code(),
			] + $purchaser
		);

		tribe( Order::class )->modify_status( $order->ID, Pending::SLUG );

		if ( Pending::SLUG !== $status ) {
			tribe( Order::class )->modify_status( $order->ID, $status );
		}

		clean_post_cache( $order->ID );

		return tec_tc_get_order( $order->ID );
	}
}
