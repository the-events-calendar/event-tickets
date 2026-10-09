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
				$this->rule_store->remove_rule( $ticket_id, $kind, ! empty( $data['ticket_end_date'] ) );

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
	 * Rejects ticket data whose rule is invalid or whose sales window does not start before it ends.
	 *
	 * The rule judged is the one the save applies: the one sent, or else the one stored for the ticket. A boundary the
	 * rule leaves to the ticket is judged with the date the save stores for it: the submitted date, now for a `default`
	 * start submitted ahead of now, or for a `default` start sent without one, the day the event was published. A
	 * `specific` boundary sent without its date is rejected.
	 *
	 * @since TBD
	 *
	 * @param true|WP_Error       $valid   `true`, or the error an earlier callback rejected the data with.
	 * @param int                 $post_id The ticket parent post ID.
	 * @param array<string,mixed> $data    The ticket data about to be saved.
	 *
	 * @return true|WP_Error `true` when the sales window is valid or does not apply, the error otherwise.
	 */
	public function validate_ticket_data( $valid, int $post_id, array $data ) {
		$kind = Window_Kind::sales();

		if (
			is_wp_error( $valid )
			|| $this->removes_rule( $data, $kind )
			|| Module::class !== ( $data['ticket_provider'] ?? Module::class )
			|| ! $this->applies_to( $post_id, $data['ticket_type'] ?? 'default' )
		) {
			return $valid;
		}

		// The save would keep the stored rule, but the admin who sent this one expects it to apply.
		if ( isset( $data[ self::DATA_KEY ] ) && ! Rule::from_raw( $data[ self::DATA_KEY ], $kind ) ) {
			return $this->get_invalid_window_error();
		}

		$rule        = $this->get_rule_to_apply( absint( $data['ticket_id'] ?? 0 ), $data, $kind );
		$event_dates = $rule ? $this->sale_window->get_event_dates( $post_id ) : null;

		if ( ! $event_dates ) {
			return $valid;
		}

		$timezone  = $event_dates[0]->getTimezone();
		$submitted = $this->get_submitted_date( $data, 'start', $timezone );
		$window    = $this->sale_window->resolve_for_event( $rule, $post_id, $submitted ? $submitted->format( 'Y-m-d H:i:s' ) : '' );
		$start     = ( $window ? $window->get_start() : null ) ?? $submitted;
		$end       = ( $window ? $window->get_end() : null ) ?? $this->get_submitted_date( $data, 'end', $timezone );

		if ( ! $start && Rule::MODE_DEFAULT === $rule->get_start()->get_mode() && empty( $data['ticket_start_date'] ) ) {
			$start = $this->get_post_day( $post_id, $timezone );
		}

		if ( ! ( $start && $end ) ) {
			return $this->get_invalid_window_error();
		}

		return ( new Resolved_Window( $start, $end ) )->is_valid() ? $valid : $this->get_invalid_window_error();
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
		$key = $kind->get_data_key();

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

		$rule = Rule::from_raw( $data[ $kind->get_data_key() ] ?? null, $kind );

		if ( $rule || ! $ticket_id ) {
			return $rule;
		}

		return Rule::from_stored( $this->rule_store->get( $ticket_id ), $kind );
	}

	/**
	 * Gets the date submitted for one end of the sales window, read the way `ticket_add()` reads it.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data     The ticket data.
	 * @param string              $end      The end of the window, `start` or `end`.
	 * @param DateTimeZone        $timezone The event timezone.
	 *
	 * @return DateTimeImmutable|null The submitted date, or `null` when none was submitted or it cannot be read.
	 */
	private function get_submitted_date( array $data, string $end, DateTimeZone $timezone ): ?DateTimeImmutable {
		$date = $data[ "ticket_{$end}_date" ] ?? '';
		$time = $data[ "ticket_{$end}_time" ] ?? '';

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

	/**
	 * Gets the error that rejects an invalid rule, or a sales window that does not start before it ends.
	 *
	 * @since TBD
	 *
	 * @return WP_Error The error, with a 400 status for REST responses.
	 */
	private function get_invalid_window_error(): WP_Error {
		return new WP_Error(
			'tec_tickets_relative_sale_dates_invalid_window',
			__( 'Ticket sales cannot end before they start. Please adjust the sales window.', 'event-tickets' ),
			[ 'status' => 400 ]
		);
	}
}
