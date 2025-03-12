<?php

namespace Tribe\Tickets\Test\Commerce\TicketsCommerce;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Tickets\Test\Commerce\Ticket_Maker as Ticket_Maker_Base;
use Tribe__Repository__Usage_Error;
use WP_Post;

trait Order_Maker {

	use Ticket_Maker_Base;

	/**
	 * Takes a list of tickets and creates an order for them.
	 *
	 * @param array $items     An array with the item ID as the key, and data as value. If
	 *                         the data is a number it will be treated as the quantity. If
	 *                         the data is an array, it can contain the following keys:
	 *                         'id': override the ID used for the item.
	 *                         'quantity': the quantity of the item.
	 *                         'extras': an array of extras to add to the item.
	 * @param array $overrides An array of overrides for the purchaser.
	 *
	 * @return false|WP_Post The order post object or false if the order could not be created.
	 */
	protected function create_order( array $items, array $overrides = [] ) {
		$cart = new Cart();

		// Individiaul items can be a simple quantity, or an array with more data.
		foreach ( $items as $id => $data ) {
			if ( is_array( $data ) ) {
				$cart->get_repository()->upsert_item(
					$data['id'] ?? $id,
					$data['quantity'] ?? 1,
					$data['extras'] ?? [],
				);
			} else {
				$cart->get_repository()->upsert_item( $id, $data );
			}
		}

		$order = $this->create_order_from_cart( $overrides );
		$cart->clear_cart();

		return $order;
	}

	/**
	 * Create an order from a cart.
	 *
	 * Does NOT clear the cart afterwards.
	 *
	 * @param array $overrides An array of overrides for the purchaser.
	 *
	 * @return false|WP_Post The order post object or false if the order could not be created.
	 */
	protected function create_order_from_cart( array $overrides = [] ) {
		$order        = $this->create_order_without_transitions( $overrides );
		$order_status = $overrides['order_status'] ?? Completed::SLUG;

		/** @var Order $orders */
		$orders = tribe( Order::class );

		// If the order can't be transitioned to pending, return false.
		if ( ! $orders->modify_status( $order->ID, Pending::SLUG ) ) {
			return false;
		}

		// If the requested status isn't pending, and the order can't be transitioned to the desired status, return the order.
		if ( Pending::SLUG !== $order_status && ! $orders->modify_status( $order->ID, $order_status ) ) {
			return $order;
		}

		clean_post_cache( $order->ID );

		return $order;
	}

	/**
	 * Create an order without transitioning it.
	 *
	 * @param array $overrides An array of overrides for the purchaser.
	 *
	 * @return WP_Post The order post object.
	 */
	protected function create_order_without_transitions( array $overrides = [] ) {
		$default_purchaser = [
			'purchaser_user_id'    => 0,
			'purchaser_full_name'  => 'Test Purchaser',
			'purchaser_first_name' => 'Test',
			'purchaser_last_name'  => 'Purchaser',
			'purchaser_email'      => 'test-' . uniqid() . '@test.com',
		];

		$gateway   = $overrides['gateway'] ?? tribe( Gateway::class );
		$purchaser = wp_parse_args( $overrides, $default_purchaser );

		$feed_args_callback = function ( $args ) use ( $overrides ) {
			$args['post_date']     = $overrides['post_date'] ?? '';
			$args['post_date_gmt'] = $overrides['post_date_gmt'] ?? $args['post_date'];

			return $args;
		};

		add_filter( 'tec_tickets_commerce_order_create_args', $feed_args_callback );

		/** @var Order $orders */
		$orders = tribe( Order::class );
		$order  = $orders->create_from_cart( $gateway, $purchaser );

		remove_filter( 'tec_tickets_commerce_order_create_args', $feed_args_callback );

		return $order;
	}
}
