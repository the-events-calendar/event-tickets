<?php
/**
 * The ticket IDs of rows in the Recurring Event Tickets table.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use InvalidArgumentException;

/**
 * Class Ticket_ID.
 *
 * A table ticket's ID is the base plus its row ID, so it can travel through the cart, the order, the attendee and
 * the QR code as any ticket ID does, and never be a post ID. `is_table_ticket()` is the only check the code uses.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Ticket_ID {
	/**
	 * The default base: the first table ticket ID is one above it.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const BASE = 1000000000;

	/**
	 * Returns the base table ticket IDs are counted from.
	 *
	 * @since TBD
	 *
	 * @return int The base.
	 */
	public static function base(): int {
		/**
		 * Filters the base table ticket IDs are counted from.
		 *
		 * Set it before the first recurring event ticket is created and never change it afterwards: attendees,
		 * orders and QR codes keep the ticket IDs they were given, and a new base would point them at other rows.
		 *
		 * @since TBD
		 *
		 * @param int $base The base. Default 1,000,000,000.
		 */
		return (int) apply_filters( 'tec_tickets_recurring_tickets_ticket_id_base', self::BASE );
	}

	/**
	 * Whether an ID is a table ticket ID.
	 *
	 * @since TBD
	 *
	 * @param mixed $id The ID, an integer or a string of digits as it comes from a request.
	 *
	 * @return bool Whether the ID is at or above the base.
	 */
	public static function is_table_ticket( $id ): bool {
		// The round trip refuses leading zeros and digit strings beyond the integer range, which (int) would clamp.
		if ( is_string( $id ) && (string) (int) $id === $id ) {
			$id = (int) $id;
		}

		return is_int( $id ) && $id >= self::base();
	}

	/**
	 * Returns the ticket ID of a row.
	 *
	 * @since TBD
	 *
	 * @param int $row_id The row ID.
	 *
	 * @return int The ticket ID.
	 *
	 * @throws InvalidArgumentException If the row ID is not positive.
	 */
	public static function from_row_id( int $row_id ): int {
		if ( $row_id < 1 ) {
			throw new InvalidArgumentException( "Row ID {$row_id} is not positive." );
		}

		return self::base() + $row_id;
	}

	/**
	 * Returns the row ID of a table ticket.
	 *
	 * The base itself is a table ticket ID and gives row 0, which never exists: it loads as a missing ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return int The row ID.
	 *
	 * @throws InvalidArgumentException If the ID is below the base, so not a table ticket ID.
	 */
	public static function to_row_id( int $ticket_id ): int {
		$row_id = $ticket_id - self::base();

		if ( $row_id < 0 ) {
			throw new InvalidArgumentException( "Ticket ID {$ticket_id} is not a table ticket ID." );
		}

		return $row_id;
	}
}
