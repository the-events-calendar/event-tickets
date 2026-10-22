<?php
/**
 * Judges the window of a kind that a ticket save would store.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Tribe__Date_Utils as Dates;
use WP_Error;

/**
 * Builds the window a save of the ticket data would store, from the rule applied and the dates submitted, and rejects
 * one with a date that cannot be read, or that ends before it starts or starts outside its parent window.
 *
 * Every kind is judged alike, at the precision of its dates; what only the sales window does is
 * `Sales_Window_Effects`'.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Window_Validator {
	/**
	 * The resolver of every kind of window.
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
	 * Window_Validator constructor.
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
	 * Judges the window of one kind the ticket data would save.
	 *
	 * Without a rule, or without event dates, there is nothing to judge. A date the window reads from the ticket data
	 * that cannot be read rejects it as such. The order of the window's ends is judged at the kind's precision. An open
	 * start the kind stores itself, the sale price's *Now*, is never judged: it opens with the parent window, and an
	 * admin choosing it gets no error. Any other start without a date of its own counts as the start of the parent
	 * window. A start with its own date must fall within the parent window, which is judged only once both ends of the
	 * parent window are known.
	 *
	 * @since TBD
	 *
	 * @param int                 $post_id     The ticket parent post ID.
	 * @param array<string,mixed> $data        The ticket data about to be saved.
	 * @param Window_Kind         $kind        The kind of window to judge.
	 * @param Rule|null           $rule        The rule of the kind the save applies, or `null` for none.
	 * @param Rule|null           $parent_rule The rule of the parent kind the save applies, or `null` for none.
	 *
	 * @return WP_Error|null The error of the kind that rejects the window, or `null` when it is valid or not judged.
	 */
	public function validate( int $post_id, array $data, Window_Kind $kind, ?Rule $rule, ?Rule $parent_rule ): ?WP_Error {
		$event_dates = $this->sale_window->get_event_dates( $post_id );

		if ( ! $rule || ! $event_dates ) {
			return null;
		}

		$window = $this->get_window( $post_id, $data, $kind, $rule, $event_dates );

		if ( $window instanceof WP_Error ) {
			return $window;
		}

		if ( ! $window ) {
			return $kind->get_error( 'endsBeforeStart' );
		}

		$parent        = $kind->get_parent();
		$parent_window = $parent ? $this->get_window( $post_id, $data, $parent, $parent_rule, $event_dates ) : null;
		$parent_start  = $parent_window instanceof Resolved_Window ? $parent_window->get_start() : null;
		$parent_end    = $parent_window instanceof Resolved_Window ? $parent_window->get_end() : null;

		$own_start     = $window->get_start();
		$is_open_start = null !== $kind->get_open_start_value() && $kind->get_open_start_mode() === $rule->get_start()->get_mode();
		$start         = $own_start ?? ( $is_open_start ? null : $parent_start );
		$end           = $window->get_end();

		if ( $start && $end && $this->compare_dates( $end, $start, $kind ) <= 0 ) {
			return $kind->get_error( 'endsBeforeStart' );
		}

		if (
			$own_start && $parent_start && $parent_end
			&& ( $this->compare_dates( $own_start, $parent_start, $kind ) < 0 || $this->compare_dates( $own_start, $parent_end, $kind ) > 0 )
		) {
			return $kind->get_error( 'outsideParent' );
		}

		return null;
	}

	/**
	 * Gets the window of a kind a save of the ticket data would store.
	 *
	 * A boundary the rule leaves to the ticket takes its submitted date. An open start of a kind that writes one as a
	 * value of its own has no date: the window opens with its parent. The dates `ticket_add()` fills in for the
	 * ticket's own sales dates are `Sales_Window_Effects`'.
	 *
	 * @since TBD
	 *
	 * @param int                                               $post_id     The ticket parent post ID.
	 * @param array<string,mixed>                               $data        The ticket data about to be saved.
	 * @param Window_Kind                                       $kind        The kind of window.
	 * @param Rule|null                                         $rule        The rule of the kind the save applies, or
	 *                                                                       `null` for none.
	 * @param array{0: DateTimeImmutable, 1: DateTimeImmutable} $event_dates The event start and end, in the event
	 *                                                                       timezone.
	 *
	 * @return Resolved_Window|WP_Error|null The window, whose ends are `null` where it has no date; the kind's
	 *                                       `invalidDate` error when a submitted date it reads cannot be read; `null`
	 *                                       when a boundary of a kind that needs a date has none.
	 */
	private function get_window( int $post_id, array $data, Window_Kind $kind, ?Rule $rule, array $event_dates ) {
		$timezone = $event_dates[0]->getTimezone();
		$fields   = $kind->get_submitted_fields();
		$is_open  = $rule && $rule->opens_at_once() && null !== $kind->get_open_start_value();

		$submitted_start = $this->get_submitted_date( $data, $fields['start'], $timezone );
		$window          = $rule ? $this->sale_window->resolve_for_event( $rule, $post_id ) : null;
		$window          = $window ? $this->sales_window_effects->apply_now_start( $window, $rule, $submitted_start ? $submitted_start->format( 'Y-m-d H:i:s' ) : '' ) : null;
		$start           = $window ? $window->get_start() : null;
		$end             = $window ? $window->get_end() : null;

		// A date the datepicker format cannot read would be stored as 1970, or as no date.
		if (
			( ! $start && ! $is_open && $this->sends_an_unreadable_date( $data, $fields['start'], $timezone ) )
			|| ( ! $end && $this->sends_an_unreadable_date( $data, $fields['end'], $timezone ) )
		) {
			return $kind->get_error( 'invalidDate' );
		}

		$start ??= $is_open ? null : $submitted_start;
		$end   ??= $this->get_submitted_date( $data, $fields['end'], $timezone );

		[ $start, $end ] = $this->sales_window_effects->fill_saved_ends( $kind, $rule, $start, $end, $post_id, $event_dates[0] );

		if ( $kind->specific_needs_date() && ( ! ( $start || $is_open ) || ! $end ) ) {
			return null;
		}

		return new Resolved_Window( $start, $end, $timezone );
	}

	/**
	 * Compares two dates of a window at the precision of its kind.
	 *
	 * @since TBD
	 *
	 * @param DateTimeImmutable $date  The date to compare.
	 * @param DateTimeImmutable $other The date to compare it with.
	 * @param Window_Kind       $kind  The kind of window.
	 *
	 * @return int Less than, equal to, or greater than zero as the date falls before, with, or after the other.
	 */
	private function compare_dates( DateTimeImmutable $date, DateTimeImmutable $other, Window_Kind $kind ): int {
		if ( $kind->compares_days() ) {
			return strcmp( $date->format( Dates::DBDATEFORMAT ), $other->format( Dates::DBDATEFORMAT ) );
		}

		return $date <=> $other;
	}

	/**
	 * Gets the date submitted for one end of a window, read the way `ticket_add()` reads it.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>                $data     The ticket data.
	 * @param array{date: string, time: ?string} $field    The fields of the end, its date and, for a kind that submits
	 *                                                     times, its time.
	 * @param DateTimeZone                       $timezone The event timezone.
	 *
	 * @return DateTimeImmutable|null The submitted date, or `null` when none was submitted or it cannot be read.
	 */
	private function get_submitted_date( array $data, array $field, DateTimeZone $timezone ): ?DateTimeImmutable {
		$date = $data[ $field['date'] ] ?? '';
		$time = null === $field['time'] ? '' : ( $data[ $field['time'] ] ?? '' );

		if ( ! is_string( $date ) || '' === $date ) {
			return null;
		}

		// A date the datepicker format cannot read comes back `false`, which `ticket_add()` would save as 1970.
		$date = Dates::maybe_format_from_datepicker( $date );

		if ( ! is_string( $date ) || '' === $date ) {
			return null;
		}

		try {
			return new DateTimeImmutable( trim( $date . ' ' . ( is_string( $time ) ? $time : '' ) ), $timezone );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Returns whether the ticket data sends a date for one end of a window that cannot be read.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>                $data     The ticket data.
	 * @param array{date: string, time: ?string} $field    The fields of the end, its date and, for a kind that submits
	 *                                                     times, its time.
	 * @param DateTimeZone                       $timezone The event timezone.
	 *
	 * @return bool Whether a date was sent and cannot be read.
	 */
	private function sends_an_unreadable_date( array $data, array $field, DateTimeZone $timezone ): bool {
		$date = $data[ $field['date'] ] ?? '';

		return is_string( $date ) && '' !== $date && ! $this->get_submitted_date( $data, $field, $timezone );
	}
}
