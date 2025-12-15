<?php
/**
 * V2 Ticket helper class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Tickets\Commerce\Ticket as Commerce_Ticket;

/**
 * Class Ticket
 *
 * Provides RSVP-specific ticket operations for V2 implementation.
 * V2 RSVP uses TC (Tickets Commerce) infrastructure.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Ticket {

	/**
	 * Get the ticket type identifier for RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @return string The RSVP ticket type.
	 */
	public function get_type(): string {
		return Constants::TC_RSVP_TYPE;
	}

	/**
	 * Check if a ticket is an RSVP ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID to check.
	 *
	 * @return bool True if the ticket is an RSVP ticket.
	 */
	public function is_rsvp( int $ticket_id ): bool {
		$type = get_post_meta( $ticket_id, Commerce_Ticket::$type_meta_key, true );

		return $type === Constants::TC_RSVP_TYPE;
	}

	/**
	 * Set a ticket as an RSVP ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return bool True on success.
	 */
	public function set_as_rsvp( int $ticket_id ): bool {
		return (bool) update_post_meta( $ticket_id, Commerce_Ticket::$type_meta_key, Constants::TC_RSVP_TYPE );
	}
}
