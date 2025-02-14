<?php

namespace TEC\Tickets\Commerce\Gateways\Manual;

use TEC\Tickets\Commerce\Abstract_Order;
use TEC\Tickets\Commerce\Order as Commerce_Order;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe__Utils__Array as Arr;

/**
 * Class Order
 *
 * @since 5.2.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Manual
 */
class Order extends Abstract_Order {
	/**
	 * Creates a manual Order based on set of items and a purchaser.
	 *
	 * @since 5.2.0
	 *
	 * @throws \Tribe__Repository__Usage_Error
	 *
	 * @param array $items
	 * @param array $purchaser
	 *
	 * @return false|\WP_Post
	 */
	public function create( $items, $purchaser = [] ) {
		$order   = tribe( Commerce_Order::class );
		$gateway = tribe( Gateway::class );

		$items      = array_map(
			static function ( $item ) {

				/** @var Value $ticket_value */
				$ticket_value         = tribe( Ticket::class )->get_price_value( $item['ticket_id'] );
				$ticket_regular_value = tribe( Ticket::class )->get_price_value( $item['ticket_id'], true );

				if ( null === $ticket_value ) {
					return null;
				}

				// Price should be 0 for Manual attendee orders.
				$item['price']     = 0;
				$item['sub_total'] = 0;

				if ( null !== $ticket_regular_value ) {
					$item['regular_price']     = $ticket_regular_value->get_decimal();
					$item['regular_sub_total'] = $ticket_regular_value->sub_total( $item['quantity'] )->get_decimal();
				}

				return $item;
			},
			$items
		);
		$total = $this->get_value_total( array_filter( $items ) );
		$hash  = wp_generate_password( 12, false );

		$order_args = [
			'title'       => $order->generate_order_title( $items, [ 'M', $hash ] ),
			'total_value' => $total->get_decimal(),
			'items'       => $items,
			'gateway'     => $gateway::get_key(),
		];

		// When purchaser data-set is not passed we pull from the current user.
		if ( empty( $purchaser ) && is_user_logged_in() && $user = wp_get_current_user() ) {
			$order_args['purchaser_user_id']    = $user->ID;
			$order_args['purchaser_full_name']  = $user->first_name . ' ' . $user->last_name;
			$order_args['purchaser_first_name'] = $user->first_name;
			$order_args['purchaser_last_name']  = $user->last_name;
			$order_args['purchaser_email']      = $user->user_email;
		} elseif ( empty( $purchaser ) ) {
			$order_args['purchaser_user_id']    = 0;
			$order_args['purchaser_full_name']  = Commerce_Order::$placeholder_name;
			$order_args['purchaser_first_name'] = Commerce_Order::$placeholder_name;
			$order_args['purchaser_last_name']  = Commerce_Order::$placeholder_name;
			$order_args['purchaser_email']      = '';
		} else {
			$order_args['purchaser_user_id'] = Arr::get( $purchaser, 'user_id', 0 );
			if ( ! empty( $purchaser['full_name'] ) ) {
				$order_args['purchaser_full_name'] = Arr::get( $purchaser, 'full_name' );
			}
			if ( ! empty( $purchaser['first_name'] ) ) {
				$order_args['purchaser_first_name'] = Arr::get( $purchaser, 'first_name' );
			}
			if ( ! empty( $purchaser['last_name'] ) ) {
				$order_args['purchaser_last_name'] = Arr::get( $purchaser, 'last_name' );
			}
			if ( ! empty( $purchaser['email'] ) ) {
				$order_args['purchaser_email'] = Arr::get( $purchaser, 'email' );
			}
		}

		return $order->create( $gateway, $order_args );
	}
}
