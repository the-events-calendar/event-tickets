<?php
/**
 * Stores a ticket's sales window rule and writes the dates it resolves to.
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
use InvalidArgumentException;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use Tribe__Date_Utils as Dates;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Timezones as Timezones;
use WP_Error;

/**
 * Applies the rule of a Tickets Commerce ticket on an event when the ticket is saved.
 *
 * The rule sent with the ticket data replaces the stored one; a save that does not send the rule keeps the stored one
 * and applies it again, and an empty rule removes it.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Ticket_Save {
	/**
	 * The ticket data key that carries the rule; its value is a JSON string, an array, or `null` or `''` to remove it.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const DATA_KEY = 'relative_sale_dates';

	/**
	 * The store of the ticket rules.
	 *
	 * @since TBD
	 *
	 * @var Rule_Store
	 */
	private Rule_Store $rule_store;

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
	 * @param Rule_Store  $rule_store  The store of the ticket rules.
	 * @param Sale_Window $sale_window The sales window resolver.
	 */
	public function __construct( Rule_Store $rule_store, Sale_Window $sale_window ) {
		$this->rule_store  = $rule_store;
		$this->sale_window = $sale_window;
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

		$rule         = $this->get_rule_to_apply( $ticket->ID ?? 0, $data );
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
	 * Stores the rule to apply to the ticket, or removes it when the ticket data asks to.
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

		if ( $this->removes_rule( $data ) ) {
			$this->rule_store->remove_sales_window( $ticket_id, ! empty( $data['ticket_end_date'] ) );

			return;
		}

		$rule = $this->get_rule_to_apply( $ticket_id, $data );

		if ( ! $rule ) {
			return;
		}

		$this->rule_store->save(
			$ticket_id,
			[
				'start' => $rule->get_start(),
				'end'   => $rule->get_end(),
			]
		);
	}

	/**
	 * Writes the dates the stored rule resolves to once the ticket is saved.
	 *
	 * `ticket_add()` replaces an empty start or end date with a default after the provider has saved the ticket,
	 * so the dates set before the save do not survive it when the ticket data leaves the start or end empty.
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

		$rule   = Rule::from_stored( $this->rule_store->get( $ticket_id ) );
		$window = $rule ? $this->sale_window->resolve_for_event( $rule, $post_id ) : null;

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

	/**
	 * Rejects ticket data whose rule is invalid or whose sales window does not start before it ends.
	 *
	 * An end the rule leaves to the ticket, in `specific` mode, is judged with the date submitted for it, and rejected
	 * when none was.
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
		if (
			is_wp_error( $valid )
			|| empty( $data[ self::DATA_KEY ] )
			|| Module::class !== ( $data['ticket_provider'] ?? Module::class )
			|| ! $this->applies_to( $post_id, $data['ticket_type'] ?? 'default' )
		) {
			return $valid;
		}

		$rule = $this->parse_rule( $data[ self::DATA_KEY ] );

		if ( ! $rule ) {
			return $this->get_invalid_window_error();
		}

		$event_dates = $this->get_event_dates( $post_id );

		if ( ! $event_dates ) {
			return $valid;
		}

		$window         = $this->sale_window->resolve( $rule, ...$event_dates );
		$timezone       = $event_dates[0]->getTimezone();
		$specific_start = Rule::MODE_SPECIFIC === $rule->get_start()['mode'];
		$specific_end   = Rule::MODE_SPECIFIC === $rule->get_end()['mode'];
		$start          = $specific_start ? $this->get_submitted_date( $data, 'start', $timezone ) : $window->get_start();
		$end            = $specific_end ? $this->get_submitted_date( $data, 'end', $timezone ) : $window->get_end();

		// Without its date, `ticket_add()` would default a specific end and the window could not be checked.
		if ( ( $specific_start && ! $start ) || ( $specific_end && ! $end ) ) {
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
	 * @return bool Whether the ticket is an event ticket that is not a Series Pass.
	 */
	private function applies_to( int $post_id, string $ticket_type ): bool {
		return 'tribe_events' === get_post_type( $post_id ) && Series_Passes::TICKET_TYPE !== $ticket_type;
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
	 * Returns whether the ticket data asks to remove the rule, sending it as `null` or `''`.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The ticket data.
	 *
	 * @return bool Whether the rule is to be removed.
	 */
	private function removes_rule( array $data ): bool {
		return array_key_exists( self::DATA_KEY, $data ) && in_array( $data[ self::DATA_KEY ], [ null, '' ], true );
	}

	/**
	 * Gets the rule to apply to the ticket: the one in the ticket data, or else the stored one.
	 *
	 * A save that does not know about rules, like a plugin calling `ticket_add()` or an update of the price only, does
	 * not send one, and an invalid rule is not a request to remove the valid one already stored.
	 *
	 * @since TBD
	 *
	 * @param int                 $ticket_id The ticket post ID, or `0` for a ticket not saved yet.
	 * @param array<string,mixed> $data      The ticket data.
	 *
	 * @return Rule|null The rule, or `null` when the ticket data removes it or neither holds a valid one.
	 */
	private function get_rule_to_apply( int $ticket_id, array $data ): ?Rule {
		if ( $this->removes_rule( $data ) ) {
			return null;
		}

		$rule = $this->parse_rule( $data[ self::DATA_KEY ] ?? null );

		if ( $rule || ! $ticket_id ) {
			return $rule;
		}

		return Rule::from_stored( $this->rule_store->get( $ticket_id ) );
	}

	/**
	 * Builds a rule from the value the ticket data sends for it.
	 *
	 * @since TBD
	 *
	 * @param mixed $raw The rule as sent, a JSON string or an array.
	 *
	 * @return Rule|null The rule, or `null` when nothing valid was sent.
	 */
	private function parse_rule( $raw ): ?Rule {
		try {
			if ( is_array( $raw ) ) {
				return Rule::from_array( $raw );
			}

			if ( is_string( $raw ) ) {
				return Rule::from_json( $raw );
			}
		} catch ( InvalidArgumentException $e ) {
			return null;
		}

		return null;
	}

	/**
	 * Gets the event's start and end in the event timezone.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The event post ID.
	 *
	 * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}|null The event start and end, or `null` when the event has no valid dates.
	 */
	private function get_event_dates( int $post_id ): ?array {
		$start = get_post_meta( $post_id, '_EventStartDate', true );
		$end   = get_post_meta( $post_id, '_EventEndDate', true );

		if ( ! is_string( $start ) || '' === $start || ! is_string( $end ) || '' === $end ) {
			return null;
		}

		$timezone = Timezones::build_timezone_object( get_post_meta( $post_id, '_EventTimezone', true ) ?: null );

		try {
			return [ new DateTimeImmutable( $start, $timezone ), new DateTimeImmutable( $end, $timezone ) ];
		} catch ( Exception $e ) {
			return null;
		}
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
	 * @return DateTimeImmutable|null The submitted date, or `null` when none was submitted.
	 */
	private function get_submitted_date( array $data, string $end, DateTimeZone $timezone ): ?DateTimeImmutable {
		$date = $data[ "ticket_{$end}_date" ] ?? '';
		$time = $data[ "ticket_{$end}_time" ] ?? '';

		if ( ! is_string( $date ) || '' === $date ) {
			return null;
		}

		try {
			return new DateTimeImmutable( trim( Dates::maybe_format_from_datepicker( $date ) . ' ' . ( is_string( $time ) ? $time : '' ) ), $timezone );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Gets the error that rejects a sales window that does not start before it ends.
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
