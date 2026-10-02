<?php
/**
 * Writes the dates a ticket's rule resolves to.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use TEC\Tickets\Commerce\Ticket;
use Tribe__Date_Utils as Dates;

/**
 * Writes the dates a rule resolves to against its event into the ticket's date metas.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Ticket_Dates {
	/**
	 * The sales window resolver.
	 *
	 * @since TBD
	 *
	 * @var Sale_Window
	 */
	private Sale_Window $sale_window;

	/**
	 * Ticket_Dates constructor.
	 *
	 * @since TBD
	 *
	 * @param Sale_Window $sale_window The sales window resolver.
	 */
	public function __construct( Sale_Window $sale_window ) {
		$this->sale_window = $sale_window;
	}

	/**
	 * Writes the dates a rule resolves to into the ticket's sale date fields.
	 *
	 * A boundary the rule does not resolve, `specific` or a `default` start, keeps the date the ticket has.
	 *
	 * @since TBD
	 *
	 * @param int  $ticket_id The ticket post ID.
	 * @param int  $post_id   The event post ID.
	 * @param Rule $rule      The ticket's sales window rule.
	 *
	 * @return void
	 */
	public function write( int $ticket_id, int $post_id, Rule $rule ): void {
		$window = $this->sale_window->resolve_for_event( $rule, $post_id );

		if ( ! $window ) {
			return;
		}

		$start = $window->get_start();
		if ( $start ) {
			update_post_meta( $ticket_id, Ticket::START_DATE_META_KEY, $start->format( Dates::DBDATEFORMAT ) );
			update_post_meta( $ticket_id, Ticket::START_TIME_META_KEY, $start->format( Dates::DBTIMEFORMAT ) );
		}

		$end = $window->get_end();
		if ( $end ) {
			update_post_meta( $ticket_id, Ticket::END_DATE_META_KEY, $end->format( Dates::DBDATEFORMAT ) );
			update_post_meta( $ticket_id, Ticket::END_TIME_META_KEY, $end->format( Dates::DBTIMEFORMAT ) );
		}
	}
}
