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
	 * @param DateTimeInterface $event_start The event start, in the event timezone.
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

		$start = $rule->get_start();
		$end   = $rule->get_end();

		return new Resolved_Window(
			Rule::MODE_RELATIVE === $start['mode'] ? $this->resolve_relative( $start, $anchors ) : null,
			Rule::MODE_DEFAULT === $end['mode'] ? $anchors[ Rule::ANCHOR_START ] : $this->resolve_relative( $end, $anchors )
		);
	}

	/**
	 * Resolves one end of the window, or returns `null` when the end is not relative.
	 *
	 * @since TBD
	 *
	 * @param array{mode: string, value?: int, unit?: int, anchor?: string} $end     The end of the window.
	 * @param array{start: DateTimeImmutable, end: DateTimeImmutable}       $anchors The event start and end, in the event timezone.
	 *
	 * @return DateTimeImmutable|null The resolved date in the event timezone, or `null` when the end is not relative.
	 */
	private function resolve_relative( array $end, array $anchors ): ?DateTimeImmutable {
		if ( Rule::MODE_RELATIVE !== $end['mode'] ) {
			return null;
		}

		$anchor = $anchors[ $end['anchor'] ];

		if ( Rule::UNIT_DAYS === $end['unit'] || Rule::UNIT_WEEKS === $end['unit'] ) {
			$days = Rule::UNIT_WEEKS === $end['unit'] ? $end['value'] * 7 : $end['value'];

			return $this->days_before( $anchor, $days );
		}

		$timestamp = $anchor->getTimestamp() - $end['value'] * $end['unit'];

		return ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $anchor->getTimezone() );
	}

	/**
	 * Moves a date back by calendar days in its own timezone, keeping its wall-clock time.
	 *
	 * The result is built fresh from the target date and the anchor's wall-clock time rather than with `sub()`: when the
	 * target wall-clock time exists twice, `sub()` keeps the anchor's UTC offset and can land on the second occurrence,
	 * while a fresh date always takes the first one, as the browser calculation does.
	 *
	 * @since TBD
	 *
	 * @param DateTimeImmutable $anchor The date to move back.
	 * @param int               $days   The number of calendar days to move back.
	 *
	 * @return DateTimeImmutable The moved date, in the anchor's timezone.
	 */
	private function days_before( DateTimeImmutable $anchor, int $days ): DateTimeImmutable {
		// A date at midnight UTC has no clock change to trip over.
		$target_date = ( new DateTimeImmutable( $anchor->format( 'Y-m-d' ), new DateTimeZone( 'UTC' ) ) )
			->modify( "-{$days} days" )
			->format( 'Y-m-d' );

		return new DateTimeImmutable( $target_date . ' ' . $anchor->format( 'H:i:s' ), $anchor->getTimezone() );
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
