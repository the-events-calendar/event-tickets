<?php
/**
 * Handles all of the event querying in the context of Series Pass Emails.
 *
 * @since 5.8.4
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Emails;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes\Emails;

use WP_Post;

/**
 * Class Upcoming_Events.
 *
 * @since 5.8.4
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Emails;
 */
class Upcoming_Events {
	/**
	 * Fetches up to limit number of upcoming Events part of the Series the Email is being sent for.
	 *
	 * @since 5.8.4
	 *
	 * @param int $series_id The Series ID.
	 *
	 * @return array{0: array<WP_Post>, 1: int} The fetched Event post objects, the number of total Events found.
	 */
	public function fetch( int $series_id ): array {
		/**
		 * Filters the number of upcoming Events to show in the Series Pass Email Upcoming Events section.
		 * Returning an empty value, e.g. `0` or `false`, will never print the Upcoming Events section in the Email.
		 *
		 * @since 5.8.4
		 *
		 * @param int $num_events_to_show The number of upcoming Events to show in the Series Pass Email Upcoming Events section.
		 * @param int $series_id          The series the upcoming Events list is being printed for.
		 */
		$num_events_to_show = apply_filters(
			'tec_tickets_flexible_tickets_series_pass_email_upcoming_events_list_count',
			5,
			$series_id
		);

		if ( empty( $num_events_to_show ) ) {
			return [ [], 0 ];
		}

		/**
		 * Filters the Upcoming Events for a Series in the context of a Series Email before the default logic runs.
		 * Returning a non-null value from this filter will override the default logic and return the filtered result.
		 *
		 * The value returned by this filter is not memoized.
		 *
		 * @since 5.8.4
		 *
		 * @param array{0: array<int>, 1: int}|null $fetched   The fetched Event IDs, the number of total Events found.
		 * @param int                               $series_id The series the upcoming Events list is being printed for.
		 * @param int                               $limit     The limit to the number of Upcoming Events to fetch.
		 */
		$fetched = apply_filters(
			'tec_tickets_flexible_tickets_series_pass_email_upcoming_events',
			null,
			$series_id,
			$num_events_to_show
		);

		if ( $fetched !== null ) {
			return $fetched;
		}

		$cache_key = __METHOD__ . '_' . $series_id . '_' . $num_events_to_show;

		$cache  = tribe_cache();
		$cached = $cache[ $cache_key ];

		if (
			is_array( $cached )
			&& count( $cached ) === 2
			&& is_int( $cached[1] )
			&& array_filter( $cached[0], fn( $id ) => $id && is_int( $id ) )
		) {
			return $cached;
		}

		// Note the query should build for public events, not the ones the current user, likely an admin, can see.
		$orm     = tribe_events();
		$all_ids = $orm
			->where( 'series', $series_id )
			->where( 'starts_after', 'now' )
			->where( 'post_status', 'publish' )
			->set_found_rows( true )
			->per_page( $num_events_to_show )
			->get_ids( true );
		$events = iterator_to_array( $all_ids, false );
		$found = $orm->found();

		$cache[ $cache_key ] = [ $events, $found ];

		return [ $events, $found ];
	}
}
