<?php
/**
 * Validate Stock Availability Flag Action.
 *
 * @since 5.26.7
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Commerce\Traits\Is_Ticket;
use Tribe__Utils__Array as Arr;
use Tribe__Tickets__Tickets;

/**
 * Class Validate_Stock_Availability.
 *
 * Validates stock availability before any stock decrease operations.
 * Runs with higher priority than Decrease_Stock to prevent overselling.
 *
 * @since 5.26.7
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Validate_Stock_Availability extends Flag_Action_Abstract {

	use Is_Ticket;

	/**
	 * {@inheritDoc}
	 *
	 * @var string[]
	 */
	protected $flags = [
		'decrease_stock',
	];

	/**
	 * {@inheritDoc}
	 *
	 * @var string[]
	 */
	protected $post_types = [
		Order::POSTTYPE,
	];

	/**
	 * {@inheritDoc}
	 *
	 * @var int
	 */
	protected $priority = 5;

	/**
	 * Handles the flag action to validate stock availability.
	 *
	 * @since 5.26.7
	 *
	 * @param Status_Interface $new_status The new status.
	 * @param mixed            $old_status The old status.
	 * @param \WP_Post         $post       The order post object.
	 */
	public function handle( Status_Interface $new_status, $old_status, \WP_Post $post ) {
		if ( empty( $post->items ) ) {
			return;
		}

		$insufficient_stock_items = [];

		foreach ( $post->items as $item ) {
			if ( ! $this->is_ticket( $item ) ) {
				continue;
			}

			$ticket = Tribe__Tickets__Tickets::load_ticket_object( $item['ticket_id'] );
			if ( null === $ticket ) {
				continue;
			}

			// Skip validation if the ticket does not manage stock.
			if ( ! $ticket->manage_stock() ) {
				continue;
			}

			// Skip validation for unlimited capacity tickets.
			if ( -1 === $ticket->capacity() ) {
				continue;
			}

			// Skip validation for shared capacity (global stock) tickets.
			$global_stock_mode  = $ticket->global_stock_mode();
			$is_shared_capacity = ! empty( $global_stock_mode ) && 'own' !== $global_stock_mode;
			if ( $is_shared_capacity ) {
				continue;
			}

			$requested_quantity = (int) Arr::get( $item, 'quantity', 1 );
			$available_stock    = $ticket->stock();

			if ( $available_stock < $requested_quantity ) {
				$insufficient_stock_items[] = [
					'item'      => $item,
					'ticket'    => $ticket,
					'requested' => $requested_quantity,
					'available' => $available_stock,
				];
			}
		}

		if ( ! empty( $insufficient_stock_items ) ) {
			$this->handle_insufficient_stock( $post, $insufficient_stock_items );
		}
	}

	/**
	 * Handles the scenario when insufficient stock is detected.
	 *
	 * @since 5.26.7
	 *
	 * @param \WP_Post $order                    The order post object.
	 * @param array    $insufficient_stock_items Array of items with insufficient stock.
	 */
	private function handle_insufficient_stock( \WP_Post $order, array $insufficient_stock_items ) {
		/**
		 * Fires when insufficient stock is detected during order processing.
		 *
		 * @since 5.26.7
		 *
		 * @param \WP_Post $order                    The order post object.
		 * @param array    $insufficient_stock_items Array of items with insufficient stock.
		 */
		do_action( 'tec_tickets_commerce_insufficient_stock_detected', $order, $insufficient_stock_items );
	}
}
