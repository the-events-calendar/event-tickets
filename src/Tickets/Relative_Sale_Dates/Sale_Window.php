<?php
/**
 * Resolves a sales window rule into sale dates for an event.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Turns a rule plus an event's start and end into the sales start and end.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Sale_Window {
	/**
	 * Resolves a rule against an event's dates.
	 *
	 * The event timezone is the event start's timezone; the event end is converted to it.
	 *
	 * @since TBD
	 *
	 * @param Rule              $rule        The sales window rule.
	 * @param DateTimeInterface $event_start The event start, in the event timezone. Build it with the event's named timezone, such as `Europe/Athens`, not a UTC offset or abbreviation parsed from a date string: only a named timezone knows its clock changes.
	 * @param DateTimeInterface $event_end   The event end.
	 *
	 * @return Resolved_Window The resolved sales window.
	 */
	public function resolve( Rule $rule, DateTimeInterface $event_start, DateTimeInterface $event_end ): Resolved_Window {
		$timezone = $event_start->getTimezone();
		$anchors  = [
			Rule::ANCHOR_START => $this->in_timezone( $event_start, $timezone ),
			Rule::ANCHOR_END   => $this->in_timezone( $event_end, $timezone ),
		];

		return new Resolved_Window(
			$this->resolve_boundary( $rule->get_start(), $anchors, null ),
			$this->resolve_boundary( $rule->get_end(), $anchors, $anchors[ Rule::ANCHOR_START ] )
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

		if ( Rule::MODE_RELATIVE === $boundary->get_mode() ) {
			return $this->before( $anchors[ $boundary->get_anchor() ], $boundary->get_interval() );
		}

		// A specific boundary keeps the ticket's own date.
		return null;
	}

	/**
	 * Moves a date back by an interval of wall-clock time, in its own timezone.
	 *
	 * The interval comes off the wall-clock time, not off the instant, so a clock change in between does not shift the
	 * result: 8 hours before 08:00 is 00:00 even on a night an hour longer or shorter than usual.
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

		return $this->at_wall_clock( $wall_clock, $anchor->getTimezone() );
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
	 * Returns a date as an immutable date in the given timezone.
	 *
	 * @since TBD
	 *
	 * @param DateTimeInterface $date     The date to convert.
	 * @param DateTimeZone      $timezone The timezone to convert to.
	 *
	 * @return DateTimeImmutable The same instant, in the given timezone.
	 */
	private function in_timezone( DateTimeInterface $date, DateTimeZone $timezone ): DateTimeImmutable {
		return ( new DateTimeImmutable( '@' . $date->getTimestamp() ) )->setTimezone( $timezone );
	}
}
