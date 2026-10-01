<?php
/**
 * Stores a ticket's sale price rule and writes the sale price dates it resolves to.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use InvalidArgumentException;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use Tribe__Date_Utils as Dates;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_Error;

/**
 * Applies the sale price rule of a Tickets Commerce ticket on an event once the sale price itself is saved.
 *
 * The sale price save reads the dates from the ticket data, not from the ticket, so the resolved dates are written over
 * them afterwards. The rule sent with the ticket data replaces the stored one; a save that does not send the rule keeps
 * the stored one and applies it again, and an empty rule removes it. Removing the sale price removes its rule too.
 * Ticket data whose sale price window does not end after it starts, or starts outside the ticket sales window, is
 * rejected before it is saved.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Sale_Price_Save {
	/**
	 * The ticket data key that carries the sale price rule; its value is a JSON string, an array, or `null` or `''` to
	 * remove it.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const DATA_KEY = 'ticket_sale_price_relative';

	/**
	 * The store of the ticket rules.
	 *
	 * @since TBD
	 *
	 * @var Rule_Store
	 */
	private Rule_Store $rule_store;

	/**
	 * The writer of the sale price dates.
	 *
	 * @since TBD
	 *
	 * @var Sale_Price_Dates
	 */
	private Sale_Price_Dates $sale_price_dates;

	/**
	 * The sales window controller, which knows the sales window a save applies.
	 *
	 * @since TBD
	 *
	 * @var Ticket_Save
	 */
	private Ticket_Save $ticket_save;

	/**
	 * The sale price window resolver.
	 *
	 * @since TBD
	 *
	 * @var Sale_Price_Window
	 */
	private Sale_Price_Window $sale_price_window;

	/**
	 * Sale_Price_Save constructor.
	 *
	 * @since TBD
	 *
	 * @param Rule_Store       $rule_store       The store of the ticket rules.
	 * @param Sale_Price_Dates  $sale_price_dates  The writer of the sale price dates.
	 * @param Sale_Price_Window $sale_price_window The sale price window resolver.
	 * @param Ticket_Save       $ticket_save       The ticket save, which knows the sales window a save stores.
	 */
	public function __construct( Rule_Store $rule_store, Sale_Price_Dates $sale_price_dates, Sale_Price_Window $sale_price_window, Ticket_Save $ticket_save ) {
		$this->rule_store        = $rule_store;
		$this->sale_price_dates  = $sale_price_dates;
		$this->sale_price_window = $sale_price_window;
		$this->ticket_save       = $ticket_save;
	}

	/**
	 * Stores the sale price rule, or removes it.
	 *
	 * @since TBD
	 *
	 * @param int                 $post_id The ticket parent post ID.
	 * @param Ticket_Object       $ticket  The ticket that was saved.
	 * @param array<string,mixed> $data    The ticket data that was saved.
	 *
	 * @return void
	 */
	public function save_rule( int $post_id, Ticket_Object $ticket, array $data ): void {
		if (
			'tribe_events' !== get_post_type( $post_id )
			|| Series_Passes::TICKET_TYPE === get_post_meta( $ticket->ID, Ticket::$type_meta_key, true )
		) {
			return;
		}

		if ( ! $this->sale_price_dates->has_sale_price( $ticket->ID ) || $this->removes_rule( $data ) ) {
			$this->rule_store->remove( $ticket->ID, [ Sale_Price_Rule::KEY ] );

			return;
		}

		$rule = $this->parse_rule( $data[ self::DATA_KEY ] ?? null );

		if ( $rule ) {
			$this->rule_store->save( $ticket->ID, [ Sale_Price_Rule::KEY => $rule->to_array() ] );
		}
	}

	/**
	 * Rejects ticket data whose sale price window does not end after the day it starts, or starts outside the sales window.
	 *
	 * The sale price rule judged is the one the save applies: the one sent, or else the one stored for the ticket. A
	 * specific boundary is judged with its submitted date, and a *now* start, or a specific one sent without a date, with
	 * the day sales open; a specific boundary sent with a date the datepicker format cannot read is rejected. Dates are
	 * compared as days, as the on-sale check reads them. A ticket without a sale price rule keeps today's behavior, and so
	 * does a sale price the save is about to drop.
	 *
	 * @since TBD
	 *
	 * @param true|WP_Error       $valid   `true`, or the error an earlier callback rejected the data with.
	 * @param int                 $post_id The ticket parent post ID.
	 * @param array<string,mixed> $data    The ticket data about to be saved.
	 *
	 * @return true|WP_Error `true` when the sale price window is valid or does not apply, the error otherwise.
	 */
	public function validate_ticket_data( $valid, int $post_id, array $data ) {
		if (
			is_wp_error( $valid )
			|| $this->removes_rule( $data )
			|| Module::class !== ( $data['ticket_provider'] ?? Module::class )
			|| 'tribe_events' !== get_post_type( $post_id )
			|| Series_Passes::TICKET_TYPE === ( $data['ticket_type'] ?? 'default' )
			|| ! $this->saves_a_sale_price( $data )
		) {
			return $valid;
		}

		// The save would keep the stored rule, but the admin who sent this one expects it to apply.
		if ( isset( $data[ self::DATA_KEY ] ) && ! $this->parse_rule( $data[ self::DATA_KEY ] ) ) {
			return $this->get_ends_before_start_error();
		}

		$rule  = $this->get_rule_to_apply( absint( $data['ticket_id'] ?? 0 ), $data );
		$dates = $rule ? $this->sale_price_window->resolve_for_event( $rule, $post_id ) : null;

		// A specific boundary, resolved as `null`, keeps its submitted date: one it cannot read would be stored as 1970.
		foreach ( [ 'start', 'end' ] as $key ) {
			if ( $dates && null === $dates[ $key ] && $this->sends_an_unreadable_date( $data, $key ) ) {
				return $this->get_ends_before_start_error();
			}
		}

		$sales_window = $dates ? $this->ticket_save->get_sales_window( $post_id, $data ) : null;

		// Without both ends of the sales window there is nothing to judge the sale price against.
		if ( ! $sales_window ) {
			return $valid;
		}

		$start       = $dates['start'] ?? $this->get_submitted_date( $data, 'start' );
		$end         = $dates['end'] ?? $this->get_submitted_date( $data, 'end' );
		$sales_start = $sales_window->get_start()->format( Dates::DBDATEFORMAT );
		$sales_end   = $sales_window->get_end()->format( Dates::DBDATEFORMAT );

		if ( '' !== $end && $end <= ( '' === $start ? $sales_start : $start ) ) {
			return $this->get_ends_before_start_error();
		}

		if ( '' !== $start && ( $start < $sales_start || $start > $sales_end ) ) {
			return $this->get_outside_sales_window_error();
		}

		return $valid;
	}

	/**
	 * Writes the sale price dates the stored rule resolves to once the ticket is saved.
	 *
	 * @since TBD
	 *
	 * @param int           $post_id The ticket parent post ID.
	 * @param Ticket_Object $ticket  The ticket that was saved.
	 *
	 * @return void
	 */
	public function write_resolved_dates( int $post_id, Ticket_Object $ticket ): void {
		if (
			'tribe_events' !== get_post_type( $post_id )
			|| Series_Passes::TICKET_TYPE === get_post_meta( $ticket->ID, Ticket::$type_meta_key, true )
		) {
			return;
		}

		$this->sale_price_dates->write( $ticket->ID, $post_id );
	}

	/**
	 * Returns whether the ticket data asks to remove the sale price rule, sending it as `null` or `''`.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The ticket data.
	 *
	 * @return bool Whether the sale price rule is to be removed.
	 */
	private function removes_rule( array $data ): bool {
		return array_key_exists( self::DATA_KEY, $data ) && in_array( $data[ self::DATA_KEY ], [ null, '' ], true );
	}

	/**
	 * Gets the sale price rule to apply to the ticket: the one in the ticket data, or else the stored one.
	 *
	 * @since TBD
	 *
	 * @param int                 $ticket_id The ticket post ID, or `0` for a ticket not saved yet.
	 * @param array<string,mixed> $data      The ticket data.
	 *
	 * @return Sale_Price_Rule|null The rule, or `null` when neither holds a valid one.
	 */
	private function get_rule_to_apply( int $ticket_id, array $data ): ?Sale_Price_Rule {
		return $this->parse_rule( $data[ self::DATA_KEY ] ?? null )
			?? ( $ticket_id ? Sale_Price_Rule::from_stored( $this->rule_store->get( $ticket_id ) ) : null );
	}

	/**
	 * Builds a sale price rule from the value the ticket data sends for it.
	 *
	 * An invalid rule is not a request to remove the valid one already stored, so it builds nothing.
	 *
	 * @since TBD
	 *
	 * @param mixed $raw The rule as sent, a JSON string or an array.
	 *
	 * @return Sale_Price_Rule|null The rule, or `null` when nothing valid was sent.
	 */
	private function parse_rule( $raw ): ?Sale_Price_Rule {
		try {
			if ( is_array( $raw ) ) {
				return Sale_Price_Rule::from_array( $raw );
			}

			if ( is_string( $raw ) ) {
				return Sale_Price_Rule::from_json( $raw );
			}
		} catch ( InvalidArgumentException $e ) {
			return null;
		}

		return null;
	}

	/**
	 * Returns whether the save keeps the sale price the ticket data sends.
	 *
	 * The save drops a sale price that is unchecked or not lower than the price, with the same `>=` comparison.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The ticket data.
	 *
	 * @return bool Whether the ticket will have a sale price.
	 */
	private function saves_a_sale_price( array $data ): bool {
		if ( ! tribe_is_truthy( $data['ticket_add_sale_price'] ?? false ) ) {
			return false;
		}

		return ! ( ( $data['ticket_sale_price'] ?? false ) >= ( $data['ticket_price'] ?? false ) );
	}

	/**
	 * Gets the sale price date submitted for one end of the window, formatted from the datepicker as the sale price save
	 * formats it, but as `''` where that save would store 1970 for a date it cannot read.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The ticket data.
	 * @param string              $end  The end of the sale price window, `start` or `end`.
	 *
	 * @return string The date as `Y-m-d`, or `''` when none was submitted or it cannot be read.
	 */
	private function get_submitted_date( array $data, string $end ): string {
		$date = $data[ "ticket_sale_{$end}_date" ] ?? '';

		if ( ! is_string( $date ) || '' === $date ) {
			return '';
		}

		$formatted = Dates::maybe_format_from_datepicker( $date );
		$timestamp = is_string( $formatted ) ? strtotime( $formatted ) : false;

		return false === $timestamp ? '' : gmdate( Dates::DBDATEFORMAT, $timestamp );
	}

	/**
	 * Returns whether the ticket data sends a sale price date for one end of the window that the datepicker format
	 * cannot read.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The ticket data.
	 * @param string              $end  The end of the sale price window, `start` or `end`.
	 *
	 * @return bool Whether a date was sent and cannot be read.
	 */
	private function sends_an_unreadable_date( array $data, string $end ): bool {
		$date = $data[ "ticket_sale_{$end}_date" ] ?? '';

		return is_string( $date ) && '' !== $date && '' === $this->get_submitted_date( $data, $end );
	}

	/**
	 * Gets the error that rejects a sale price window that does not end after the day it starts, or an invalid rule.
	 *
	 * @since TBD
	 *
	 * @return WP_Error The error, with a 400 status for REST responses.
	 */
	private function get_ends_before_start_error(): WP_Error {
		return new WP_Error(
			'tec_tickets_relative_sale_dates_sale_price_ends_before_start',
			__( 'The sale price cannot end before it starts. Please adjust the sale price window.', 'event-tickets' ),
			[ 'status' => 400 ]
		);
	}

	/**
	 * Gets the error that rejects a sale price starting outside the ticket sales window.
	 *
	 * @since TBD
	 *
	 * @return WP_Error The error, with a 400 status for REST responses.
	 */
	private function get_outside_sales_window_error(): WP_Error {
		return new WP_Error(
			'tec_tickets_relative_sale_dates_sale_price_outside_sales_window',
			__( 'The sale price window falls outside the ticket sales window. Please adjust the dates.', 'event-tickets' ),
			[ 'status' => 400 ]
		);
	}
}
