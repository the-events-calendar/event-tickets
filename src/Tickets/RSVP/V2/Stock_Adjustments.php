<?php
/**
 * Adjusts ticket stock / qty_sold for RSVP V2 so Not Going attendees don't count.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Attendee as TC_Attendee;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Hooks into the ticket stock and qty_sold filters to subtract Not Going attendees
 * from the values reported for `tc-rsvp` tickets, so seats marked Not Going are
 * not treated as held — without changing the underlying `_stock` / qty_sold meta.
 *
 * @since TBD
 */
class Stock_Adjustments {
	/**
	 * Request-scoped cache of Not Going counts, keyed by ticket ID.
	 *
	 * @var array<int,int>
	 */
	private array $not_going_cache = [];

	/**
	 * Adds the Not Going count back to a tc-rsvp ticket's reported stock.
	 *
	 * @since TBD
	 *
	 * @param int           $stock  The stock value computed by Ticket_Object::stock().
	 * @param Ticket_Object $ticket The ticket being queried.
	 *
	 * @return int
	 */
	public function adjust_stock( $stock, $ticket ): int {
		if ( ! $this->is_tc_rsvp( $ticket ) ) {
			return (int) $stock;
		}

		return (int) $stock + $this->count_not_going( (int) $ticket->ID );
	}

	/**
	 * Subtracts the Not Going count from a tc-rsvp ticket's reported qty_sold.
	 *
	 * @since TBD
	 *
	 * @param int           $qty_sold The qty_sold value from Ticket_Object::qty_sold().
	 * @param Ticket_Object $ticket   The ticket being queried.
	 *
	 * @return int
	 */
	public function adjust_qty_sold( $qty_sold, $ticket ): int {
		if ( ! $this->is_tc_rsvp( $ticket ) ) {
			return (int) $qty_sold;
		}

		return max( 0, (int) $qty_sold - $this->count_not_going( (int) $ticket->ID ) );
	}

	/**
	 * Whether the ticket is a Tickets Commerce RSVP (V2).
	 *
	 * @param mixed $ticket Anything; only Ticket_Object with a `type()` of TC_RSVP_TYPE qualifies.
	 */
	private function is_tc_rsvp( $ticket ): bool {
		return $ticket instanceof Ticket_Object
			&& method_exists( $ticket, 'type' )
			&& Constants::TC_RSVP_TYPE === $ticket->type();
	}

	/**
	 * Counts Not Going attendees attached to a ticket, cached per request.
	 *
	 * Mirrors the query in REST_Properties::add_show_not_going_to_properties so the
	 * count matches what the REST API reports as `not_going_count`. Not persistently
	 * cached for the same reasons noted there (invalidation on every attendee /
	 * meta change would cost more than the query itself).
	 */
	private function count_not_going( int $ticket_id ): int {
		if ( isset( $this->not_going_cache[ $ticket_id ] ) ) {
			return $this->not_going_cache[ $ticket_id ];
		}

		$count = (int) DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i p
					INNER JOIN %i pm_ticket ON p.ID = pm_ticket.post_id
					INNER JOIN %i pm_status ON p.ID = pm_status.post_id
					WHERE p.post_type = %s
					AND pm_ticket.meta_key = %s
					AND pm_ticket.meta_value = %s
					AND pm_status.meta_key = %s
					AND pm_status.meta_value IN (%s, %s)',
				DB::prefix( 'posts' ),
				DB::prefix( 'postmeta' ),
				DB::prefix( 'postmeta' ),
				TC_Attendee::POSTTYPE,
				TC_Attendee::$ticket_relation_meta_key,
				$ticket_id,
				Constants::RSVP_STATUS_META_KEY,
				'no',
				'0'
			)
		);

		$this->not_going_cache[ $ticket_id ] = $count;

		return $count;
	}
}
