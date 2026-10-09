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

use DateTimeImmutable;
use Tribe__Date_Utils as Dates;

/**
 * Writes the dates a rule of any kind resolves to against its event into the date metas the kind names.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Ticket_Dates {
	/**
	 * The meta key of the event timezone the ticket's sales window dates were last written in.
	 *
	 * The dates are stored as wall-clock times, so the same times in another timezone fall at other instants, and the
	 * sales actions that announce them must move.
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
	 * Writes the dates a rule resolves to into the date metas of the rule's kind.
	 *
	 * A boundary the rule does not resolve, `specific` or an open start, keeps the date the ticket has, unless the kind
	 * writes an open start as a value of its own. A new event timezone counts as a change of the sales window even when
	 * its dates stay the same: they now fall at other instants, and the sales actions announce them.
	 *
	 * @since TBD
	 *
	 * @param int  $ticket_id The ticket post ID.
	 * @param int  $post_id   The event post ID.
	 * @param Rule $rule      The ticket's rule, of any kind.
	 *
	 * @return bool Whether any of the dates, or the timezone the sales window is in, changed; `false` when the ticket
	 *              does not have the window or the event has no valid dates.
	 */
	public function write( int $ticket_id, int $post_id, Rule $rule ): bool {
		$kind = $rule->get_kind();

		if ( ! $kind->is_enabled_for_ticket( $ticket_id ) ) {
			return false;
		}

		$window      = $this->sale_window->resolve_for_event( $rule, $post_id );
		$event_dates = $this->sale_window->get_event_dates( $post_id );

		if ( ! $window || ! $event_dates ) {
			return false;
		}

		$metas  = $kind->get_date_metas();
		$values = $kind->has_sales_actions() ? [ self::TIMEZONE_META_KEY => $event_dates[0]->getTimezone()->getName() ] : [];
		$start  = $window->get_start();

		if ( $start ) {
			$values += $this->get_date_values( $metas['start'], $start );
		} elseif ( $rule->opens_at_once() && null !== $kind->get_open_start_value() ) {
			$values[ $metas['start']['date'] ] = $kind->get_open_start_value();
		}

		$end = $window->get_end();
		if ( $end ) {
			$values += $this->get_date_values( $metas['end'], $end );
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

	/**
	 * Gets the values one end of the window is written as.
	 *
	 * @since TBD
	 *
	 * @param array{date: string, time: ?string} $metas The date and time meta keys of the end; a `null` time stores the
	 *                                                  date alone.
	 * @param DateTimeImmutable                  $date  The resolved date, in the event timezone.
	 *
	 * @return array<string,string> The values, keyed by their meta key.
	 */
	private function get_date_values( array $metas, DateTimeImmutable $date ): array {
		$values = [ $metas['date'] => $date->format( Dates::DBDATEFORMAT ) ];

		if ( null !== $metas['time'] ) {
			$values[ $metas['time'] ] = $date->format( Dates::DBTIMEFORMAT );
		}

		return $values;
	}
}
