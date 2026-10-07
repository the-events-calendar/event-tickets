<?php
/**
 * The attendees of a recurring event's dates, on the event's own pages.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */

namespace TEC\Tickets\Recurring_Tickets\Admin;

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Event;
use TEC\Tickets\Recurring_Tickets\Commerce\Attendees;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;

/**
 * Lists a recurring event's attendees of every date on the event, marks those whose date is gone, shows each one's
 * date and narrows the list to one, and gives each its date on My Tickets.
 *
 * An attendee of a row holds its date's ID; the event's pages look attendees up by the event's own ID.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */
final class Attendees_Page {
	/**
	 * The attendees table's column of each attendee's date.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const DATE_COLUMN = 'tec_tickets_recurring_date';

	/**
	 * The request variable of the date the attendees table is narrowed to.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const DATE_FILTER = 'tec_tickets_recurring_date';

	/**
	 * The rows repository.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

	/**
	 * The dates each event's attendees hold, by event: the date's start by its post ID.
	 *
	 * @since TBD
	 *
	 * @var array<int,array<int,string>>
	 */
	private array $dates = [];

	/**
	 * Attendees_Page constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows $rows The rows repository.
	 */
	public function __construct( Rows $rows ) {
		$this->rows = $rows;
	}

	/**
	 * Adds, to the posts attendees are looked up by, the dates the attendees of those events hold.
	 *
	 * The dates come from the attendees, so a date that no longer exists is still there.
	 *
	 * @since TBD
	 *
	 * @param int[]|mixed $post_ids The post IDs attendees are looked up by.
	 *
	 * @return int[] The post IDs, with the events' dates.
	 */
	public function filter_event_ids( $post_ids ): array {
		$post_ids = array_values( array_filter( array_map( 'intval', (array) $post_ids ) ) );

		if ( ! $post_ids ) {
			return $post_ids;
		}

		global $wpdb;
		$placeholders = implode( ', ', array_fill( 0, count( $post_ids ), '%d' ) );
		$date_ids     = DB::get_col(
			DB::prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- One placeholder per ID.
				"SELECT DISTINCT dates.meta_value FROM %i events JOIN %i dates ON dates.post_id = events.post_id AND dates.meta_key = '_tec_tickets_commerce_event' WHERE events.meta_key = %s AND events.meta_value IN ({$placeholders})",
				$wpdb->postmeta,
				$wpdb->postmeta,
				Attendees::POST_ID_META_KEY,
				...$post_ids
			)
		);

		return array_values( array_unique( array_merge( $post_ids, array_map( 'intval', (array) $date_ids ) ) ) );
	}

	/**
	 * Marks, in the attendees table, an attendee whose recurring event ticket is gone with its date.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>|mixed $item The attendee's row in the table.
	 *
	 * @return void
	 */
	public function mark_stranded( $item ): void {
		$ticket_id = (int) ( $item['product_id'] ?? 0 );

		if ( ! Ticket_ID::is_table_ticket( $ticket_id ) || $this->rows->find( Ticket_ID::to_row_id( $ticket_id ) ) ) {
			return;
		}

		printf(
			'<span class="tec-tickets__attendee-stranded" title="%1$s">%2$s</span>',
			esc_attr__( 'The date this ticket was bought for no longer exists. Move the attendee to another date.', 'event-tickets' ),
			esc_html_x( 'Stranded', 'An attendee whose date no longer exists.', 'event-tickets' )
		);
	}

	/**
	 * Gives, on My Tickets, the date a recurring event ticket was bought for.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>|mixed $attendee The attendee's data.
	 *
	 * @return void
	 */
	public function show_date( $attendee ): void {
		$attendee_id = (int) ( $attendee['attendee_id'] ?? 0 );
		$start       = $attendee_id ? (string) get_post_meta( $attendee_id, Attendees::OCCURRENCE_START_META_KEY, true ) : '';

		if ( '' === $start ) {
			return;
		}

		printf( ' <span class="tec-tickets__recurring-ticket-date">%s</span>', esc_html( tribe_format_date( $start, true ) ) );
	}

	/**
	 * Adds, after the ticket, a column of each attendee's date to an event whose attendees hold dates.
	 *
	 * @since TBD
	 *
	 * @param array<string,string>|mixed $columns  The attendees table's columns.
	 * @param int|mixed                  $event_id The event.
	 *
	 * @return array<string,string> The columns.
	 */
	public function add_date_column( $columns, $event_id ): array {
		$columns = (array) $columns;

		if ( ! $this->dates( (int) $event_id ) ) {
			return $columns;
		}

		$position = array_search( 'ticket', array_keys( $columns ), true );
		$position = false === $position ? count( $columns ) : $position + 1;
		$column   = [ self::DATE_COLUMN => esc_html_x( 'Event date', 'attendee table', 'event-tickets' ) ];

		return array_slice( $columns, 0, $position, true ) + $column + array_slice( $columns, $position, null, true );
	}

	/**
	 * Returns an attendee's date, in the date column.
	 *
	 * @since TBD
	 *
	 * @param string|mixed              $value  The cell's value.
	 * @param array<string,mixed>|mixed $item   The attendee's row in the table.
	 * @param string|mixed              $column The column.
	 *
	 * @return string|mixed The cell's value.
	 */
	public function render_date_column( $value, $item, $column ) {
		if ( self::DATE_COLUMN !== $column ) {
			return $value;
		}

		$attendee_id = (int) ( ( (array) $item )['attendee_id'] ?? 0 );
		$start       = $attendee_id ? (string) get_post_meta( $attendee_id, Attendees::OCCURRENCE_START_META_KEY, true ) : '';

		return '' === $start ? '' : esc_html( tribe_format_date( $start, true ) );
	}

	/**
	 * Adds, above the attendees table, a select of the dates its attendees hold to narrow the list to one.
	 *
	 * @since TBD
	 *
	 * @param array<string,string[]>|mixed $nav   The table's nav items.
	 * @param string|mixed                 $which Where the nav is: `top` or `bottom`.
	 *
	 * @return array<string,string[]> The nav items.
	 */
	public function add_date_filter( $nav, $which ): array {
		$nav      = (array) $nav;
		$event_id = Event::filter_event_id( (int) tribe_get_request_var( 'event_id' ), 'attendees-table' );
		$dates    = 'top' === $which ? $this->dates( (int) $event_id ) : [];

		if ( ! $dates ) {
			return $nav;
		}

		$chosen  = $this->chosen_date( (int) $event_id );
		$options = sprintf( '<option value="">%s</option>', esc_html__( 'All dates', 'event-tickets' ) );
		foreach ( $dates as $date_id => $start ) {
			$options .= sprintf( '<option value="%1$d"%2$s>%3$s</option>', $date_id, selected( $chosen, $date_id, false ), esc_html( tribe_format_date( $start, true ) ) );
		}

		$nav['left']['tec_tickets_recurring_date'] = sprintf(
			'<label class="screen-reader-text" for="%1$s">%2$s</label><select name="%1$s" id="%1$s">%3$s</select><input type="submit" class="button action" value="%4$s">',
			esc_attr( self::DATE_FILTER ),
			esc_html__( 'Filter by date', 'event-tickets' ),
			$options,
			esc_attr__( 'Filter', 'event-tickets' )
		);

		return $nav;
	}

	/**
	 * Narrows the attendees table's query to the chosen date.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>|mixed $args     The table's query arguments.
	 * @param int|mixed                 $event_id The event.
	 *
	 * @return array<string,mixed> The arguments.
	 */
	public function narrow_to_date( $args, $event_id ): array {
		$args   = (array) $args;
		$chosen = $this->chosen_date( (int) $event_id );

		if ( $chosen ) {
			$args['by']                = (array) ( $args['by'] ?? [] );
			$args['by']['meta_equals'] = [ '_tec_tickets_commerce_event', $chosen ];
		}

		return $args;
	}

	/**
	 * Returns the date the request narrows an event's attendees to, 0 if none or one its attendees do not hold.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event.
	 *
	 * @return int The date's post ID.
	 */
	private function chosen_date( int $event_id ): int {
		$date_id = absint( tribe_get_request_var( self::DATE_FILTER ) );

		return isset( $this->dates( $event_id )[ $date_id ] ) ? $date_id : 0;
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
	private function dates( int $event_id ): array {
		if ( $event_id <= 0 ) {
			return [];
		}

		if ( isset( $this->dates[ $event_id ] ) ) {
			return $this->dates[ $event_id ];
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

		$this->dates[ $event_id ] = array_column( (array) $rows, 'start', 'date_id' );

		return $this->dates[ $event_id ];
	}
}
