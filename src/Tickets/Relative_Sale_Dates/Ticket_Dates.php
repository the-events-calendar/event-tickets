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
	 * The format that writes a date and its time to separate metas, as Tickets Commerce stores its sales dates.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const FORMAT_SPLIT = 'split';

	/**
	 * The format that writes a date and its time, `Y-m-d H:i:s`, to the date meta alone.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const FORMAT_DATETIME = 'datetime';

	/**
	 * The sales window resolver.
	 *
	 * @since TBD
	 *
	 * @var Sale_Window
	 */
	private Sale_Window $sale_window;

	/**
	 * The effects only the sales window has.
	 *
	 * @since TBD
	 *
	 * @var Sales_Window_Effects
	 */
	private Sales_Window_Effects $sales_window_effects;

	/**
	 * Ticket_Dates constructor.
	 *
	 * @since TBD
	 *
	 * @param Sale_Window          $sale_window          The resolver of every kind of window.
	 * @param Sales_Window_Effects $sales_window_effects The effects only the sales window has.
	 */
	public function __construct( Sale_Window $sale_window, Sales_Window_Effects $sales_window_effects ) {
		$this->sale_window          = $sale_window;
		$this->sales_window_effects = $sales_window_effects;
	}

	/**
	 * Writes the dates a rule resolves to into the date metas of the rule's kind.
	 *
	 * A boundary the rule does not resolve, `specific` or an open start, keeps the date the ticket has, unless the kind
	 * writes an open start as a value of its own. A start that opens the window at once moves a later ticket start to
	 * now: the ticket keeps the wall-clock time it was saved with, which an event moved to a timezone behind its own reads
	 * as a later instant. A new event timezone counts as a change of the sales window even when its dates stay the same:
	 * they now fall at other instants, and the sales actions announce them.
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

		$window = $this->sale_window->resolve_for_event( $rule, $post_id );

		if ( ! $window ) {
			return false;
		}

		$metas  = $kind->get_date_metas();
		$format = $this->get_format( $ticket_id, $kind );

		$window = $this->sales_window_effects->apply_now_start( $window, $rule, $this->read_wall_clock( $ticket_id, $metas['start'], $format ) );
		$values = $this->sales_window_effects->get_extra_metas( $window, $kind );
		$start  = $window->get_start();

		if ( $start ) {
			$values += $this->get_date_values( $metas['start'], $start, $format );
		} elseif ( $rule->opens_at_once() && null !== $kind->get_open_start_value() ) {
			$values[ $metas['start']['date'] ] = $kind->get_open_start_value();
		}

		$end = $window->get_end();
		if ( $end ) {
			$values += $this->get_date_values( $metas['end'], $end, $format );
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
	 * Returns whether a window of the ticket, as written, ends before it starts.
	 *
	 * A window stored with times ends before it starts when its end is not after its start; one stored as whole days,
	 * such as the sale price, when its end day is not after its start day, as the save judges it.
	 *
	 * @since TBD
	 *
	 * @param int         $ticket_id The ticket post ID.
	 * @param Window_Kind $kind      The kind of window.
	 *
	 * @return bool Whether both dates are set and the end is not after the start.
	 */
	public function ends_before_it_starts( int $ticket_id, Window_Kind $kind ): bool {
		$metas  = $kind->get_date_metas();
		$format = $this->get_format( $ticket_id, $kind );
		$start  = $this->read_date( $ticket_id, $metas['start'], $format );
		$end    = $this->read_date( $ticket_id, $metas['end'], $format );

		if ( null === $start || null === $end ) {
			return false;
		}

		if ( $kind->compares_days() ) {
			return $end['date'] <= $start['date'];
		}

		// Without both times only the days can be told apart.
		if ( null === $start['time'] || null === $end['time'] ) {
			return $end['date'] < $start['date'];
		}

		// Both are `Y-m-d H:i:s` in the same timezone, so they compare as strings.
		return "{$end['date']} {$end['time']}" <= "{$start['date']} {$start['time']}";
	}

	/**
	 * Gets the format a ticket's dates of a window are written in.
	 *
	 * @since TBD
	 *
	 * @param int         $ticket_id The ticket post ID.
	 * @param Window_Kind $kind      The kind of window.
	 *
	 * @return string The format, `FORMAT_SPLIT` or `FORMAT_DATETIME`; always `FORMAT_SPLIT` for a kind that stores whole
	 *                days.
	 */
	private function get_format( int $ticket_id, Window_Kind $kind ): string {
		if ( $kind->compares_days() ) {
			return self::FORMAT_SPLIT;
		}

		/**
		 * Filters the format a ticket's sales dates are written in.
		 *
		 * Tickets Commerce reads a date and its time from separate metas; a provider that reads both from the date meta,
		 * such as one added through `tec_tickets_relative_sale_dates_ticket_providers`, asks for `datetime`.
		 *
		 * @since TBD
		 *
		 * @param string $format    The format, `split` or `datetime`. Default `split`.
		 * @param int    $ticket_id The ticket post ID.
		 */
		$format = apply_filters( 'tec_tickets_relative_sale_dates_ticket_date_format', self::FORMAT_SPLIT, $ticket_id );

		return self::FORMAT_DATETIME === $format ? self::FORMAT_DATETIME : self::FORMAT_SPLIT;
	}

	/**
	 * Gets the values one end of the window is written as.
	 *
	 * @since TBD
	 *
	 * @param array{date: string, time: ?string} $metas  The date and time meta keys of the end; a `null` time stores the
	 *                                                   date alone.
	 * @param DateTimeImmutable                  $date   The resolved date, in the event timezone.
	 * @param string                             $format The format the dates are written in.
	 *
	 * @return array<string,string> The values, keyed by their meta key.
	 */
	private function get_date_values( array $metas, DateTimeImmutable $date, string $format ): array {
		if ( self::FORMAT_DATETIME === $format ) {
			return [ $metas['date'] => $date->format( Dates::DBDATETIMEFORMAT ) ];
		}

		$values = [ $metas['date'] => $date->format( Dates::DBDATEFORMAT ) ];

		if ( null !== $metas['time'] ) {
			$values[ $metas['time'] ] = $date->format( Dates::DBTIMEFORMAT );
		}

		return $values;
	}

	/**
	 * Reads the date and time one end of a window is written as.
	 *
	 * @since TBD
	 *
	 * @param int                                $ticket_id The ticket post ID.
	 * @param array{date: string, time: ?string} $metas     The date and time meta keys of the end.
	 * @param string                             $format    The format the dates are written in.
	 *
	 * @return array{date: string, time: ?string}|null The date and the time, `null` when there is none, or `null` when
	 *                                                 the end has no date.
	 */
	private function read_date( int $ticket_id, array $metas, string $format ): ?array {
		$date = get_post_meta( $ticket_id, $metas['date'], true );

		if ( ! is_string( $date ) || '' === $date ) {
			return null;
		}

		if ( self::FORMAT_DATETIME === $format ) {
			$time = substr( $date, 11, 8 );
		} else {
			$time = null === $metas['time'] ? '' : get_post_meta( $ticket_id, $metas['time'], true );
		}

		return [
			// `ticket_add()` writes a start sent without a date as `Y-m-d 00:00:00`, which compares after `Y-m-d`.
			'date' => substr( $date, 0, 10 ),
			'time' => is_string( $time ) && '' !== $time ? $time : null,
		];
	}

	/**
	 * Reads one end of a window as a wall-clock time in the event timezone.
	 *
	 * @since TBD
	 *
	 * @param int                                $ticket_id The ticket post ID.
	 * @param array{date: string, time: ?string} $metas     The date and time meta keys of the end.
	 * @param string                             $format    The format the dates are written in.
	 *
	 * @return string The date and time, `Y-m-d H:i:s` or `Y-m-d` without a time, or `''` when the end has no date.
	 */
	private function read_wall_clock( int $ticket_id, array $metas, string $format ): string {
		$date = $this->read_date( $ticket_id, $metas, $format );

		return $date ? trim( $date['date'] . ' ' . $date['time'] ) : '';
	}
}
