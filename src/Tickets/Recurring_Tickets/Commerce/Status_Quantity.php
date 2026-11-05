<?php
/**
 * The quantity of a row's ticket ordered in each status.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Commerce
 */

namespace TEC\Tickets\Recurring_Tickets\Commerce;

use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;

/**
 * Counts a row's ticket in its orders: Tickets Commerce keeps the count in the ticket's post meta, and a row has no
 * post to keep it in.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Commerce
 */
final class Status_Quantity {
	/**
	 * Gives a row's ticket the quantity ordered in each status, from its orders.
	 *
	 * @since TBD
	 *
	 * @param array<string,int>|null|mixed $quantities The quantities another filter gave, or null.
	 * @param int|string|object|mixed      $ticket_id  The ticket.
	 *
	 * @return array<string,int>|null|mixed The quantity by status slug for a row's ticket; `$quantities` otherwise.
	 */
	public function count( $quantities, $ticket_id ) {
		$ticket_id = is_numeric( $ticket_id ) ? (int) $ticket_id : 0;

		if ( null !== $quantities || ! Ticket_ID::is_table_ticket( $ticket_id ) ) {
			return $quantities;
		}

		$counts = [];
		foreach ( tribe( Status_Handler::class )->get_all() as $status ) {
			$counts[ $status->get_slug() ] = 0;
		}

		// ponytail: one orders query per row's ticket; a sales count on the row when reports list hundreds of dates.
		$orders = tec_tc_orders()->by_args(
			[
				'tickets' => $ticket_id,
				'status'  => 'any',
			]
		)->all();

		foreach ( $orders as $order ) {
			foreach ( (array) $order->items as $item ) {
				if ( $ticket_id === (int) ( $item['ticket_id'] ?? 0 ) && isset( $counts[ $order->status_slug ] ) ) {
					$counts[ $order->status_slug ] += (int) $item['quantity'];
				}
			}
		}

		return $counts;
	}
}
