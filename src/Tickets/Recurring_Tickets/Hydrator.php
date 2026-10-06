<?php
/**
 * Turns a row of the Recurring Event Tickets table into the ticket object Event Tickets expects.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use TEC\Events_Pro\Custom_Tables\V1\Events\Provisional\ID_Generator;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket as Commerce_Ticket;
use TEC\Tickets\Recurring_Tickets\Models\Ticket;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Class Hydrator.
 *
 * Everything comes from the row: no query per ticket for its counts. The sale price is the template's, read the way
 * Tickets Commerce reads it for a post, through post meta the meta shim answers.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Hydrator {
	/**
	 * The rows.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

	/**
	 * The Tickets Commerce ticket service, for the sale price.
	 *
	 * @since TBD
	 *
	 * @var Commerce_Ticket
	 */
	private Commerce_Ticket $commerce;

	/**
	 * Hydrator constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows            $rows     The rows.
	 * @param Commerce_Ticket $commerce The Tickets Commerce ticket service.
	 */
	public function __construct( Rows $rows, Commerce_Ticket $commerce ) {
		$this->rows     = $rows;
		$this->commerce = $commerce;
	}

	/**
	 * Returns the ticket object of a table ticket ID.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The table ticket ID.
	 *
	 * @return Ticket_Object|null The ticket, or null when the ID has no row, as for a deleted ticket.
	 */
	public function load( int $ticket_id ): ?Ticket_Object {
		$row = $this->rows->find( Ticket_ID::to_row_id( $ticket_id ) );

		if ( ! $row ) {
			return null;
		}

		$ticket = $this->hydrate( $row );

		/** This filter is documented in src/Tickets/Commerce/Ticket.php */
		$filtered = apply_filters( 'tec_tickets_commerce_get_ticket_legacy', $ticket, $ticket->get_event_id(), $ticket_id );

		return $filtered instanceof Ticket_Object ? $filtered : $ticket;
	}

	/**
	 * Builds the ticket object of a row already read.
	 *
	 * The type is left to `type()`, which reads it through post meta: assigning it would write post meta for an
	 * ID that is not a post.
	 *
	 * @since TBD
	 *
	 * @param Ticket $row The row.
	 *
	 * @return Ticket_Object The ticket.
	 */
	public function hydrate( Ticket $row ): Ticket_Object {
		// The meta shim reads the row again for the sale price below: it is the row in hand.
		$this->rows->prime( $row );

		$price     = Price::to_decimal( (int) $row->price );
		$unlimited = -1 === (int) $row->capacity;

		$ticket = new Ticket_Object(
			array_merge(
				$this->sale_window( $row ),
				[
					'ID'               => Ticket_ID::from_row_id( (int) $row->id ),
					'name'             => (string) $row->name,
					'description'      => (string) $row->description,
					'show_description' => (bool) $row->show_description,
					'menu_order'       => (int) $row->menu_order,
					'sku'              => (string) $row->sku,
					'post_type'        => Commerce_Ticket::POSTTYPE,
					'provider_class'   => Module::class,
					'admin_link'       => '',
					'price'            => $price,
					'regular_price'    => $price,
					'capacity'         => (int) $row->capacity,
					'event_id'         => $this->event_id( $row ),
				]
			)
		);

		$ticket->manage_stock( ! $unlimited );
		$ticket->stock( $unlimited || null === $row->stock ? -1 : (int) $row->stock );
		$ticket->global_stock_mode( (string) $row->stock_mode );
		$ticket->qty_sold( (int) $row->sales );
		$ticket->qty_pending( 0 );
		$ticket->qty_cancelled( 0 );

		// As Tickets Commerce does for a post, once the ticket has its ID and event.
		$ticket->on_sale = $this->commerce->is_on_sale( $ticket );
		$ticket->price   = $this->commerce->get_price( $ticket );

		return $ticket;
	}

	/**
	 * Returns the ID of the event a row belongs to: the date's ID while ECP provides dates, otherwise the post.
	 *
	 * @since TBD
	 *
	 * @param Ticket $row The row.
	 *
	 * @return int The event ID.
	 */
	public function event_id( Ticket $row ): int {
		if ( $row->occurrence_id && did_action( 'tec_events_pro_custom_tables_v1_fully_activated' ) ) {
			return (int) tribe( ID_Generator::class )->provide_id( (int) $row->occurrence_id );
		}

		return (int) $row->post_id;
	}

	/**
	 * Returns the row's event-local sale window as a ticket post carries it, date and time apart.
	 *
	 * @since TBD
	 *
	 * @param Ticket $row The row.
	 *
	 * @return array{start_date: string, start_time: string, end_date: string, end_time: string} `Y-m-d` dates and
	 *                                                                                           `H:i:s` times, empty
	 *                                                                                           without a date.
	 */
	public function sale_window( Ticket $row ): array {
		[ $start_date, $start_time ] = $this->split( $row->start_date );
		[ $end_date, $end_time ]     = $this->split( $row->end_date );

		return [
			'start_date' => $start_date,
			'start_time' => $start_time,
			'end_date'   => $end_date,
			'end_time'   => $end_time,
		];
	}

	/**
	 * Splits a date into the date and the time.
	 *
	 * @since TBD
	 *
	 * @param mixed $datetime The row's datetime, as the model returns it.
	 *
	 * @return array{0: string, 1: string} The `Y-m-d` date and `H:i:s` time, empty when there is no date.
	 */
	private function split( $datetime ): array {
		if ( $datetime instanceof \DateTimeInterface ) {
			return [ $datetime->format( 'Y-m-d' ), $datetime->format( 'H:i:s' ) ];
		}

		return $datetime ? explode( ' ', (string) $datetime, 2 ) + [ '', '' ] : [ '', '' ];
	}
}
