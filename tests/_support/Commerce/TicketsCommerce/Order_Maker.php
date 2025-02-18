<?php
namespace Tribe\Tickets\Test\Commerce\TicketsCommerce;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Tickets\Test\Commerce\Ticket_Maker as Ticket_Maker_Base;

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
			$cart->get_repository()->upsert_item( $id, $quantity );
		}

		$default_purchaser = [
			'purchaser_user_id'    => 0,
			'purchaser_full_name'  => 'Test Purchaser',
			'purchaser_first_name' => 'Test',
			'purchaser_last_name'  => 'Purchaser',
			'purchaser_email'      => 'test-' . uniqid() . '@test.com',
		];

		$gateway = $overrides['gateway'] ?? tribe( Gateway::class );

		$order_status = $overrides['order_status'] ?? Completed::SLUG;
		$purchaser    = wp_parse_args( $overrides, $default_purchaser );

		$feed_args_callback = function ( $args ) use ( $overrides ) {
			$args['post_date']     = $overrides['post_date'] ?? '';
			$args['post_date_gmt'] = $overrides['post_date_gmt'] ?? $args['post_date'];

			return $args;
		};

		add_filter( 'tec_tickets_commerce_order_create_args', $feed_args_callback );

		$orders = tribe( Order::class );
		$order  = $orders->create_from_cart( $gateway, $purchaser );

		// If the order can't be transitioned to pending, return false.
		if ( ! $orders->modify_status( $order->ID, Pending::SLUG ) ) {
			return false;
		}

		// If the requested status isn't pending, and the order can't be transitioned to the desired status, return the order.
		if ( Pending::SLUG !== $order_status && ! $orders->modify_status( $order->ID, $order_status ) ) {
			return $order;
		}

		clean_post_cache( $order->ID );

		remove_filter( 'tec_tickets_commerce_order_create_args', $feed_args_callback );

		$cart->clear_cart();

		return $order;
	}
}
