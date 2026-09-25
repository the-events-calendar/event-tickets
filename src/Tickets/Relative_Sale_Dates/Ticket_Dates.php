<?php
/**
 * Resolves a ticket's rule against its event and writes the dates it resolves to.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use DateTimeImmutable;
use Exception;
use TEC\Tickets\Commerce\Ticket;
use Tribe__Date_Utils as Dates;
use Tribe__Timezones as Timezones;

/**
 * Reads an event's dates and writes a rule's resolved dates into the ticket date fields.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Ticket_Dates {
	/**
	 * The sales window resolver.
	 *
	 * @since TBD
	 *
	 * @var Sale_Window
	 */
	private Sale_Window $sale_window;

	/**
	 * Ticket_Dates constructor.
	 *
	 * @since TBD
	 *
	 * @param Sale_Window $sale_window The sales window resolver.
	 */
	public function __construct( Sale_Window $sale_window ) {
		$this->sale_window = $sale_window;
	}

	/**
	 * Resolves a rule against the event's dates.
	 *
	 * @since TBD
	 *
	 * @param int  $post_id The event post ID.
	 * @param Rule $rule    The sales window rule.
	 *
	 * @return Resolved_Window|null The resolved window, or `null` when the event has no valid dates.
	 */
	public function resolve( int $post_id, Rule $rule ): ?Resolved_Window {
		$event_dates = $this->get_event_dates( $post_id );

		return $event_dates ? $this->sale_window->resolve( $rule, ...$event_dates ) : null;
	}

	/**
	 * Gets the event's start and end in the event timezone.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The event post ID.
	 *
	 * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}|null The event start and end, or `null` when the event has no valid dates.
	 */
	public function get_event_dates( int $post_id ): ?array {
		$start = get_post_meta( $post_id, '_EventStartDate', true );
		$end   = get_post_meta( $post_id, '_EventEndDate', true );

		if ( ! is_string( $start ) || '' === $start || ! is_string( $end ) || '' === $end ) {
			return null;
		}

		$timezone = Timezones::build_timezone_object( get_post_meta( $post_id, '_EventTimezone', true ) ?: null );

		try {
			return [ new DateTimeImmutable( $start, $timezone ), new DateTimeImmutable( $end, $timezone ) ];
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Writes the dates a rule resolves to into the ticket's sale date fields.
	 *
	 * An end the rule does not resolve, `specific` or a `default` start, keeps the date the ticket has.
	 *
	 * @since TBD
	 *
	 * @param int  $ticket_id The ticket post ID.
	 * @param int  $post_id   The event post ID.
	 * @param Rule $rule      The ticket's sales window rule.
	 *
	 * @return void
	 */
	public function write( int $ticket_id, int $post_id, Rule $rule ): void {
		$window = $this->resolve( $post_id, $rule );

		if ( ! $window ) {
			return;
		}

		$start = $window->get_start();
		if ( $start ) {
			update_post_meta( $ticket_id, Ticket::START_DATE_META_KEY, $start->format( Dates::DBDATEFORMAT ) );
			update_post_meta( $ticket_id, Ticket::START_TIME_META_KEY, $start->format( Dates::DBTIMEFORMAT ) );
		}

		$end = $window->get_end();
		if ( $end ) {
			update_post_meta( $ticket_id, Ticket::END_DATE_META_KEY, $end->format( Dates::DBDATEFORMAT ) );
			update_post_meta( $ticket_id, Ticket::END_TIME_META_KEY, $end->format( Dates::DBTIMEFORMAT ) );
		}
	}
}
