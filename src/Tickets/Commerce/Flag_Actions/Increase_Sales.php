<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Denied;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Commerce\Ticket;
use Tribe__Utils__Array as Arr;

/**
 * Class Increase_Sales, normally triggered when refunding on orders get set to not-completed.
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Increase_Sales extends Flag_Action_Abstract {

	/**
	 * {@inheritDoc}
	 */
	protected $flags = [
		'increase_sales',
	];

	/**
	 * {@inheritDoc}
	 */
	protected $post_types = [
		Order::POSTTYPE,
	];

	protected $ticket;

	/**
	 * {@inheritDoc}
	 */
	public function handle( Status_Interface $new_status, $old_status, \WP_Post $post ) {
		if ( empty( $post->items ) ) {
			return;
		}

		foreach ( $post->items as $ticket_id => $item ) {
			$this->ticket       = \Tribe__Tickets__Tickets::load_ticket_object( $item['ticket_id'] );
			$this->global_stock = new \Tribe__Tickets__Global_Stock( $this->ticket->get_event_id() );

			if ( null === $this->ticket ) {
				continue;
			}

			$quantity = Arr::get( $item, 'quantity', 1 );

			// Skip generating for zero-ed items.
			if ( 0 >= $quantity ) {
				continue;
			}

			$this->increase_sales_by( $quantity );
		}
	}

	private function increase_sales_by( $quantity ) {
		tribe( Ticket::class )->increase_ticket_sales_by( $this->ticket->ID, $quantity, $this->ticket->global_stock_mode(), $this->global_stock );
	}
}