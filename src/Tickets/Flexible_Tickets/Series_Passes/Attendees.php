<?php
/**
 * Handles Attendees in the context of Series Passes.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use Tribe__Events__Main as TEC;

/**
 * Class Attendees.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes;
 */
class Attendees {
	/**
	 * Filter the Attendees table columns to remove the "Check-in" column when looking at Series Passes.
	 *
	 * @since TBD
	 *
	 * @param array<string,string> $columns  The columns to display in the Attendees table.
	 * @param int                  $event_id The ID of the event being displayed.
	 *
	 * @return array<string,string> The modified columns to display.
	 */
	public function filter_attendees_table_columns( array $columns, int $event_id ) {
		if ( get_post_type( $event_id ) !== Series_Post_Type::POSTTYPE ) {
			return $columns;
		}

		return array_diff_key( $columns, [ 'check_in' => true ] );
	}

	/**
	 * Filters the Attendee checkin to prevent Series Pass Attendees from being checked in.
	 *
	 * @since TBD
	 *
	 * @param mixed $checkin     Null by default, if not null, it will prevent the default checkin logic
	 *                           from firing.
	 * @param int   $attendee_id The post ID of the Attendee being checked in.
	 *
	 * @return bool|null Null to let the default checkin logic run, boolean value to prevent it.
	 */
	public function handle_series_pass_attendee_checkin( $checkin, int $attendee_id ) {
		if ( tribe_attendees()->where( 'id', $attendee_id )->where( 'ticket_type', Series_Passes::TICKET_TYPE )->count() === 0 ) {
			// Not an Attendee for a Series Pass, let the default logic run its course.
			return $checkin;
		}

		// We do not know the Attendee Ticket Provider and don't need to, just iterate over the possible ones.
		$series_id = null;
		foreach ( tribe_attendees()->attendee_to_event_keys() as $key ) {
			$series_id = (int) get_post_meta( $attendee_id, $key, true );

			if ( $series_id ) {
				break;
			}
		}

		if ( ! $series_id ) {
			// Weird, let the default checkin logic run.
			return $checkin;
		}

		// If the check-in window option is set, use that value.
		$time_buffer = (int) tribe_get_option( 'tickets-plus-qr-check-in-events-happening-now-time-buffer', 6 * HOUR_IN_SECONDS );

		/**
		 * Filters the time frame, in seconds, to look for Events part of a Series a Series Pass Attendee might be trying
		 * to check into.
		 *
		 * @since TBD
		 *
		 * @param int $time_buffer The time frame, in seconds; defaults to the check-in window value, or 6 hours if that
		 *                         is not set.
		 * @param int $series_id The post ID of the Series the Series Pass Atteende is related to.
		 * @param int $attendee_id The post ID of the Series Pass Attendee.
		 *
		 */
		$time_buffer = (int) apply_filters(
			'tec_tickets_flexible_tickets_series_checkin_time_buffer',
			$time_buffer,
			$series_id,
			$attendee_id
		);

		// Let's set up the time window to pull current and upcoming Events from.
		$now           = time();
		$starts_before = $now + $time_buffer;

		// How many Events are there in the current check-in window?
		$repository = tribe_events();
		$event      = $repository
			->where( 'series', $series_id )
			->where( 'ends_after', $now )
			->where( 'starts_before', $starts_before )
			->first();
		$count      = $repository->get_query()->found_posts;

		if ( $count === 0 ) {
			// Checking-in a Series Pass Attendee directly is not allowed.
			return false;
		}

		if ( $count === 1 ) {
			// Just clone the Attendee to the Event.
			$this->clone_attendee_to_event( $attendee_id, $event->ID );

			// We've handled the check-in correctly.
			return true;
		}

		$event_id = tribe_get_request_var('context_id');

		if ( ! ( $event_id && get_post_type( $event_id ) === TEC::POSTTYPE ) ) {
			// Cannot know the target Event to check the attendee in for: more inforation required.
			// @todo require more information.
			return false;
		}

		// The Series Pass Attendee will not be checked-in.
		return false;
	}
}