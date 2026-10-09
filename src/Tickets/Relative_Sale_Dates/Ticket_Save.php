<?php
/**
 * Stores a ticket's rules, its sales window rule and its sale price rule, and writes the dates they resolve to.
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
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use TEC\Tickets\RSVP\V2\Constants as RSVP_V2_Constants;
use Tribe__Date_Utils as Dates;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_Error;

/**
 * Applies the rules of a Tickets Commerce ticket on an event when the ticket is saved.
 *
 * Each kind of window has its own rule, sent under the kind's data key. The rule sent with the ticket data replaces the
 * stored one; a save that does not send the rule keeps the stored one and applies it again, and an empty rule removes
 * it. A rule whose window the ticket does not have, such as a sale price rule without a sale price, is removed.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Ticket_Save {
	/**
	 * The ticket data key that carries the sales window rule; its value is a JSON string, an array, or `null` or `''` to
	 * remove it.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const DATA_KEY = 'relative_sale_dates';

	/**
	 * The ticket types relative sale dates never apply to: Series Passes, and RSVPs stored as Tickets Commerce tickets.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	public const EXCLUDED_TICKET_TYPES = [ Series_Passes::TICKET_TYPE, RSVP_V2_Constants::TC_RSVP_TYPE ];

	/**
	 * The store of the ticket rules.
	 *
	 * @since TBD
	 *
	 * @var Rule_Store
	 */
	private Rule_Store $rule_store;

	/**
	 * The writer of the ticket dates.
	 *
	 * @since TBD
	 *
	 * @var Ticket_Dates
	 */
	private Ticket_Dates $ticket_dates;

	/**
	 * The sales window resolver.
	 *
	 * @since TBD
	 *
	 * @var Sale_Window
	 */
	private Sale_Window $sale_window;

	/**
	 * Ticket_Save constructor.
	 *
	 * @since TBD
	 *
	 * @param Rule_Store   $rule_store   The store of the ticket rules.
	 * @param Sale_Window  $sale_window  The sales window resolver.
	 * @param Ticket_Dates $ticket_dates The writer of the ticket dates.
	 */
	public function __construct( Rule_Store $rule_store, Sale_Window $sale_window, Ticket_Dates $ticket_dates ) {
		$this->rule_store   = $rule_store;
		$this->sale_window  = $sale_window;
		$this->ticket_dates = $ticket_dates;
	}

	/**
	 * Sets the resolved dates on the ticket before the provider saves it, so it writes them with the ticket.
	 *
	 * The block editor sends the start it loaded for a ticket whose start was relative before; a Now start resolves
	 * it to now.
	 *
	 * @since TBD
	 *
	 * @param int                 $post_id The ticket parent post ID.
	 * @param Ticket_Object       $ticket  The ticket that is being saved.
	 * @param array<string,mixed> $data    The ticket data that is being saved.
	 *
	 * @return void
	 */
	public function set_ticket_dates( int $post_id, Ticket_Object $ticket, array $data ): void {
		if ( Module::class !== $ticket->provider_class || ! $this->applies_to( $post_id, $data['ticket_type'] ?? 'default' ) ) {
			return;
		}

		// Only the sales window dates are the ticket's own, which the provider saves with the ticket.
		$rule         = $this->get_rule_to_apply( $ticket->ID ?? 0, $data, Window_Kind::sales() );
		$ticket_start = $ticket->start_date ? trim( $ticket->start_date . ' ' . $ticket->start_time ) : '';
		$window       = $rule ? $this->sale_window->resolve_for_event( $rule, $post_id, $ticket_start ) : null;

		if ( ! $window ) {
			return;
		}

		$start = $window->get_start();
		if ( $start ) {
			$ticket->start_date = $start->format( Dates::DBDATEFORMAT );
			$ticket->start_time = $start->format( Dates::DBTIMEFORMAT );
		}

		$end = $window->get_end();
		if ( $end ) {
			$ticket->end_date = $end->format( Dates::DBDATEFORMAT );
			$ticket->end_time = $end->format( Dates::DBTIMEFORMAT );
		}
	}

	/**
	 * Stores the rule of each kind to apply to the ticket, or removes it when the ticket data asks to or the ticket does
	 * not have the kind's window.
	 *
	 * Tickets Commerce has saved the sale price by now, and removed it when it is unchecked or not lower than the price.
	 *
	 * @since TBD
	 *
	 * @param int                 $ticket_id The ticket post ID.
	 * @param int                 $post_id   The ticket parent post ID.
	 * @param array<string,mixed> $data      The ticket data that was saved.
	 *
	 * @return void
	 */
	public function save_rule( int $ticket_id, int $post_id, array $data ): void {
		if ( ! $this->applies_to_saved_ticket( $ticket_id, $post_id ) ) {
			return;
		}

		foreach ( Window_Kind::all() as $kind ) {
			if ( ! $kind->is_enabled_for_ticket( $ticket_id ) || $this->removes_rule( $data, $kind ) ) {
				$this->rule_store->remove_rule( $ticket_id, $kind, ! empty( $data[ $kind->get_submitted_fields()['end']['date'] ] ) );

				continue;
			}

			$rule = $this->get_rule_to_apply( $ticket_id, $data, $kind );

			if ( $rule ) {
				$this->rule_store->save_rule( $ticket_id, $rule );
			}
		}
	}

	/**
	 * Writes the dates the stored rule of each kind resolves to once the ticket is saved.
	 *
	 * `ticket_add()` replaces an empty start or end date with a default after the provider has saved the ticket,
	 * so the dates set before the save do not survive it when the ticket data leaves the start or end empty. Tickets
	 * Commerce writes the submitted sale price dates during the save, so the resolved ones go over them afterwards.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 * @param int $post_id   The ticket parent post ID.
	 *
	 * @return void
	 */
	public function write_resolved_dates( int $ticket_id, int $post_id ): void {
		if ( ! $this->applies_to_saved_ticket( $ticket_id, $post_id ) ) {
			return;
		}

		$stored = $this->rule_store->get( $ticket_id );

		foreach ( Window_Kind::all() as $kind ) {
			$rule = Rule::from_stored( $stored, $kind );

			if ( $rule ) {
				$this->ticket_dates->write( $ticket_id, $post_id, $rule );
			}
		}
	}

	/**
	 * Rejects ticket data whose rule of any kind is invalid, whose window ends before it starts, or whose window starts
	 * outside its parent window.
	 *
	 * Each kind is judged in turn, the parent first, and the first error is returned: a sales window it rejects is not
	 * judged again as the parent of the sale price. A kind the save drops, such as a sale price that is unchecked or not
	 * lower than the price, is not judged.
	 *
	 * @since TBD
	 *
	 * @param true|WP_Error       $valid   `true`, or the error an earlier callback rejected the data with.
	 * @param int                 $post_id The ticket parent post ID.
	 * @param array<string,mixed> $data    The ticket data about to be saved.
	 *
	 * @return true|WP_Error `true` when every window is valid or does not apply, the first error otherwise.
	 */
	public function validate_ticket_data( $valid, int $post_id, array $data ) {
		if (
			is_wp_error( $valid )
			|| Module::class !== ( $data['ticket_provider'] ?? Module::class )
			|| ! $this->applies_to( $post_id, $data['ticket_type'] ?? 'default' )
		) {
			return $valid;
		}

		foreach ( Window_Kind::all() as $kind ) {
			$error = $this->validate_window( $post_id, $data, $kind );

			if ( $error ) {
				return $error;
			}
		}

		return $valid;
	}

	/**
	 * Returns whether relative sale dates apply to a ticket of the given type on the given post.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id     The ticket parent post ID.
	 * @param string $ticket_type The ticket type.
	 *
	 * @return bool Whether the ticket is an event ticket of a type the rule applies to.
	 */
	private function applies_to( int $post_id, string $ticket_type ): bool {
		return 'tribe_events' === get_post_type( $post_id ) && ! in_array( $ticket_type, self::EXCLUDED_TICKET_TYPES, true );
	}

	/**
	 * Returns whether relative sale dates apply to a saved ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 * @param int $post_id   The ticket parent post ID.
	 *
	 * @return bool Whether the ticket is a Tickets Commerce ticket the rule applies to.
	 */
	private function applies_to_saved_ticket( int $ticket_id, int $post_id ): bool {
		return Ticket::POSTTYPE === get_post_type( $ticket_id )
			&& $this->applies_to( $post_id, get_post_meta( $ticket_id, Ticket::$type_meta_key, true ) ?: 'default' );
	}

	/**
	 * Returns whether the ticket data asks to remove the rule of a kind, sending it as `null` or `''`.
	 *
	 * A front-end ticket form, such as Community Events', offers no sales window options, so the dates it sends are the
	 * ones the person set: for a kind such a form removes, a save from it that does not send the rule removes the stored
	 * one. `tickets.js` tells such a form apart by sending `is_admin` as false; a REST request sends no `is_admin`, and
	 * leaving the rule out keeps it.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The ticket data.
	 * @param Window_Kind         $kind The kind of the rule.
	 *
	 * @return bool Whether the rule is to be removed.
	 */
	private function removes_rule( array $data, Window_Kind $kind ): bool {
		$key = $kind->get_rule_keys()['data'];

		if ( array_key_exists( $key, $data ) ) {
			return in_array( $data[ $key ], [ null, '' ], true );
		}

		if ( ! $kind->is_removed_by_front_end_form() ) {
			return false;
		}

		$is_admin = tec_get_request_var( 'is_admin' );

		return null !== $is_admin && ! tribe_is_truthy( $is_admin );
	}

	/**
	 * Gets the rule of a kind to apply to the ticket: the one in the ticket data, or else the stored one.
	 *
	 * A save that does not know about rules, like a plugin calling `ticket_add()` or an update of the price only, does
	 * not send one, and an invalid rule is not a request to remove the valid one already stored.
	 *
	 * @since TBD
	 *
	 * @param int                 $ticket_id The ticket post ID, or `0` for a ticket not saved yet.
	 * @param array<string,mixed> $data      The ticket data.
	 * @param Window_Kind         $kind      The kind of the rule.
	 *
	 * @return Rule|null The rule, or `null` when the ticket data removes it or neither holds a valid one.
	 */
	private function get_rule_to_apply( int $ticket_id, array $data, Window_Kind $kind ): ?Rule {
		if ( $this->removes_rule( $data, $kind ) ) {
			return null;
		}

		$rule = Rule::from_raw( $data[ $kind->get_rule_keys()['data'] ] ?? null, $kind );

		if ( $rule || ! $ticket_id ) {
			return $rule;
		}

		return Rule::from_stored( $this->rule_store->get( $ticket_id ), $kind );
	}

	/**
	 * Judges the window of one kind the ticket data would save.
	 *
	 * The rule judged is the one the save applies: the one sent, or else the one stored for the ticket. Without one, or
	 * without event dates, there is nothing to judge. The order of the window's ends is judged at the kind's precision;
	 * a start without a date of its own counts as the start of the parent window. A start with its own date must fall
	 * within the parent window, and without both ends of the parent window there is nothing to judge the window against.
	 *
	 * @since TBD
	 *
	 * @param int                 $post_id The ticket parent post ID.
	 * @param array<string,mixed> $data    The ticket data about to be saved.
	 * @param Window_Kind         $kind    The kind of window to judge.
	 *
	 * @return WP_Error|null The error of the kind that rejects the window, or `null` when it is valid or not judged.
	 */
	private function validate_window( int $post_id, array $data, Window_Kind $kind ): ?WP_Error {
		if ( $this->removes_rule( $data, $kind ) || ! $kind->is_saved_with( $data ) ) {
			return null;
		}

		$key = $kind->get_rule_keys()['data'];

		// The save would keep the stored rule, but the admin who sent this one expects it to apply.
		if ( isset( $data[ $key ] ) && ! Rule::from_raw( $data[ $key ], $kind ) ) {
			return $kind->get_ends_before_start_error();
		}

		$ticket_id   = absint( $data['ticket_id'] ?? 0 );
		$rule        = $this->get_rule_to_apply( $ticket_id, $data, $kind );
		$event_dates = $this->sale_window->get_event_dates( $post_id );

		if ( ! $rule || ! $event_dates ) {
			return null;
		}

		$window = $this->get_window( $post_id, $data, $kind, $rule, $event_dates );

		if ( ! $window ) {
			return $kind->get_ends_before_start_error();
		}

		$parent        = $kind->get_parent();
		$parent_window = $parent ? $this->get_window( $post_id, $data, $parent, $this->get_rule_to_apply( $ticket_id, $data, $parent ), $event_dates ) : null;
		$parent_start  = $parent_window ? $parent_window->get_start() : null;
		$parent_end    = $parent_window ? $parent_window->get_end() : null;

		if ( $parent && ! ( $parent_start && $parent_end ) ) {
			return null;
		}

		$own_start = $window->get_start();
		$start     = $own_start ?? $parent_start;
		$end       = $window->get_end();

		if ( $start && $end && $this->compare_dates( $end, $start, $kind ) <= 0 ) {
			return $kind->get_ends_before_start_error();
		}

		if (
			$own_start && $parent_start && $parent_end
			&& ( $this->compare_dates( $own_start, $parent_start, $kind ) < 0 || $this->compare_dates( $own_start, $parent_end, $kind ) > 0 )
		) {
			return $kind->get_outside_parent_error();
		}

		return null;
	}

	/**
	 * Gets the window of a kind a save of the ticket data would store.
	 *
	 * The rule applied is the one sent, or else the one stored for the ticket. A boundary the rule leaves to the ticket
	 * takes its submitted date, except a Now start of the ticket sales submitted ahead of now, which the save moves to
	 * now. An open start of a kind that writes one as a value of its own has no date: the window opens with its parent.
	 * For the ticket's own sales dates, `ticket_add()` fills an empty start with the day the event was published and an
	 * empty end with the event start; here the start fallback applies only to an open start or a ticket without a rule,
	 * and the end fallback only to a ticket without a rule, so a `specific` boundary sent without its date has none.
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
	 * @return Resolved_Window|null The window, whose ends are `null` where it has no date; `null` when a submitted date
	 *                              cannot be read, or a boundary of a kind that needs a date has none.
	 */
	private function get_window( int $post_id, array $data, Window_Kind $kind, ?Rule $rule, array $event_dates ): ?Resolved_Window {
		$timezone = $event_dates[0]->getTimezone();
		$fields   = $kind->get_submitted_fields();
		$opens    = $rule && $rule->opens_at_once();
		$is_open  = $opens && null !== $kind->get_open_start_value();

		$submitted_start = $this->get_submitted_date( $data, $fields['start'], $timezone );
		$window          = $rule ? $this->sale_window->resolve_for_event( $rule, $post_id, $submitted_start ? $submitted_start->format( 'Y-m-d H:i:s' ) : '' ) : null;
		$start           = $window ? $window->get_start() : null;
		$end             = $window ? $window->get_end() : null;

		// A date the datepicker format cannot read would be stored as 1970, or as no date.
		if (
			( ! $start && ! $is_open && $this->sends_an_unreadable_date( $data, $fields['start'], $timezone ) )
			|| ( ! $end && $this->sends_an_unreadable_date( $data, $fields['end'], $timezone ) )
		) {
			return null;
		}

		$start ??= $is_open ? null : $submitted_start;
		$end   ??= $this->get_submitted_date( $data, $fields['end'], $timezone );

		if ( $kind->owns_ticket_sales_dates() ) {
			$start ??= ! $rule || $opens ? $this->get_post_day( $post_id, $timezone ) : null;
			$end   ??= $rule ? null : $event_dates[0];
		}

		if ( $kind->specific_needs_date() && ( ! ( $start || $is_open ) || ! $end ) ) {
			return null;
		}

		return new Resolved_Window( $start, $end );
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

	/**
	 * Gets the day the event was published, which `ticket_add()` stores as the start of a ticket sent without one.
	 *
	 * @since TBD
	 *
	 * @param int          $post_id  The event post ID.
	 * @param DateTimeZone $timezone The event timezone.
	 *
	 * @return DateTimeImmutable|null Midnight of the day the event was published, or `null` when its date cannot be read.
	 */
	private function get_post_day( int $post_id, DateTimeZone $timezone ): ?DateTimeImmutable {
		$date = date_create_immutable( get_post_field( 'post_date', $post_id, 'raw' ), $timezone );

		return $date ? $date->setTime( 0, 0 ) : null;
	}
}
