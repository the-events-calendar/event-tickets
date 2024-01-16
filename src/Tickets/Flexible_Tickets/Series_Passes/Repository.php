<?php
/**
 * A pseudo-repository to run CRUD operations on Series Passes.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Repositories;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use TEC\Tickets\Flexible_Tickets\Enums\Ticket_To_Post_Relationship_Keys;
use WP_Post;

/**
 * Class Repository.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Repositories;
 */
class Repository {

	/**
	 * Given the ID of a Series Pass, returns the last Occurrence of the Series
	 *
	 * @since 5.8.0
	 *
	 * @param int $ticket_id The ID of the ticket.
	 *
	 * @return WP_Post|null The last occurrence of the series pass.
	 */
	public function get_last_occurrence_by_ticket( int $ticket_id ): ?WP_Post {
		// This is cached, fast call. Returns an array of arrays.
		$ticket_meta = get_post_meta( $ticket_id );

		// Search for the first matching meta key relating Tickets to Events.
		$found = array_filter(
			array_column(
				array_intersect_key(
					$ticket_meta,
					array_flip( Ticket_To_Post_Relationship_Keys::all() )
				),
				0
			)
		);

		if ( ! count( $found ) ) {
			return null;
		}

		$series_id = reset( $found );

		if ( empty( $series_id ) ) {
			return null;
		}

		$last = tribe_events()->where( 'series', $series_id )->order_by( 'event_date', 'DESC' )->per_page( 1 )->first();

		if ( ! $last instanceof WP_Post ) {
			return null;
		}

		return $last;
	}
}
