<?php
/**
 * The sale dates a rule resolves to for one event.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use DateTimeImmutable;
use DateTimeZone;

/**
 * An immutable resolved sales window. An empty end means the resolver has no date for it.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Resolved_Window {
	/**
	 * The sales start in the event timezone, or `null` when there is no date for it.
	 *
	 * @since TBD
	 *
	 * @var DateTimeImmutable|null
	 */
	private ?DateTimeImmutable $start;

	/**
	 * The sales end in the event timezone, or `null` when there is no date for it.
	 *
	 * @since TBD
	 *
	 * @var DateTimeImmutable|null
	 */
	private ?DateTimeImmutable $end;

	/**
	 * Resolved_Window constructor.
	 *
	 * @since TBD
	 *
	 * @param DateTimeImmutable|null $start The sales start in the event timezone, or `null` when there is no date for it.
	 * @param DateTimeImmutable|null $end   The sales end in the event timezone, or `null` when there is no date for it.
	 */
	public function __construct( ?DateTimeImmutable $start, ?DateTimeImmutable $end ) {
		$this->start = $start;
		$this->end   = $end;
	}

	/**
	 * Gets the sales start in the event timezone.
	 *
	 * @since TBD
	 *
	 * @return DateTimeImmutable|null The sales start, or `null` when sales open at once or keep the ticket's own date.
	 */
	public function get_start(): ?DateTimeImmutable {
		return $this->start;
	}

	/**
	 * Gets the sales start in UTC.
	 *
	 * @since TBD
	 *
	 * @return DateTimeImmutable|null The sales start, or `null` when sales open at once or keep the ticket's own date.
	 */
	public function get_start_utc(): ?DateTimeImmutable {
		return $this->start ? $this->start->setTimezone( new DateTimeZone( 'UTC' ) ) : null;
	}

	/**
	 * Gets the sales end in the event timezone.
	 *
	 * @since TBD
	 *
	 * @return DateTimeImmutable|null The sales end, or `null` when the ticket keeps its own date.
	 */
	public function get_end(): ?DateTimeImmutable {
		return $this->end;
	}

	/**
	 * Gets the sales end in UTC.
	 *
	 * @since TBD
	 *
	 * @return DateTimeImmutable|null The sales end, or `null` when the ticket keeps its own date.
	 */
	public function get_end_utc(): ?DateTimeImmutable {
		return $this->end ? $this->end->setTimezone( new DateTimeZone( 'UTC' ) ) : null;
	}

	/**
	 * Returns whether the window is valid.
	 *
	 * Without both dates the window cannot be judged here, so it is reported valid; the caller checks it once the
	 * ticket's own dates are filled in.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the sales start is before the sales end.
	 */
	public function is_valid(): bool {
		if ( ! $this->start || ! $this->end ) {
			return true;
		}

		return $this->start < $this->end;
	}
}
