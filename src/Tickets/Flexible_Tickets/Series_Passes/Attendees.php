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
	public function prevent_series_pass_attendee_checkin( $checkin, int $attendee_id ) {
		if ( tribe_attendees()->where( 'id', $attendee_id )->where( 'ticket_type', Series_Passes::TICKET_TYPE )->count() === 0 ) {
			return $checkin;
		}

		return false;
	}
}