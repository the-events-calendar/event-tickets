<?php
/**
 * Sends a recurring event date's Attendees and Orders pages to its event's.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */

namespace TEC\Tickets\Recurring_Tickets\Admin;

/**
 * Redirects the Attendees or Orders page of a date of a recurring event with recurring event tickets to the event's,
 * which lists every date and narrows to one with its date filter.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */
final class Event_Pages {
	/**
	 * The pages redirected.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	private const PAGES = [ 'tickets-attendees', 'tickets-commerce-orders' ];

	/**
	 * The request variables the pages read their post from.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	private const POST_VARS = [ 'event_id', 'post_id' ];

	/**
	 * The event's dates.
	 *
	 * @since TBD
	 *
	 * @var Event_Dates
	 */
	private Event_Dates $event_dates;

	/**
	 * Event_Pages constructor.
	 *
	 * @since TBD
	 *
	 * @param Event_Dates $event_dates The event's dates.
	 */
	public function __construct( Event_Dates $event_dates ) {
		$this->event_dates = $event_dates;
	}

	/**
	 * Redirects a date's Attendees or Orders page to its event's, unfiltered.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function redirect_date_to_event(): void {
		if ( ! in_array( tec_get_request_var( 'page' ), self::PAGES, true ) ) {
			return;
		}

		foreach ( self::POST_VARS as $var ) {
			$post_id = absint( tec_get_request_var( $var ) );

			if ( ! $post_id ) {
				continue;
			}

			$event_id = $this->event_dates->event_of( $post_id );

			if ( $event_id === $post_id || ! $this->event_dates->all( $event_id ) ) {
				return;
			}

			wp_safe_redirect( add_query_arg( $var, $event_id ) );
			tribe_exit();

			return;
		}
	}
}
