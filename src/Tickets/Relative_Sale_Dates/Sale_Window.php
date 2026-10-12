<?php
/**
 * Resolves a sales or sale price window rule into dates for an event.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Tribe__Timezones as Timezones;

/**
 * Turns a rule plus an event into the start and end of its window.
 *
 * The dates are resolved on the wall clock of the event timezone. In a timezone east of UTC, a time that happens twice
 * when the clocks go back resolves to its first occurrence, as the editors' JavaScript does, while the sales actions
 * are scheduled from the stored `Y-m-d H:i:s` strings, which PHP reads as the second: inside that repeated hour the
 * judged and the scheduled instant can be an hour apart.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Sale_Window {
	/**
	 * Resolves a rule against the dates of an event, read in the event timezone.
	 *
	 * A Now start of the sales window puts the ticket on sale: it moves a ticket start that is later than now to now, and
	 * leaves a start that has already passed, or no start, to the ticket, so saving a ticket on sale again does not move
	 * its start.
	 *
	 * @since TBD
	 *
	 * @param Rule   $rule         The rule.
	 * @param int    $event_id     The event post ID.
	 * @param string $ticket_start The start the ticket is saved with, as `Y-m-d H:i:s` in the event timezone, or `''`.
	 *
	 * @return Resolved_Window|null The resolved window, or `null` when the event has no valid dates.
	 */
	public function resolve_for_event( Rule $rule, int $event_id, string $ticket_start = '' ): ?Resolved_Window {
		$event_dates = $this->get_event_dates( $event_id );

		if ( ! $event_dates ) {
			return null;
		}

		$window = $this->resolve( $rule, ...$event_dates );

		if ( ! $rule->get_kind()->moves_open_start_to_now() || ! $rule->opens_at_once() ) {
			return $window;
		}

		$now_start = $this->get_now_start( $ticket_start, $event_dates[0]->getTimezone() );

		return $now_start ? new Resolved_Window( $now_start, $window->get_end(), $window->get_timezone() ) : $window;
	}

	/**
	 * Gets the event's start and end in the event timezone.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event post ID.
	 *
	 * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}|null The event start and end, or `null` when the event has no valid dates.
	 */
	public function get_event_dates( int $event_id ): ?array {
		$start = get_post_meta( $event_id, '_EventStartDate', true );
		$end   = get_post_meta( $event_id, '_EventEndDate', true );

		if ( ! is_string( $start ) || '' === $start || ! is_string( $end ) || '' === $end ) {
			return null;
		}

		$timezone = Timezones::build_timezone_object( get_post_meta( $event_id, '_EventTimezone', true ) ?: null );

		try {
			return [ new DateTimeImmutable( $start, $timezone ), new DateTimeImmutable( $end, $timezone ) ];
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Resolves a rule against an event's start and end, both in the event timezone.
	 *
	 * A start that opens the window at once has no date of its own, and a default end closes it when the event starts.
	 *
	 * @since TBD
	 *
	 * @param Rule              $rule        The rule.
	 * @param DateTimeImmutable $event_start The event start, in the event's named timezone: only a named timezone, such as `Europe/Athens`, knows its clock changes.
	 * @param DateTimeImmutable $event_end   The event end, in the same timezone.
	 *
	 * @return Resolved_Window The resolved window.
	 */
	private function resolve( Rule $rule, DateTimeImmutable $event_start, DateTimeImmutable $event_end ): Resolved_Window {
		$anchors = [
			Rule::ANCHOR_START => $event_start,
			Rule::ANCHOR_END   => $event_end,
		];

		return new Resolved_Window(
			$this->resolve_boundary( $rule->get_start(), $anchors, null ),
			$this->resolve_boundary( $rule->get_end(), $anchors, $anchors[ Rule::ANCHOR_START ] ),
			$event_start->getTimezone()
		);
	}

	/**
	 * Resolves one boundary of the window.
	 *
	 * @since TBD
	 *
	 * @param Boundary                                                $boundary     The boundary to resolve.
	 * @param array{start: DateTimeImmutable, end: DateTimeImmutable} $anchors      The event start and end, in the event timezone.
	 * @param DateTimeImmutable|null                                  $default_date The date a default boundary resolves to.
	 *
	 * @return DateTimeImmutable|null The resolved date in the event timezone, or `null` when the boundary has no date.
	 */
	private function resolve_boundary( Boundary $boundary, array $anchors, ?DateTimeImmutable $default_date ): ?DateTimeImmutable {
		if ( Rule::MODE_DEFAULT === $boundary->get_mode() ) {
			return $default_date;
		}

		$anchor   = $boundary->get_anchor();
		$interval = $boundary->get_interval();

		// Only a relative boundary has both: a specific one keeps the ticket's own date, and a Now start opens the window.
		if ( null === $anchor || null === $interval ) {
			return null;
		}

		return $this->before( $anchors[ $anchor ], $interval );
	}

	/**
	 * Moves a date back by an interval of wall-clock time, in its own timezone.
	 *
	 * The interval comes off the wall-clock time, not off the instant, so a clock change in between does not shift the
	 * result: 8 hours before 08:00 is 00:00 even on a night an hour longer or shorter than usual.
	 *
	 * A number of minutes or hours that lands on a time skipped by a clock change comes off the instant instead, as the
	 * next valid time can fall on or after the anchor: 30 minutes before 03:15 would otherwise be 03:45.
	 *
	 * @since TBD
	 *
	 * @param DateTimeImmutable $anchor   The date to move back.
	 * @param DateInterval      $interval How far to move it back.
	 *
	 * @return DateTimeImmutable The moved date, in the anchor's timezone.
	 */
	private function before( DateTimeImmutable $anchor, DateInterval $interval ): DateTimeImmutable {
		// UTC has no clock change, so the interval moves the wall-clock time and nothing else.
		$wall_clock = ( new DateTimeImmutable( $anchor->format( 'Y-m-d H:i:s' ), new DateTimeZone( 'UTC' ) ) )
			->sub( $interval )
			->format( 'Y-m-d H:i:s' );
		$date       = $this->at_wall_clock( $wall_clock, $anchor->getTimezone() );

		// Days and weeks keep their wall-clock time: a skipped time a day or more back still lands well before the anchor.
		if ( $interval->d || $date->format( 'Y-m-d H:i:s' ) === $wall_clock ) {
			return $date;
		}

		$seconds = ( new DateTimeImmutable( '@0' ) )->add( $interval )->getTimestamp();

		return ( new DateTimeImmutable( '@' . ( $anchor->getTimestamp() - $seconds ) ) )->setTimezone( $anchor->getTimezone() );
	}

	/**
	 * Returns the date a wall-clock time falls on in a timezone.
	 *
	 * A time skipped by a clock change moves forward by the size of the change. A time that happens twice takes its
	 * first occurrence, as the editors' JavaScript does: PHP alone takes the first west of UTC and the second east of it.
	 *
	 * @since TBD
	 *
	 * @param string       $wall_clock The wall-clock time, in `Y-m-d H:i:s` format.
	 * @param DateTimeZone $timezone   The timezone.
	 *
	 * @return DateTimeImmutable The date, in the given timezone.
	 */
	private function at_wall_clock( string $wall_clock, DateTimeZone $timezone ): DateTimeImmutable {
		$date        = new DateTimeImmutable( $wall_clock, $timezone );
		$as_utc      = ( new DateTimeImmutable( $wall_clock, new DateTimeZone( 'UTC' ) ) )->getTimestamp();
		$transitions = $timezone->getTransitions( $as_utc - DAY_IN_SECONDS, $as_utc + DAY_IN_SECONDS );

		// A fixed offset has no transitions, and no time that happens twice.
		foreach ( is_array( $transitions ) ? $transitions : [] as $transition ) {
			// Not `setTimestamp()`: on PHP 7.4 it snaps back to the occurrence the date already holds.
			$candidate = ( new DateTimeImmutable( '@' . ( $as_utc - $transition['offset'] ) ) )->setTimezone( $timezone );

			if ( $candidate < $date && $candidate->format( 'Y-m-d H:i:s' ) === $wall_clock ) {
				$date = $candidate;
			}
		}

		return $date;
	}

	/**
	 * Gets the start a Now boundary moves the ticket to: now, when the ticket start is later.
	 *
	 * @since TBD
	 *
	 * @param string       $ticket_start The start the ticket is saved with, as `Y-m-d H:i:s` in the event timezone, or `''`.
	 * @param DateTimeZone $timezone     The event timezone.
	 *
	 * @return DateTimeImmutable|null Now, in the event timezone, or `null` to leave the start to the ticket.
	 */
	private function get_now_start( string $ticket_start, DateTimeZone $timezone ): ?DateTimeImmutable {
		if ( '' === $ticket_start ) {
			return null;
		}

		try {
			$start = new DateTimeImmutable( $ticket_start, $timezone );
		} catch ( Exception $e ) {
			return null;
		}

		$now = new DateTimeImmutable( 'now', $timezone );

		return $start > $now ? $now : null;
	}
}
