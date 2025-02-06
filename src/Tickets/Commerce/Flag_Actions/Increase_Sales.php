<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Traits\Is_Ticket;
use Tribe__Utils__Array as Arr;

/**
 * Class Increase_Sales, normally triggered when refunding on orders get set to not-completed.
 *
 * @since   5.2.0
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Increase_Sales extends Flag_Action_Abstract {

	use Is_Ticket;

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.2.0
	 */
	protected $flags = [
		'increase_sales',
	];

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.2.0
	 */
	protected $post_types = [
		Order::POSTTYPE,
	];

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.2.0
	 * @since 5.13.3 Check shared capacity before sending to the `Ticket::increase_ticket_sales_by` method.
	 * @since 5.18.1    Making the action idempotent. Self aware of which tickets have already increased their sales and how many times.
	 */
	public function handle( Status_Interface $new_status, $old_status, \WP_Post $post ) {
		if ( empty( $post->items ) ) {
			return;
		}

		$already_increased_tickets = (array) get_post_meta( $post->ID, '_tribe_tickets_sales_increased', true );

		foreach ( $post->items as $item ) {
			if ( ! $this->is_ticket( $item ) ) {
				continue;
			}

			$ticket = \Tribe__Tickets__Tickets::load_ticket_object( $item['ticket_id'] );
			if ( null === $ticket ) {
				continue;
			}

			$quantity = Arr::get( $item, 'quantity' );

			if ( ! $quantity || ! is_numeric( $quantity ) ) {
				continue;
			}

			$quantity     = (int) $quantity;
			$new_quantity = $quantity;

			if (
				! empty( $already_increased_tickets[ $item['ticket_id'] ] ) &&
				$already_increased_tickets[ $item['ticket_id'] ] > 0
			) {
				$new_quantity = $quantity - $already_increased_tickets[ $item['ticket_id'] ];
			}

			$already_increased_tickets[ $item['ticket_id'] ] = $quantity;

			// Skip generating for zero-ed items.
			if ( 0 >= $new_quantity ) {
				continue;
			}

			$global_stock = new \Tribe__Tickets__Global_Stock( $ticket->get_event_id() );

			// Is ticket shared capacity?
			$global_stock_mode  = $ticket->global_stock_mode();
			$is_shared_capacity = ! empty( $global_stock_mode ) && 'own' !== $global_stock_mode;

			tribe( Ticket::class )->increase_ticket_sales_by( $ticket->ID, $new_quantity, $is_shared_capacity, $global_stock );
		}

		update_post_meta( $post->ID, '_tribe_tickets_sales_increased', $already_increased_tickets );
	}
}
