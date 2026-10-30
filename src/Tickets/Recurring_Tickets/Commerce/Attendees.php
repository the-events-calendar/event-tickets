<?php
/**
 * What an attendee of a row keeps of its event and date.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Commerce
 */

namespace TEC\Tickets\Recurring_Tickets\Commerce;

use DateTimeInterface;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Recurring_Tickets\Models\Ticket;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_Post;

/**
 * Stores, on an attendee of a row, the real event and the date's start, so the attendee keeps both once its date is
 * gone (EngDoc section 4.3).
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Commerce
 */
final class Attendees {
	/**
	 * The meta key of the real event's post ID.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const POST_ID_META_KEY = '_tec_tickets_recurring_post_id';

	/**
	 * The meta key of the date's local start.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const OCCURRENCE_START_META_KEY = '_tec_tickets_recurring_occurrence_start';

	/**
	 * The rows repository.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

	/**
	 * Attendees constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows $rows The rows repository.
	 */
	public function __construct( Rows $rows ) {
		$this->rows = $rows;
	}

	/**
	 * Stores the event and the date on an attendee just created for a row, and cleans the event's attendee caches.
	 *
	 * @since TBD
	 *
	 * @param WP_Post|mixed       $attendee The attendee.
	 * @param WP_Post|mixed       $order    The order.
	 * @param Ticket_Object|mixed $ticket   The ticket.
	 *
	 * @return void
	 */
	public function store( $attendee, $order, $ticket ): void {
		if ( ! $attendee instanceof WP_Post || ! $ticket instanceof Ticket_Object || ! Ticket_ID::is_table_ticket( (int) $ticket->ID ) ) {
			return;
		}

		$row = $this->rows->find( Ticket_ID::to_row_id( (int) $ticket->ID ) );

		if ( $row instanceof Ticket ) {
			$this->write( $attendee->ID, $row );
		}
	}

	/**
	 * Writes a row's event and date on an attendee.
	 *
	 * @since TBD
	 *
	 * @param int    $attendee_id The attendee.
	 * @param Ticket $row         The row.
	 *
	 * @return void
	 */
	public function write( int $attendee_id, Ticket $row ): void {
		$start = $row->occurrence_start instanceof DateTimeInterface ? $row->occurrence_start->format( 'Y-m-d H:i:s' ) : (string) $row->occurrence_start;

		update_post_meta( $attendee_id, self::POST_ID_META_KEY, (int) $row->post_id );
		update_post_meta( $attendee_id, self::OCCURRENCE_START_META_KEY, $start );
		// The attendee's own event is a date: the real event's caches do not know of it yet.
		tribe( Module::class )->clear_attendees_cache( (int) $row->post_id );
	}

	/**
	 * Removes a row's event and date from an attendee that no longer belongs to a row.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id The attendee.
	 *
	 * @return void
	 */
	public function forget( int $attendee_id ): void {
		delete_post_meta( $attendee_id, self::POST_ID_META_KEY );
		delete_post_meta( $attendee_id, self::OCCURRENCE_START_META_KEY );
	}
}
