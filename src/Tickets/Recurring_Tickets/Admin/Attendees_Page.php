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
use TEC\Tickets\Recurring_Tickets\Template_Guard;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use Tribe__Events__Main as TEC;
use Tribe__Tickets__Tickets as Tickets;

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
	 * The rows repository.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

	/**
	 * The select that narrows the table to one date.
	 *
	 * @since TBD
	 *
	 * @var Date_Filter
	 */
	private Date_Filter $date_filter;

	/**
	 * The event's dates.
	 *
	 * @since TBD
	 *
	 * @var Event_Dates
	 */
	private Event_Dates $event_dates;

	/**
	 * The sales of each recurring event ticket over every date.
	 *
	 * @since TBD
	 *
	 * @var Template_Sales
	 */
	private Template_Sales $template_sales;

	/**
	 * Attendees_Page constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows           $rows           The rows repository.
	 * @param Date_Filter    $date_filter    The select that narrows the table to one date.
	 * @param Event_Dates    $event_dates    The event's dates.
	 * @param Template_Sales $template_sales The sales of each recurring event ticket over every date.
	 */
	public function __construct( Rows $rows, Date_Filter $date_filter, Event_Dates $event_dates, Template_Sales $template_sales ) {
		$this->rows           = $rows;
		$this->date_filter    = $date_filter;
		$this->event_dates    = $event_dates;
		$this->template_sales = $template_sales;
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
		$post_ids = (array) $post_ids;

		// Without The Events Calendar there are no events, nor dates.
		if ( ! class_exists( TEC::class ) ) {
			return $post_ids;
		}

		// Only an event's attendees hold its dates.
		$event_ids = array_values( array_filter( array_map( 'intval', $post_ids ), static fn( int $id ) => $id > 0 && TEC::POSTTYPE === get_post_type( $id ) ) );

		if ( ! $event_ids ) {
			return $post_ids;
		}

		// ponytail: scans the event meta of every recurring event ticket attendee; a lookup table when sites hold 100k+.
		global $wpdb;
		$placeholders = implode( ', ', array_fill( 0, count( $event_ids ), '%d' ) );
		$date_ids     = DB::get_col(
			DB::prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- One placeholder per ID.
				"SELECT DISTINCT dates.meta_value FROM %i events JOIN %i dates ON dates.post_id = events.post_id AND dates.meta_key = '_tec_tickets_commerce_event' WHERE events.meta_key = %s AND events.meta_value IN ({$placeholders})",
				$wpdb->postmeta,
				$wpdb->postmeta,
				Attendees::POST_ID_META_KEY,
				...$event_ids
			)
		);

		return array_values( array_unique( array_merge( $post_ids, array_map( 'intval', (array) $date_ids ) ) ) );
	}

	/**
	 * Cleans an event's cached attendees with its date's, since the event's list holds the date's attendees.
	 *
	 * @since TBD
	 *
	 * @param int|mixed $post_id The post whose cached attendees were cleaned.
	 *
	 * @return void
	 */
	public function forget_event_cache( $post_id ): void {
		$event_id = (int) Event::filter_event_id( (int) $post_id, 'recurring-tickets-attendees-cache' );

		if ( $event_id > 0 && (int) $post_id !== $event_id ) {
			tribe( 'post-transient' )->delete( $event_id, Tickets::ATTENDEES_CACHE );
		}
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

		if ( ! $this->event_dates->held( (int) $event_id ) ) {
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
	 * Adds, above the attendees table, a select of the event's dates to narrow the list to one.
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
		$event_id = (int) Event::filter_event_id( (int) tribe_get_request_var( 'event_id' ), 'attendees-table' );
		$dates    = 'top' === $which ? $this->event_dates->all( $event_id ) : [];

		if ( $dates ) {
			$nav['left'][ Date_Filter::NAME ] = $this->date_filter->render( $dates, $this->event_dates->removed( $event_id ), __( 'Attendees for:', 'event-tickets' ) );
		}

		return $nav;
	}

	/**
	 * Narrows the attendees table's query to the chosen dates.
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
		$chosen = $this->date_filter->chosen( $this->event_dates->all( (int) $event_id ) );

		if ( null !== $chosen ) {
			$args['by']            = (array) ( $args['by'] ?? [] );
			$args['by']['meta_in'] = [ '_tec_tickets_commerce_event', $chosen ?: [ 0 ] ];
		}

		return $args;
	}

	/**
	 * Gives the Attendees page of a recurring event, or of one of its dates, its recurring event tickets' labels, and
	 * the event's page the sales of its templates over every date: a template is never sold, its rows are.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>|mixed $context The Attendees page's render context.
	 * @param int|mixed                 $post_id The post of the page.
	 *
	 * @return array<string,mixed> The context.
	 */
	public function summarize_tickets( $context, $post_id ): array {
		$context   = (array) $context;
		$templates = $context['tickets_by_type'][ Template_Guard::TICKET_TYPE ] ?? [];

		if ( ! $templates ) {
			return $context;
		}

		$context['type_labels'][ Template_Guard::TICKET_TYPE ]       = __( 'Recurring Event Tickets', 'event-tickets' );
		$context['type_icon_classes'][ Template_Guard::TICKET_TYPE ] = 'tec-tickets__admin-attendees-overview-ticket-type-icon--ticket';
		$context['post_singular_label']                              = __( 'Recurring Event', 'event-tickets' );

		$sales     = $this->template_sales->for_event( $this->event_dates->event_of( (int) $post_id ) );
		$sold      = (int) ( $context['ticket_totals']['sold'] ?? 0 );
		$available = (int) ( $context['ticket_totals']['available'] ?? 0 );
		foreach ( $templates as $template ) {
			// A date's page lists its rows, which count their own sales.
			if ( Ticket_ID::is_table_ticket( (int) $template->ID ) ) {
				continue;
			}

			$template_sales = $sales[ (int) $template->ID ] ?? [
				'sold'      => 0,
				'available' => 0,
			];

			$context['ticket_stats'][ (int) $template->ID ] = sprintf(
				// translators: %1$d is the number of tickets issued, %2$s the number available, or "Unlimited".
				_x( '%1$d issued (%2$s available)', 'recurring event ticket on the attendees overview', 'event-tickets' ),
				$template_sales['sold'],
				tribe_tickets_get_readable_amount( $template_sales['available'] )
			);

			// The page counted the template; its rows are what was sold.
			$sold += $template_sales['sold'] - (int) $template->qty_sold();

			if ( -1 === $available ) {
				continue;
			}

			// ponytail: assumes the template's own stock; a shared-capacity template was counted once per event instead.
			$available = -1 === $template_sales['available'] ? -1 : $available + $template_sales['available'] - max( 0, (int) $template->available() );
		}

		$context['ticket_totals'] = [
			'sold'      => $sold,
			'available' => $available,
		];

		return $context;
	}

	/**
	 * Adds, to the tickets an event's attendance totals count as paid, the recurring event tickets sold on its dates.
	 *
	 * @since TBD
	 *
	 * @param int|mixed $total    The number paid.
	 * @param int|mixed $original The number before filtering.
	 * @param int|mixed $event_id The event.
	 *
	 * @return int The number paid.
	 */
	public function add_sold_to_paid( $total, $original, $event_id ): int {
		return (int) $total + $this->template_sales->sold( (int) $event_id );
	}
}
