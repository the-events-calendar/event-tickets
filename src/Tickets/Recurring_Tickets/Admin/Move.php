<?php
/**
 * Move Tickets with recurring event tickets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */

namespace TEC\Tickets\Recurring_Tickets\Admin;

use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Events_Pro\Custom_Tables\V1\Events\Provisional\ID_Generator;
use TEC\Tickets\Recurring_Tickets\Commerce\Attendees;
use TEC\Tickets\Recurring_Tickets\Hydrator;
use TEC\Tickets\Recurring_Tickets\Models\Ticket;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Template_Guard;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Events__Main as TEC;

/**
 * Makes Move Tickets the fix for a stranded attendee (EngDoc section D4): dates are destinations, and a moved
 * attendee's event and date follow it.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */
final class Move {
	/**
	 * The rows repository.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

	/**
	 * What an attendee of a row keeps of its event and date.
	 *
	 * @since TBD
	 *
	 * @var Attendees
	 */
	private Attendees $attendees;

	/**
	 * The row hydrator.
	 *
	 * @since TBD
	 *
	 * @var Hydrator
	 */
	private Hydrator $hydrator;

	/**
	 * Move constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows      $rows      The rows repository.
	 * @param Attendees $attendees What an attendee of a row keeps of its event and date.
	 * @param Hydrator  $hydrator  The row hydrator.
	 */
	public function __construct( Rows $rows, Attendees $attendees, Hydrator $hydrator ) {
		$this->rows      = $rows;
		$this->attendees = $attendees;
		$this->hydrator  = $hydrator;
	}

	/**
	 * Lets a search of events find each date of a recurring event, which sells its own tickets.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $query_args The destination search's query arguments.
	 *
	 * @return array<string,mixed> The arguments.
	 */
	public function allow_dates( $query_args ): array {
		$query_args = (array) $query_args;

		if ( [ TEC::POSTTYPE ] === array_values( (array) ( $query_args['post_type'] ?? [] ) ) ) {
			unset( $query_args['post__not_recurring'] );
		}

		return $query_args;
	}

	/**
	 * Moves an attendee's event and date with it, once moved to another ticket.
	 *
	 * Tickets Commerce moves the sales and the stock, rows included; a source row that is gone changes nothing.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id The attendee.
	 * @param int $from_ticket The ticket it left.
	 * @param int $to_ticket   The ticket it belongs to now.
	 *
	 * @return void
	 */
	public function follow( $attendee_id, $from_ticket, $to_ticket ): void {
		$attendee_id = (int) $attendee_id;

		if ( ! Ticket_ID::is_table_ticket( (int) $to_ticket ) ) {
			$this->attendees->forget( $attendee_id );

			return;
		}

		$row = $this->rows->find( Ticket_ID::to_row_id( (int) $to_ticket ) );

		if ( $row instanceof Ticket ) {
			// Its date, whichever ID the move named it by: the event's own one stands for its first date.
			update_post_meta( $attendee_id, '_tec_tickets_commerce_event', $this->hydrator->event_id( $row ) );
			$this->attendees->write( $attendee_id, $row );
		}
	}

	/**
	 * Returns the tickets Move Tickets offers on a destination: never a template, and for a recurring event's own ID,
	 * which stands for its first date in a list of dates, that date's rows.
	 *
	 * @since TBD
	 *
	 * @param int                     $post_id The destination.
	 * @param Ticket_Object[]|mixed[] $tickets The destination's tickets.
	 *
	 * @return array The tickets to offer.
	 */
	public function ticket_types( int $post_id, array $tickets ): array {
		$guard     = tribe( Template_Guard::class );
		$templates = array_filter( $tickets, static fn( $ticket ) => $ticket instanceof Ticket_Object && $guard->is_template( (int) $ticket->ID ) );

		if ( ! $templates ) {
			return $tickets;
		}

		$first = Occurrence::where( 'post_id', $post_id )->order_by( 'start_date', 'ASC' )->first();

		if ( ! $first ) {
			return array_values( array_diff_key( $tickets, $templates ) );
		}

		return Tickets::get_event_tickets( (int) tribe( ID_Generator::class )->provide_id( (int) $first->occurrence_id ) );
	}
}
