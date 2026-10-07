<?php
/**
 * The orders of a recurring event's dates, on the event's Orders page.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */

namespace TEC\Tickets\Recurring_Tickets\Admin;

use DateTimeInterface;
use TEC\Tickets\Recurring_Tickets\Hydrator;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;

/**
 * Lists a recurring event's orders of every date on the event's Orders page and its export, shows each order's dates
 * and narrows the list to one.
 *
 * An order of a row holds its date's ID; the event's Orders page looks orders up by the event's own ID.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */
final class Orders_Page {
	/**
	 * The orders table's column of each order's dates.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const DATE_COLUMN = 'tec_tickets_recurring_date';

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
	 * The event's Attendees page, which knows the dates its attendees hold.
	 *
	 * @since TBD
	 *
	 * @var Attendees_Page
	 */
	private Attendees_Page $attendees_page;

	/**
	 * The select that narrows the table to one date.
	 *
	 * @since TBD
	 *
	 * @var Date_Filter
	 */
	private Date_Filter $date_filter;

	/**
	 * The dates of each event, by event: the date's start by its post ID.
	 *
	 * @since TBD
	 *
	 * @var array<int,array<int,string>>
	 */
	private array $dates = [];

	/**
	 * Orders_Page constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows           $rows           The rows repository.
	 * @param Hydrator       $hydrator       The row hydrator.
	 * @param Attendees_Page $attendees_page The event's Attendees page.
	 * @param Date_Filter    $date_filter    The select that narrows the table to one date.
	 */
	public function __construct( Rows $rows, Hydrator $hydrator, Attendees_Page $attendees_page, Date_Filter $date_filter ) {
		$this->rows           = $rows;
		$this->hydrator       = $hydrator;
		$this->attendees_page = $attendees_page;
		$this->date_filter    = $date_filter;
	}

	/**
	 * Looks an event's orders up by its dates too, or by the one chosen.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>|mixed $arguments The Orders page's, or its export's, query arguments.
	 *
	 * @return array<string,mixed> The arguments.
	 */
	public function filter_events( $arguments ): array {
		$arguments = (array) $arguments;
		$event_id  = is_numeric( $arguments['events'] ?? null ) ? (int) $arguments['events'] : 0;
		$dates     = $this->dates( $event_id );

		if ( ! $dates ) {
			return $arguments;
		}

		$chosen              = $this->date_filter->chosen( $dates );
		$arguments['events'] = $chosen ? [ $chosen ] : array_merge( [ $event_id ], array_keys( $dates ) );

		return $arguments;
	}

	/**
	 * Adds, after what was purchased, a column of each order's dates to an event with dates.
	 *
	 * @since TBD
	 *
	 * @param array<string,string>|mixed $columns  The orders table's columns.
	 * @param int|mixed                  $event_id The event.
	 *
	 * @return array<string,string> The columns.
	 */
	public function add_date_column( $columns, $event_id ): array {
		$columns = (array) $columns;

		if ( ! $this->dates( (int) $event_id ) ) {
			return $columns;
		}

		$position = array_search( 'purchased', array_keys( $columns ), true );
		$position = false === $position ? count( $columns ) : $position + 1;
		$column   = [ self::DATE_COLUMN => esc_html__( 'Event date', 'event-tickets' ) ];

		return array_slice( $columns, 0, $position, true ) + $column + array_slice( $columns, $position, null, true );
	}

	/**
	 * Returns an order's dates of the event, in the date column.
	 *
	 * @since TBD
	 *
	 * @param string|mixed $value  The cell's value.
	 * @param object|mixed $item   The order.
	 * @param string|mixed $column The column.
	 *
	 * @return string|mixed The cell's value.
	 */
	public function render_date_column( $value, $item, $column ) {
		if ( self::DATE_COLUMN !== $column ) {
			return $value;
		}

		$dates  = $this->dates( $this->page_event_id() );
		$labels = [];
		foreach ( (array) ( $item->events_in_order ?? [] ) as $date_id ) {
			if ( isset( $dates[ (int) $date_id ] ) ) {
				$labels[] = tribe_format_date( $dates[ (int) $date_id ], true );
			}
		}

		return esc_html( implode( ', ', $labels ) );
	}

	/**
	 * Adds, above the orders table, a select of the event's dates to narrow the list to one.
	 *
	 * @since TBD
	 *
	 * @param array<string,string[]>|mixed $nav   The table's nav items.
	 * @param string|mixed                 $which Where the nav is: `top` or `bottom`.
	 *
	 * @return array<string,string[]> The nav items.
	 */
	public function add_date_filter( $nav, $which ): array {
		$nav   = (array) $nav;
		$dates = 'top' === $which ? $this->dates( $this->page_event_id() ) : [];

		if ( $dates ) {
			$nav['left'][ Date_Filter::NAME ] = $this->date_filter->render( $dates );
		}

		return $nav;
	}

	/**
	 * Returns the event of the Orders page, as the page reads it.
	 *
	 * @since TBD
	 *
	 * @return int The event's post ID.
	 */
	private function page_event_id(): int {
		return (int) tribe_get_request_var( 'event_id', tribe_get_request_var( 'post_id', 0 ) );
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
	private function dates( int $event_id ): array {
		if ( $event_id <= 0 ) {
			return [];
		}

		if ( isset( $this->dates[ $event_id ] ) ) {
			return $this->dates[ $event_id ];
		}

		$dates = $this->attendees_page->held_dates( $event_id );
		foreach ( $this->rows->get_by_post( $event_id ) as $row ) {
			$start = $row->occurrence_start instanceof DateTimeInterface ? $row->occurrence_start->format( 'Y-m-d H:i:s' ) : (string) $row->occurrence_start;

			$dates[ $this->hydrator->event_id( $row ) ] = $start;
		}
		asort( $dates );

		$this->dates[ $event_id ] = $dates;

		return $dates;
	}
}
