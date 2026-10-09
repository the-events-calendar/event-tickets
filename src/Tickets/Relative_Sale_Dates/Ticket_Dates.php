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
	 * The meta key of the event timezone the ticket's dates were last written in.
	 *
	 * The dates are stored as wall-clock times, so the same times in another timezone fall at other instants.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const TIMEZONE_META_KEY = '_tec_tickets_relative_sale_dates_timezone';

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
	 * A boundary the rule does not resolve, `specific` or a `default` start, keeps the date the ticket has. A new event
	 * timezone counts as a change even when the dates stay the same: they now fall at other instants.
	 *
	 * @since TBD
	 *
	 * @param int  $ticket_id The ticket post ID.
	 * @param int  $post_id   The event post ID.
	 * @param Rule $rule      The ticket's sales window rule.
	 *
	 * @return bool Whether any of the ticket's sale dates, or the timezone they are in, changed.
	 */
	public function write( int $ticket_id, int $post_id, Rule $rule ): bool {
		$window      = $this->sale_window->resolve_for_event( $rule, $post_id );
		$event_dates = $this->sale_window->get_event_dates( $post_id );

		if ( ! $window || ! $event_dates ) {
			return false;
		}

		$values = [ self::TIMEZONE_META_KEY => $event_dates[0]->getTimezone()->getName() ];
		$start  = $window->get_start();
		if ( $start ) {
			$values[ Ticket::START_DATE_META_KEY ] = $start->format( Dates::DBDATEFORMAT );
			$values[ Ticket::START_TIME_META_KEY ] = $start->format( Dates::DBTIMEFORMAT );
		}

		$end = $window->get_end();
		if ( $end ) {
			$values[ Ticket::END_DATE_META_KEY ] = $end->format( Dates::DBDATEFORMAT );
			$values[ Ticket::END_TIME_META_KEY ] = $end->format( Dates::DBTIMEFORMAT );
		}

		$changed = false;
		foreach ( $values as $meta_key => $value ) {
			// `update_post_meta()` returns `false` both for an unchanged value and for a failed write.
			if ( get_post_meta( $ticket_id, $meta_key, true ) !== $value ) {
				update_post_meta( $ticket_id, $meta_key, $value );
				$changed = true;
			}
		}

		return $changed;
	}
}
