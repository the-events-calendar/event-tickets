<?php
/**
 * Looks up the fees of a recurring event ticket on its template.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;

/**
 * Class Fees.
 *
 * Fees are attached to ticket posts. A row sells for its template, so its fees are the template's.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Fees {
	/**
	 * The rows.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

	/**
	 * Fees constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows $rows The rows.
	 */
	public function __construct( Rows $rows ) {
		$this->rows = $rows;
	}

	/**
	 * Returns the ID a ticket's fees are attached to.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return int The template's ID for a table ticket, 0 when it has none; any other ID unchanged.
	 */
	public function lookup_id( int $ticket_id ): int {
		if ( ! Ticket_ID::is_table_ticket( $ticket_id ) ) {
			return $ticket_id;
		}

		$row = $this->rows->find( Ticket_ID::to_row_id( $ticket_id ) );

		return $row ? (int) $row->parent_id : 0;
	}
}
