<?php
/**
 * The sale dates a rule resolves to for one event.
 *
 * @since 5.31.0
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use DateTimeImmutable;
use DateTimeZone;

/**
 * An immutable resolved sales window. A `null` start or end means the resolver has no date for it.
 *
 * @since 5.31.0
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Resolved_Window {
	/**
	 * The sales start in the event timezone, or `null` when there is no date for it.
	 *
	 * @since 5.31.0
	 *
	 * @var DateTimeImmutable|null
	 */
	private ?DateTimeImmutable $start;

	/**
	 * The sales end in the event timezone, or `null` when there is no date for it.
	 *
	 * @since 5.31.0
	 *
	 * @var DateTimeImmutable|null
	 */
	private ?DateTimeImmutable $end;

	/**
	 * The event timezone the window was resolved in.
	 *
	 * @since 5.31.0
	 *
	 * @var DateTimeZone
	 */
	private DateTimeZone $timezone;

	/**
	 * Resolved_Window constructor.
	 *
	 * @since 5.31.0
	 *
	 * @param DateTimeImmutable|null $start    The sales start in the event timezone, or `null` when there is no date for it.
	 * @param DateTimeImmutable|null $end      The sales end in the event timezone, or `null` when there is no date for it.
	 * @param DateTimeZone           $timezone The event timezone the window was resolved in.
	 */
	public function __construct( ?DateTimeImmutable $start, ?DateTimeImmutable $end, DateTimeZone $timezone ) {
		$this->start    = $start;
		$this->end      = $end;
		$this->timezone = $timezone;
	}

	/**
	 * Gets the sales start in the event timezone.
	 *
	 * @since 5.31.0
	 *
	 * @return DateTimeImmutable|null The sales start, or `null` when sales open at once or keep the ticket's own date.
	 */
	public function get_start(): ?DateTimeImmutable {
		return $this->start;
	}

	/**
	 * Gets the sales end in the event timezone.
	 *
	 * @since 5.31.0
	 *
	 * @return DateTimeImmutable|null The sales end, or `null` when the ticket keeps its own date.
	 */
	public function get_end(): ?DateTimeImmutable {
		return $this->end;
	}

	/**
	 * Gets the event timezone the window was resolved in, which its dates are written in.
	 *
	 * @since 5.31.0
	 *
	 * @return DateTimeZone The event timezone.
	 */
	public function get_timezone(): DateTimeZone {
		return $this->timezone;
	}
}
