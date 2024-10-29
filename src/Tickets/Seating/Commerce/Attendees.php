<?php
/**
 * Provides methods to interact with Attndees' data.
 *
 * @since   5.16.0
 *
 * @package TEC\Tickets\Seating\Commerce;
 */

namespace TEC\Tickets\Seating\Commerce;

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Seating\Meta;
use Tribe__Cache_Listener as Triggers;

/**
 * Class Attendees.
 *
 * @since   5.16.0
 *
 * @package TEC\Tickets\Seating\Commerce;
 */
class Attendees {

	/**
	 * Returns the number of attendees for a given post and seat type..
	 *
	 * @since 5.16.0
	 *
	 * @param int    $post_id   The post ID to return the number of attendees for.
	 * @param string $seat_type The seat type UUID to return the number of attendees for.
	 *
	 * @return int The number of attendees for the given post and seat type.
	 */
	public function get_count_by_post_seat_type( int $post_id, string $seat_type ): int {
		$cache_key = sprintf( 'seating_attendees_by_post_seat_type_count_%d_%s', $post_id, $seat_type );

		$cache = tribe_cache();

		// This cached value will be invalidated by the save of any Ticket, Attendee or Order.
		$count = $cache->get( $cache_key, Triggers::TRIGGER_SAVE_POST, null, DAY_IN_SECONDS );

		if ( is_int( $count ) && $count > 0 ) {
			return $count;
		}

		$attendee_types                 = array_values( tribe_attendees()->attendee_types() );
		$attendee_types_in              = DB::prepare(
			implode( ',', array_fill( 0, count( $attendee_types ), '%s' ) ),
			...$attendee_types
		);
		$attendee_to_event_meta_keys    = array_values( tribe_attendees()->attendee_to_event_keys() );
		$attendee_to_event_meta_keys_in = DB::prepare(
			implode( ',', array_fill( 0, count( $attendee_to_event_meta_keys ), '%s' ) ),
			...$attendee_to_event_meta_keys
		);
		global $wpdb;
		$ticket_post_types               = array_values( tribe_tickets()->ticket_types() );
		$ticket_post_types_in            = DB::prepare(
			implode( ',', array_fill( 0, count( $ticket_post_types ), '%s' ) ),
			...$ticket_post_types
		);
		$ticket_to_event_meta_keys       = array_values( tribe_tickets()->ticket_to_event_keys() );
		$ticket_to_event_meta_keys_in    = DB::prepare(
			implode( ',', array_fill( 0, count( $ticket_to_event_meta_keys ), '%s' ) ),
			...$ticket_to_event_meta_keys
		);
		$attendee_to_ticket_meta_keys    = array_values( tribe_attendees()->attendee_to_ticket_keys() );
		$attendee_to_ticket_meta_keys_in = DB::prepare(
			implode( ',', array_fill( 0, count( $attendee_to_ticket_meta_keys ), '%s' ) ),
			...$attendee_to_ticket_meta_keys
		);
		$seat_type_attendees_count       = DB::get_var(
			DB::prepare(
				"SELECT COUNT(*) FROM %i AS attendee
				JOIN %i AS attendee_for_event ON attendee.ID = attendee_for_event.post_id
					AND attendee_for_event.meta_key in ({$attendee_to_event_meta_keys_in})
				JOIN %i AS attendee_for_ticket ON attendee.ID = attendee_for_ticket.post_id
					AND attendee_for_ticket.meta_key in ({$attendee_to_ticket_meta_keys_in})
				WHERE attendee.post_type IN ({$attendee_types_in})
				AND attendee.post_status != 'trash'
				AND attendee_for_event.meta_value = %d
				AND attendee_for_ticket.meta_value IN (
					SELECT ID FROM %i ticket
					JOIN %i ticket_for_event ON ticket.ID = ticket_for_event.post_id
						AND meta_key in ({$ticket_to_event_meta_keys_in})
					JOIN %i ticket_for_seat_type ON ticket.ID = ticket_for_seat_type.post_id
						AND ticket_for_seat_type.meta_key = %s
					WHERE ticket.post_type IN ({$ticket_post_types_in})
					AND ticket_for_event.meta_value = %d
					AND ticket_for_seat_type.meta_value = %s
				)",
				$wpdb->posts,
				$wpdb->postmeta,
				$wpdb->postmeta,
				$post_id,
				$wpdb->posts,
				$wpdb->postmeta,
				$wpdb->postmeta,
				Meta::META_KEY_SEAT_TYPE,
				$post_id,
				$seat_type
			)
		);

		$cache->set( $cache_key, $seat_type_attendees_count, DAY_IN_SECONDS, Triggers::TRIGGER_SAVE_POST );

		return (int) $seat_type_attendees_count;
	}
}
