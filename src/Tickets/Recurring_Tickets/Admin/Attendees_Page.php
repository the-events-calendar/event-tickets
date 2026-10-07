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
use TEC\Tickets\Recurring_Tickets\Commerce\Attendees;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;

/**
 * Lists a recurring event's attendees of every date on the event, marks those whose date is gone, and gives each its
 * date on My Tickets.
 *
 * An attendee of a row holds its date's ID; the event's pages look attendees up by the event's own ID.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */
final class Attendees_Page {
	/**
	 * The rows repository.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

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
}
