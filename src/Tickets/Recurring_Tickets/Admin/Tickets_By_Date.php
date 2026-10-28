<?php
/**
 * The Tickets by Date section's data of a recurring event.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */

namespace TEC\Tickets\Recurring_Tickets\Admin;

use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Tickets\Commerce\Reports\Orders as Orders_Report;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Recurring_Tickets\Hydrator;
use TEC\Tickets\Recurring_Tickets\Models\Ticket;
use TEC\Tickets\Recurring_Tickets\Price;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;

/**
 * Lists every date of a recurring event with its recurring event tickets as they stand on that date.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */
final class Tickets_By_Date {
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
	 * Tickets_By_Date constructor.
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
	 * Returns the section's data of an event, as of its last save.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event.
	 *
	 * @return array{counts: array{upcoming: int, all: int}, dates: array<int,array{id: int, label: string, time: string, past: bool, capacity: int, available: int, attendees_url: string, orders_url: string, tickets: array<int,array<string,mixed>>}>}|null The data, null when the event has no recurring event ticket.
	 */
	public function for_event( int $event_id ): ?array {
		$rows_by_date = $this->rows_by_date( $event_id );

		if ( ! $rows_by_date ) {
			return null;
		}

		$attendees_url = tribe( 'tickets.attendees' )->get_report_link( get_post( $event_id ) );
		$orders_url    = tribe( Orders_Report::class )->get_event_link( $event_id, true );
		$now           = gmdate( 'Y-m-d H:i:s' );
		$dates         = [];
		$upcoming      = 0;

		foreach ( Occurrence::where( 'post_id', $event_id )->order_by( 'start_date', 'ASC' )->all() as $occurrence ) {
			$rows = $rows_by_date[ (int) $occurrence->occurrence_id ] ?? [];

			if ( ! $rows ) {
				continue;
			}

			$date_id = $this->hydrator->event_id( reset( $rows ) );
			$past    = (string) $occurrence->end_date_utc < $now;
			$tickets = array_map( fn( Ticket $row ) => $this->ticket( $row, $event_id ), $rows );

			$upcoming += $past ? 0 : 1;
			$dates[]   = array_merge(
				$this->when( (string) $occurrence->start_date, (string) $occurrence->end_date, tribe_event_is_all_day( $event_id ) ),
				[
					'id'            => $date_id,
					'past'          => $past,
					'capacity'      => $this->total( array_column( $tickets, 'capacity' ) ),
					'available'     => $this->total( array_column( $tickets, 'available' ) ),
					'attendees_url' => add_query_arg( Date_Filter::NAME, $date_id, $attendees_url ),
					'orders_url'    => add_query_arg( Date_Filter::NAME, $date_id, $orders_url ),
					'tickets'       => array_values( $tickets ),
				]
			);
		}

		return [
			'counts' => [
				'upcoming' => $upcoming,
				'all'      => count( $dates ),
			],
			'dates'  => $dates,
		];
	}

	/**
	 * Returns an event's published rows by occurrence, each date's in the tickets' order.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event.
	 *
	 * @return array<int,Ticket[]> The rows, by occurrence ID.
	 */
	private function rows_by_date( int $event_id ): array {
		$rows = array_filter( $this->rows->get_by_post( $event_id ), static fn( Ticket $row ) => 'publish' === $row->status );
		usort( $rows, static fn( Ticket $a, Ticket $b ) => (int) $a->menu_order <=> (int) $b->menu_order );

		$by_date = [];
		foreach ( $rows as $row ) {
			$by_date[ (int) $row->occurrence_id ][] = $row;
		}

		return $by_date;
	}

	/**
	 * Returns a row as the section lists it.
	 *
	 * @since TBD
	 *
	 * @param Ticket $row      The row.
	 * @param int    $event_id The event.
	 *
	 * @return array{id: int, name: string, price: string, regular_price: string|null, capacity: int, available: int, sales_window: string, fields: string[]} The ticket; capacity and availability are -1 when unlimited.
	 */
	private function ticket( Ticket $row, int $event_id ): array {
		$ticket    = $this->hydrator->hydrate( $row );
		$regular   = Price::to_decimal( (int) $row->price );
		$unlimited = -1 === (int) $row->capacity;

		/**
		 * Filters the attendee registration fields named under a recurring event ticket in Tickets by Date.
		 *
		 * @since TBD
		 *
		 * @param string[] $fields      The fields' labels.
		 * @param int      $template_id The recurring event ticket.
		 * @param int      $event_id    The event.
		 */
		$fields = (array) apply_filters( 'tec_tickets_recurring_tickets_by_date_ticket_fields', [], (int) $row->parent_id, $event_id );

		return [
			'id'            => (int) $ticket->ID,
			'name'          => (string) $ticket->name,
			'price'         => Value::create( $ticket->price )->get_currency(),
			'regular_price' => (float) $ticket->price < (float) $regular ? Value::create( $regular )->get_currency() : null,
			'capacity'      => $unlimited ? -1 : (int) $row->capacity,
			'available'     => $unlimited ? -1 : max( 0, (int) $row->stock ),
			'sales_window'  => $this->sales_window( $row ),
			'fields'        => array_values( array_map( 'strval', $fields ) ),
		];
	}

	/**
	 * Returns a date's label and time: the date and its start time, the date and "All day", or the dates of a
	 * multi-day date with no time.
	 *
	 * @since TBD
	 *
	 * @param string $start   The event-local start.
	 * @param string $end     The event-local end.
	 * @param bool   $all_day Whether the event is all day.
	 *
	 * @return array{label: string, time: string} The label and the time.
	 */
	private function when( string $start, string $end, bool $all_day ): array {
		$date_format = tribe_get_date_format( true );

		if ( substr( $start, 0, 10 ) !== substr( $end, 0, 10 ) && ! ( $all_day && $this->ends_at_midnight( $start, $end ) ) ) {
			return [
				'label' => sprintf( '%1$s - %2$s', tribe_format_date( $start, false, $date_format ), tribe_format_date( $end, false, $date_format ) ),
				'time'  => '',
			];
		}

		return [
			'label' => tribe_format_date( $start, false, $date_format ),
			'time'  => $all_day ? __( 'All day', 'event-tickets' ) : tribe_format_date( $start, false, tribe_get_time_format() ),
		];
	}

	/**
	 * Whether an all-day date ends on the day after it starts at midnight, as TEC can store one day.
	 *
	 * @since TBD
	 *
	 * @param string $start The event-local start.
	 * @param string $end   The event-local end.
	 *
	 * @return bool Whether the date is one day.
	 */
	private function ends_at_midnight( string $start, string $end ): bool {
		return gmdate( 'Y-m-d', strtotime( substr( $start, 0, 10 ) . ' +1 day' ) ) === substr( $end, 0, 10 ) && '00:00:00' === substr( $end, 11, 8 );
	}

	/**
	 * Returns a row's sales window, e.g. "June 1, 2026 - September 16, 2026"; empty without dates.
	 *
	 * @since TBD
	 *
	 * @param Ticket $row The row.
	 *
	 * @return string The sales window.
	 */
	private function sales_window( Ticket $row ): string {
		$window = $this->hydrator->sale_window( $row );
		$format = tribe_get_date_format( true );
		$start  = '' === $window['start_date'] ? '' : tribe_format_date( $window['start_date'], false, $format );
		$end    = '' === $window['end_date'] ? '' : tribe_format_date( $window['end_date'], false, $format );

		return trim( "{$start} - {$end}", ' -' );
	}

	/**
	 * Adds up capacities or availabilities, -1 when one is unlimited.
	 *
	 * @since TBD
	 *
	 * @param int[] $values The values.
	 *
	 * @return int The total.
	 */
	private function total( array $values ): int {
		return in_array( -1, $values, true ) ? -1 : (int) array_sum( $values );
	}
}
