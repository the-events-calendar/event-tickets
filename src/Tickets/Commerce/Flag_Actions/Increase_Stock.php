<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Traits\Is_Ticket;
use Tribe__Utils__Array as Arr;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Class Increase_Stock, normally triggered when refunding on orders get set to not-completed.
 *
 * @since 5.1.9
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Increase_Stock extends Flag_Action_Abstract {

	use Is_Ticket;

	/**
	 * {@inheritDoc}
	 */
	protected $flags = [
		'increase_stock',
	];

	/**
	 * {@inheritDoc}
	 */
	protected $post_types = [
		Order::POSTTYPE
	];

	/**
	 * {@inheritDoc}
	 */
	public function handle( Status_Interface $new_status, $old_status, \WP_Post $post ) {
		if ( empty( $post->items ) ) {
			return;
		}

		foreach ( $post->items as $item ) {
			if ( ! $this->is_ticket( $item ) ) {
				continue;
			}

			$ticket = \Tribe__Tickets__Tickets::load_ticket_object( $item['ticket_id'] );
			if ( null === $ticket ) {
				continue;
			}

			if ( ! $ticket->manage_stock() ) {
				continue;
			}

			$quantity = (int) Arr::get( $item, 'quantity', 1 );

			// Skip generating for zero-ed items.
			if ( 0 >= $quantity ) {
				continue;
			}

			$original_stock = $ticket->stock();

			$global_stock = new \Tribe__Tickets__Global_Stock( $ticket->get_event_id() );

			tribe( Ticket::class )->decrease_ticket_sales_by( $ticket->ID, $quantity, $ticket->global_stock_mode(), $global_stock );

			$stock = $ticket->stock();

			$stock_should_be = min( $original_stock + $quantity, $ticket->capacity() );

			if ( $stock_should_be !== $stock ) {
				$stock = $stock_should_be;
			}

			/**
			 * Fires after the calculations of a ticket stock increase are done but before are saved.
			 *
			 * @since 5.20.0
			 *
			 * @param Ticket_Object $ticket   The ticket post object.
			 * @param int           $quantity The quantity of tickets to increase.
			 */
			do_action( 'tec_tickets_commerce_increase_ticket_stock', $ticket, $quantity );

			update_post_meta( $ticket->ID, Ticket::$stock_meta_key, $stock );
		}
	}
}
