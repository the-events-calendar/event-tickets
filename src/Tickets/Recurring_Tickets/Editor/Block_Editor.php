<?php
/**
 * Recurring event tickets in the block editor.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Editor
 */

namespace TEC\Tickets\Recurring_Tickets\Editor;

use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Lists, in a date's Tickets block, the event's recurring event tickets rather than the date's copies of them.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Editor
 */
final class Block_Editor {
	/**
	 * Removes a date's copies of the recurring event tickets from the tickets the Tickets block loads.
	 *
	 * A date shares its event's content, so its Tickets block already holds the event's tickets; a copy would be
	 * added to it as a ticket of its own.
	 *
	 * @since TBD
	 *
	 * @param Ticket_Object[]|mixed $tickets The tickets the block loads.
	 *
	 * @return Ticket_Object[]|mixed The tickets, without a date's copies.
	 */
	public function remove_date_copies( $tickets ) {
		if ( ! is_array( $tickets ) ) {
			return $tickets;
		}

		return array_values(
			array_filter(
				$tickets,
				static fn( $ticket ) => ! ( $ticket instanceof Ticket_Object && Ticket_ID::is_table_ticket( $ticket->ID ) )
			)
		);
	}
}
