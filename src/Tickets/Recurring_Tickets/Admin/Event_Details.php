<?php
/**
 * The details of a recurring event, atop its Attendees and Orders pages.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */

namespace TEC\Tickets\Recurring_Tickets\Admin;

use Tribe__Events__Event_Tickets__Main as TEC_Event_Tickets;

/**
 * Lists a recurring event's dates, in place of the single date The Events Calendar gives an event, and its venue.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */
final class Event_Details {
	/**
	 * How many dates show before the rest fold away.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private const SHOWN_DATES = 3;

	/**
	 * The event's dates.
	 *
	 * @since TBD
	 *
	 * @var Event_Dates
	 */
	private Event_Dates $event_dates;

	/**
	 * Event_Details constructor.
	 *
	 * @since TBD
	 *
	 * @param Event_Dates $event_dates The event's dates.
	 */
	public function __construct( Event_Dates $event_dates ) {
		$this->event_dates = $event_dates;
	}

	/**
	 * Prints a recurring event's dates and venue, in place of The Events Calendar's single date and venue.
	 *
	 * @since TBD
	 *
	 * @param int|mixed $event_id The event of the page, or one of its dates.
	 *
	 * @return void
	 */
	public function render( $event_id ): void {
		$event_id = $this->event_dates->event_of( (int) $event_id );
		$dates    = $this->event_dates->current( $event_id );

		if ( ! $dates ) {
			return;
		}

		// TEC prints the event's first date, which reads as the only one; this prints them all, and the venue it also prints.
		if ( class_exists( TEC_Event_Tickets::class ) ) {
			remove_action( current_action(), [ TEC_Event_Tickets::instance()->attendees_report(), 'event_details_top' ] );
		}

		$upcoming = $this->event_dates->upcoming( $dates );
		$listed   = array_map( static fn( string $start ) => tribe_format_date( $start, true, tribe_get_datetime_format( true ) ), array_values( $upcoming ?: $dates ) );

		tribe( 'tickets.admin.views' )->template(
			'recurring-tickets/event-details',
			[
				'dates_label' => $upcoming ? __( 'Upcoming dates:', 'event-tickets' ) : __( 'Dates:', 'event-tickets' ),
				'shown'       => array_slice( $listed, 0, self::SHOWN_DATES ),
				'folded'      => array_slice( $listed, self::SHOWN_DATES ),
				'venue'       => $this->venue( $event_id ),
			]
		);
	}

	/**
	 * Returns the event's venue, as The Events Calendar gives it on these pages.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event.
	 *
	 * @return array{name: string, edit_link: string|null}|null The venue, null when the event has none.
	 */
	private function venue( int $event_id ): ?array {
		if ( ! function_exists( 'tribe_has_venue' ) || ! tribe_has_venue( $event_id ) ) {
			return null;
		}

		$venue_id = tribe_get_venue_id( $event_id );

		return [
			'name'      => tribe_get_venue( $event_id ),
			/** This filter is documented in the-events-calendar/src/Tribe/Event_Tickets/Attendees_Report.php */
			'edit_link' => apply_filters( 'tribe_tickets_venue_action_links_edit_url', get_edit_post_link( $venue_id ), $venue_id ),
		];
	}
}
