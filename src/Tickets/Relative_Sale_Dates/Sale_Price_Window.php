<?php
/**
 * Resolves a sale price rule into sale price dates for an event.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use DateTimeInterface;
use Tribe__Date_Utils as Dates;

/**
 * Turns a sale price rule plus an event's start and end into the sale price start and end dates.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Sale_Price_Window {
	/**
	 * The sales window resolver, which also does the date arithmetic of the sale price.
	 *
	 * @since TBD
	 *
	 * @var Sale_Window
	 */
	private Sale_Window $sale_window;

	/**
	 * Sale_Price_Window constructor.
	 *
	 * @since TBD
	 *
	 * @param Sale_Window $sale_window The sales window resolver.
	 */
	public function __construct( Sale_Window $sale_window ) {
		$this->sale_window = $sale_window;
	}

	/**
	 * Resolves a sale price rule against an event's dates, into the values of the ticket's sale price date metas.
	 *
	 * A *now* start is an empty date, which the on-sale check reads as already started: the ticket itself cannot be
	 * bought before its sales window opens.
	 *
	 * @since TBD
	 *
	 * @param Sale_Price_Rule   $rule        The sale price rule.
	 * @param DateTimeInterface $event_start The event start, in the event timezone, built with its named timezone as for `Sale_Window::resolve()`.
	 * @param DateTimeInterface $event_end   The event end.
	 *
	 * @return array{start: ?string, end: ?string} Each date as `Y-m-d` in the event timezone, `''` for a *now* start, or `null` for a specific boundary, whose submitted date stays.
	 */
	public function resolve( Sale_Price_Rule $rule, DateTimeInterface $event_start, DateTimeInterface $event_end ): array {
		$start = Sale_Price_Rule::MODE_NOW === $rule->get_start()->get_mode()
			? ''
			: $this->resolve_date( $rule->get_start(), $event_start, $event_end );

		return [
			'start' => $start,
			'end'   => $this->resolve_date( $rule->get_end(), $event_start, $event_end ),
		];
	}

	/**
	 * Resolves one boundary of the sale price window into a date.
	 *
	 * @since TBD
	 *
	 * @param Sale_Price_Boundary $boundary    The boundary to resolve.
	 * @param DateTimeInterface   $event_start The event start, in the event timezone.
	 * @param DateTimeInterface   $event_end   The event end.
	 *
	 * @return string|null The date as `Y-m-d` in the event timezone, or `null` when the boundary is not relative.
	 */
	private function resolve_date( Sale_Price_Boundary $boundary, DateTimeInterface $event_start, DateTimeInterface $event_end ): ?string {
		$relative = $boundary->to_boundary();
		$date     = $relative ? $this->sale_window->resolve_relative( $relative, $event_start, $event_end ) : null;

		return $date ? $date->format( Dates::DBDATEFORMAT ) : null;
	}
}
