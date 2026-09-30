<?php
/**
 * V2 Ticket helper class.
 *
 * @since 5.30.0
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket as Commerce_Ticket;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Class Ticket
 *
 * Provides RSVP-specific ticket operations for V2 implementation.
 * V2 RSVP uses TC (Tickets Commerce) infrastructure.
 *
 * @since 5.30.0
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Ticket {
	/**
	 * Get the ticket type identifier for RSVP tickets.
	 *
	 * @since 5.30.0
	 *
	 * @return string The RSVP ticket type.
	 */
	public function get_type(): string {
		return Constants::TC_RSVP_TYPE;
	}

	/**
	 * Check if a ticket is an RSVP ticket.
	 *
	 * @since 5.30.0
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
	 * @since 5.30.0
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return bool True on success.
	 */
	public function set_as_rsvp( int $ticket_id ): bool {
		return (bool) update_post_meta( $ticket_id, Commerce_Ticket::$type_meta_key, Constants::TC_RSVP_TYPE );
	}

	/**
	 * Get the TC-RSVP ticket for an event.
	 *
	 * When multiple tickets exist for the same event, returns the most recently
	 * created one so stale tickets left behind after a failed delete do not win.
	 *
	 * @since 5.30.0
	 *
	 * @param int $post_id The event post ID.
	 *
	 * @return Ticket_Object|null Matching ticket object or null when not found.
	 */
	public function get_for_event( int $post_id ): ?Ticket_Object {
		$ticket_id = (int) tribe( 'tickets.ticket-repository.rsvp' )
			->where( 'event', $post_id )
			->order_by( 'ID', 'DESC' )
			->first_id();

		if ( ! $ticket_id ) {
			return null;
		}

		$ticket = tribe( Module::class )->get_ticket( $post_id, $ticket_id );

		return $ticket instanceof Ticket_Object ? $ticket : null;
	}

	/**
	 * Never show a description for RSVP tickets.
	 *
	 * V2 RSVPs have no description field, but RSVPs migrated from V1 keep their old
	 * `post_excerpt` and a `_tribe_ticket_show_description` value of `yes`, so the
	 * stale text would otherwise render on the front end.
	 *
	 * @since 5.30.0
	 *
	 * @param mixed $show      Whether the description should show.
	 * @param mixed $ticket_id The ticket ID.
	 *
	 * @return mixed Whether the description should show.
	 */
	public function hide_description( $show, $ticket_id ) {
		return $this->is_rsvp( (int) $ticket_id ) ? false : $show;
	}
}
