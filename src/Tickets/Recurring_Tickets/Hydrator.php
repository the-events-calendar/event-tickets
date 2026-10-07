<?php
/**
 * Turns a row of the Recurring Event Tickets table into the ticket object Event Tickets expects.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use TEC\Common\StellarWP\DB\Database\Exceptions\DatabaseQueryException;
use TEC\Events_Pro\Custom_Tables\V1\Events\Provisional\ID_Generator;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket as Commerce_Ticket;
use TEC\Tickets\Commerce\Utils\Currency;
use TEC\Tickets\Recurring_Tickets\Models\Ticket;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Class Hydrator.
 *
 * Everything comes from the row: no query per ticket for its counts, and no post meta read. The object is cached
 * under the ticket ID in the group and shape Tickets Commerce uses for ticket posts; every row writer clears it.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Hydrator {
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
		$cached = wp_cache_get( $ticket_id, 'tec_tickets' );

		if ( is_array( $cached ) && $cached ) {
			return new Ticket_Object( $cached );
		}

		try {
			$row = Tickets::get_by_id( Ticket_ID::to_row_id( $ticket_id ) );
		} catch ( DatabaseQueryException $e ) {
			// No table, no rows: the ID names a ticket that does not exist.
			return null;
		}

		if ( ! $row instanceof Ticket ) {
			return null;
		}

		$ticket = $this->hydrate( $row );

		/** This filter is documented in src/Tickets/Commerce/Ticket.php */
		$filtered = apply_filters( 'tec_tickets_commerce_get_ticket_legacy', $ticket, $ticket->get_event_id(), $ticket_id );
		$ticket   = $filtered instanceof Ticket_Object ? $filtered : $ticket;

		wp_cache_set( $ticket_id, $ticket->to_array(), 'tec_tickets' );

		return $ticket;
	}

	/**
	 * Builds the ticket object of a row already read, without a query.
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
		$price                       = $this->to_decimal( (int) $row->price );
		$unlimited                   = -1 === (int) $row->capacity;
		[ $start_date, $start_time ] = $this->split( $row->start_date );
		[ $end_date, $end_time ]     = $this->split( $row->end_date );

		$ticket = new Ticket_Object(
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
				'on_sale'          => false,
				'start_date'       => $start_date,
				'start_time'       => $start_time,
				'end_date'         => $end_date,
				'end_time'         => $end_time,
				'capacity'         => (int) $row->capacity,
				'event_id'         => $this->event_id( $row ),
			]
		);

		$ticket->manage_stock( ! $unlimited );
		$ticket->stock( $unlimited || null === $row->stock ? -1 : (int) $row->stock );
		$ticket->global_stock_mode( (string) $row->stock_mode );
		$ticket->qty_sold( (int) $row->sales );
		$ticket->qty_pending( 0 );
		$ticket->qty_cancelled( 0 );

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
	private function event_id( Ticket $row ): int {
		if ( $row->occurrence_id && did_action( 'tec_events_pro_custom_tables_v1_fully_activated' ) ) {
			return (int) tribe( ID_Generator::class )->provide_id( (int) $row->occurrence_id );
		}

		return (int) $row->post_id;
	}

	/**
	 * Converts a row's price, in thousandths of the currency's unit, to the decimal string a ticket carries.
	 *
	 * @since TBD
	 *
	 * @param int $thousandths The stored price.
	 *
	 * @return string The amount with the Tickets Commerce currency's own decimals, e.g. `10.50`.
	 */
	private function to_decimal( int $thousandths ): string {
		// The currency's own decimals, not the site's display setting, as Order Line Items stores money.
		$decimals = (int) ( Currency::get_default_currency_map()[ Currency::get_currency_code() ]['decimal_precision'] ?? 2 );

		return number_format( $thousandths / 1000, $decimals, '.', '' );
	}

	/**
	 * Splits a date into the date and time a ticket carries separately.
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
