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
	 * A Now start puts the ticket on sale when it is saved, even when the ticket data carries a later start, which the
	 * block editor sends for a ticket whose start was relative before.
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

		$rule   = $this->get_rule( $ticket->ID ?? 0, $data );
		$window = $rule ? $this->resolve( $post_id, $rule ) : null;

		if ( ! $window ) {
			return;
		}

		$start = $window->get_start();
		if ( ! $start && Rule::MODE_DEFAULT === $rule->get_start()->get_mode() ) {
			$start = $this->get_default_start( $post_id, $ticket );
		}

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
	 * Stores the rule, or removes it, and writes the resolved dates again once the ticket is saved.
	 *
	 * `ticket_add()` replaces an empty start or end date with a default after the provider has saved the ticket,
	 * so the dates set before the save do not survive it when the ticket data leaves the start or end empty.
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
		if (
			Ticket::POSTTYPE !== get_post_type( $ticket_id )
			|| ! $this->applies_to( $post_id, get_post_meta( $ticket_id, Ticket::$type_meta_key, true ) ?: 'default' )
		) {
			return;
		}

		if ( $this->removes_rule( $data ) ) {
			$this->rule_store->remove_sales_window( $ticket_id, ! empty( $data['ticket_end_date'] ) );

			return;
		}

		$rule = $this->get_rule( $ticket_id, $data );

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

		$window = $this->resolve( $post_id, $rule );

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
	private function get_rule( int $ticket_id, array $data ): ?Rule {
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
	 * Resolves a rule against the event's dates.
	 *
	 * @since TBD
	 *
	 * @param int  $post_id The event post ID.
	 * @param Rule $rule    The sales window rule.
	 *
	 * @return Resolved_Window|null The resolved window, or `null` when the event has no valid dates.
	 */
	private function resolve( int $post_id, Rule $rule ): ?Resolved_Window {
		$start = get_post_meta( $post_id, '_EventStartDate', true );
		$end   = get_post_meta( $post_id, '_EventEndDate', true );

		if ( ! is_string( $start ) || '' === $start || ! is_string( $end ) || '' === $end ) {
			return null;
		}

		$timezone = $this->get_event_timezone( $post_id );

		try {
			$event_start = new DateTimeImmutable( $start, $timezone );
			$event_end   = new DateTimeImmutable( $end, $timezone );
		} catch ( Exception $e ) {
			return null;
		}

		return $this->sale_window->resolve( $rule, $event_start, $event_end );
	}

	/**
	 * Gets the start a Now boundary moves the ticket to: now, when the ticket data would start the sales later.
	 *
	 * A ticket already on sale keeps its start, so saving it again does not move the start forward.
	 *
	 * @since TBD
	 *
	 * @param int           $post_id The event post ID.
	 * @param Ticket_Object $ticket  The ticket that is being saved.
	 *
	 * @return DateTimeImmutable|null Now, in the event timezone, or `null` to keep the start the ticket data carries.
	 */
	private function get_default_start( int $post_id, Ticket_Object $ticket ): ?DateTimeImmutable {
		if ( empty( $ticket->start_date ) ) {
			return null;
		}

		$timezone = $this->get_event_timezone( $post_id );

		try {
			$submitted = new DateTimeImmutable( trim( $ticket->start_date . ' ' . $ticket->start_time ), $timezone );
		} catch ( Exception $e ) {
			return null;
		}

		$now = new DateTimeImmutable( 'now', $timezone );

		return $submitted > $now ? $now : null;
	}

	/**
	 * Gets the timezone of an event.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The event post ID.
	 *
	 * @return DateTimeZone The event timezone, or the site one when the event has none.
	 */
	private function get_event_timezone( int $post_id ): DateTimeZone {
		return Timezones::build_timezone_object( get_post_meta( $post_id, '_EventTimezone', true ) ?: null );
	}
}
