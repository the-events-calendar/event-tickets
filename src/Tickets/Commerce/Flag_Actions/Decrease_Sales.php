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
 * @since    5.2.0
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Decrease_Sales extends Flag_Action_Abstract {

	use Is_Ticket;

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.2.0
	 */
	protected $flags = [
		'decrease_sales',
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

			$quantity = Arr::get( $item, 'quantity' );

			if ( ! $quantity ) {
				continue;
			}

			// Skip generating for zero-ed items.
			if ( 0 >= $quantity ) {
				continue;
			}

			$global_stock = new \Tribe__Tickets__Global_Stock( $ticket->get_event_id() );

			tribe( Ticket::class )->decrease_ticket_sales_by( $ticket->ID, $quantity, $ticket->global_stock_mode(), $global_stock );
		}
	}
}
