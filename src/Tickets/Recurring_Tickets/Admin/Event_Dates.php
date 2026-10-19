<?php
/**
 * The dates of a recurring event, as its Attendees and Orders pages list them.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */

namespace TEC\Tickets\Recurring_Tickets\Admin;

use DateTimeInterface;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Event;
use TEC\Tickets\Recurring_Tickets\Commerce\Attendees;
use TEC\Tickets\Recurring_Tickets\Hydrator;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;

/**
 * Knows an event's dates: those with rows, those its attendees hold, which of them are removed and which are to come.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */
final class Event_Dates {
	/**
	 * The rows repository.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

	/**
	 * The row hydrator.
	 *
	 * @since TBD
	 *
	 * @var Hydrator
	 */
	private Hydrator $hydrator;

	/**
	 * The dates each event's attendees hold, by event: the date's start by its post ID.
	 *
	 * @since TBD
	 *
	 * @var array<int,array<int,string>>
	 */
	private array $held = [];

	/**
	 * The dates each event has rows on, by event: the date's start by its post ID.
	 *
	 * @since TBD
	 *
	 * @var array<int,array<int,string>>
	 */
	private array $current = [];

	/**
	 * Event_Dates constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows     $rows     The rows repository.
	 * @param Hydrator $hydrator The row hydrator.
	 */
	public function __construct( Rows $rows, Hydrator $hydrator ) {
		$this->rows     = $rows;
		$this->hydrator = $hydrator;
	}

	/**
	 * Returns the event a post ID stands for: the event of a date, the post itself otherwise.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID, an event's or one of its dates'.
	 *
	 * @return int The event's post ID.
	 */
	public function event_of( int $post_id ): int {
		return Event::filter_event_id( $post_id, 'recurring-tickets-dates' ) ?? $post_id;
	}

	/**
	 * Returns an event's dates: those with rows, and those its attendees hold, removed ones included; in start order.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event.
	 *
	 * @return array<int,string> The date's start, by its post ID.
	 */
	public function all( int $event_id ): array {
		$dates = $this->held( $event_id ) + $this->current( $event_id );
		asort( $dates );

		return $dates;
	}

	/**
	 * Returns the dates an event's attendees hold, removed ones included, in start order.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event.
	 *
	 * @return array<int,string> The date's start, by its post ID.
	 */
	public function held( int $event_id ): array {
		if ( $event_id <= 0 ) {
			return [];
		}

		if ( isset( $this->held[ $event_id ] ) ) {
			return $this->held[ $event_id ];
		}

		global $wpdb;
		$rows = DB::get_results(
			DB::prepare(
				"SELECT dates.meta_value AS date_id, MIN( starts.meta_value ) AS start FROM %i events JOIN %i dates ON dates.post_id = events.post_id AND dates.meta_key = '_tec_tickets_commerce_event' JOIN %i starts ON starts.post_id = events.post_id AND starts.meta_key = %s WHERE events.meta_key = %s AND events.meta_value = %s GROUP BY dates.meta_value ORDER BY start",
				$wpdb->postmeta,
				$wpdb->postmeta,
				$wpdb->postmeta,
				Attendees::OCCURRENCE_START_META_KEY,
				Attendees::POST_ID_META_KEY,
				(string) $event_id
			)
		);

		$this->held[ $event_id ] = array_column( (array) $rows, 'start', 'date_id' );

		return $this->held[ $event_id ];
	}

	/**
	 * Returns the dates an event has rows on, in start order.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event.
	 *
	 * @return array<int,string> The date's start, by its post ID.
	 */
	public function current( int $event_id ): array {
		if ( $event_id <= 0 ) {
			return [];
		}

		if ( isset( $this->current[ $event_id ] ) ) {
			return $this->current[ $event_id ];
		}

		$dates = [];
		foreach ( $this->rows->get_by_post( $event_id ) as $row ) {
			$date_id = $this->hydrator->event_id( $row );

			// Without ECP, a row names its event: not a date of it.
			if ( $date_id !== $event_id ) {
				$dates[ $date_id ] = $row->occurrence_start instanceof DateTimeInterface ? $row->occurrence_start->format( 'Y-m-d H:i:s' ) : (string) $row->occurrence_start;
			}
		}
		asort( $dates );

		$this->current[ $event_id ] = $dates;

		return $dates;
	}

	/**
	 * Returns the dates of an event that are gone, but that its attendees still hold.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event.
	 *
	 * @return int[] The dates' post IDs.
	 */
	public function removed( int $event_id ): array {
		return array_keys( array_diff_key( $this->held( $event_id ), $this->current( $event_id ) ) );
	}

	/**
	 * Returns the dates of a list that start now or later.
	 *
	 * @since TBD
	 *
	 * @param array<int,string> $dates The dates: the start by the date's post ID.
	 *
	 * @return array<int,string> The upcoming dates, in the same order.
	 */
	public function upcoming( array $dates ): array {
		$now = current_time( 'mysql' );

		return array_filter( $dates, static fn( string $start ) => $start >= $now );
	}
}
