<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Traits\Is_Ticket;
use Tribe__Utils__Array as Arr;

/**
 * Class Increase_Stock, normally triggered when refunding on orders get set to not-completed.
 *
 * @since   5.1.9
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

			$quantity = Arr::get( $item, 'quantity', 1 );

			// Skip generating for zero-ed items.
			if ( 0 >= $quantity ) {
				continue;
			}

			update_post_meta( $ticket->ID, Ticket::$stock_meta_key, $ticket->stock() + $quantity );
		}
	}
}
