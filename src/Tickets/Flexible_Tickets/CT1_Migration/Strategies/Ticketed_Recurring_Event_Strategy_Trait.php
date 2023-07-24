<?php
/**
 * Provides common methods for the migration strategies dealing with Ticketed Recurring Events (excluding RSVP).
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies;
 */

namespace TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies;

use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\CT1_Migration\CT1_Migration_Checks;
use TEC\Tickets\Flexible_Tickets\Series_Passes;
use Tribe__Tickets__Main as Tickets_Main;
use Tribe__Tickets__Tickets as Tickets;

/**
 * Trait Ticketed_Recurring_Event_Strategy_Trait.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies;
 */
trait Ticketed_Recurring_Event_Strategy_Trait {
	use CT1_Migration_Checks;

	/**
	 * Returns a list of meta keys relating a Ticket or an Attende to the Event.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The Ticket or Attendee ID.
	 *
	 * @return string[] A list of meta keys relating a Ticket or an Attende to the Event.
	 */
	private function get_event_relationship_meta_keys( int $post_id ): array {
		$post_meta = get_post_meta( $post_id );

		$meta_keys = [];
		foreach ( array_keys( $post_meta ) as $meta_key ) {
			if ( preg_match( '/^_(tribe|tec)_.*_event$/', $meta_key ) ) {
				$meta_keys[] = $meta_key;
			}
		}

		return $meta_keys;
	}

	/**
	 * Ensures that the Series Post Type is ticketable.
	 *
	 * This is required because the Series Post Type is not ticketable by default and the migration cannot rely on
	 * the option being already set. This method is idem-potent and each migration instance running it will have
	 * the same effect.
	 *
	 * @since TBD
	 */
	protected function ensure_series_ticketable(): void {
		$ticketable_post_types   = Tickets_Main::instance()->post_types();
		$ticketable_post_types[] = Series_Post_Type::POSTTYPE;
		$ticketable_post_types   = array_unique( $ticketable_post_types );
		tribe_update_option( 'ticket-enabled-post-types', $ticketable_post_types );
	}

	/**
	 * Returns the IDs of the Attendees for a given Ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The Ticket ID.
	 *
	 * @return int[] The post IDs of the Attendees for the given Ticket.
	 */
	protected function get_attendee_ids( $ticket_id ): array {
		return tribe_attendees()->where( 'ticket', $ticket_id )->get_ids();
	}


	/**
	 * Moves the Tickets and their Attendees to the Series.
	 *
	 * In the process, each Ticket is converted to a Series Pass.
	 * The "move" of Tickets and Attendees is performed by updating the meta keys that relate them to the Event,
	 * a low-level operation that does not trigger any hooks.
	 *
	 * @since TBD
	 *
	 * @param int $series_id The ID of the Series to move the Tickets to.
	 *
	 * @return array{0: int, 1: array<int,int>} The number of moved Tickets and the number of moved Attendees per Ticket
	 *                                          indexed by Ticket ID, respectively.
	 */
	protected function move_tickets_to_series( int $series_id ): array {
		$moved_tickets   = [];
		$moved_attendees = [];
		foreach ( $this->get_ticket_ids( $this->post_id ) as $ticket_id ) {
			$meta_keys = $this->get_event_relationship_meta_keys( $ticket_id );

			// Attach the Ticket to the Series.
			foreach ( $meta_keys as $meta_key ) {
				update_post_meta( $ticket_id, $meta_key, $series_id );
			}

			// Update the Ticket type to Series Pass.
			update_post_meta( $ticket_id, '_type', Series_Passes::TICKET_TYPE );

			$moved_tickets[]                = $ticket_id;
			$moved_attendees [ $ticket_id ] = 0;

			$attendee_ids = $this->get_attendee_ids( $ticket_id );

			if ( ! count( $attendee_ids ) ) {
				continue;
			}

			// Use the first Attendee to sample the meta keys that relate Attendees to the Event.
			$first_attendee_id = $attendee_ids[0];
			$meta_keys         = $this->get_event_relationship_meta_keys( $first_attendee_id );

			foreach ( $attendee_ids as $attendee_id ) {
				// Attach the Attendee to the Series.
				foreach ( $meta_keys as $meta_key ) {
					update_post_meta( $attendee_id, $meta_key, $series_id );
					$moved_attendees[ $ticket_id ] ++;
				}
			}
		}

		return [ $moved_tickets, $moved_attendees ];
	}

	/**
	 * Sets the default ticket provider for the given Series by either using the one set for the Event or the default
	 * one.
	 *
	 * @since TBD
	 *
	 * @param int $series_id The ID of the Series.
	 */
	protected function set_default_ticket_provider( int $series_id ): void {
		$meta_key        = tribe( 'tickets.handler' )->key_provider_field;
		$ticket_provider = get_post_meta( $this->post_id, $meta_key, true );
		if ( empty( $ticket_provider ) ) {
			$ticket_provider = Tickets::get_default_module();
		}
		update_post_meta( $series_id, $meta_key, str_replace( '\\', '\\\\', $ticket_provider ) );
	}
}