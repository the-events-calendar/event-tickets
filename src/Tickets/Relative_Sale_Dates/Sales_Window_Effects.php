<?php
/**
 * What a window whose dates are the ticket's own sales dates does beyond resolving and storing them.
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
use TEC\Tickets\Ticket_Actions;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;

/**
 * The effects only the sales window has, so the rules, resolver, store and writer handle every kind of window alike.
 *
 * The sales window's dates are the ones the ticket is on sale between: `ticket_add()` fills them in, the sales actions
 * announce them, and the legacy event start sync moves the end. Every method here leaves another kind's window as it is.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Sales_Window_Effects {
	/**
	 * The tickets handler, which keeps the manual-update flags of the ticket dates.
	 *
	 * @since TBD
	 *
	 * @var Tickets_Handler
	 */
	private Tickets_Handler $tickets_handler;

	/**
	 * The scheduler of the "sales started" and "sales ended" actions.
	 *
	 * @since TBD
	 *
	 * @var Ticket_Actions
	 */
	private Ticket_Actions $ticket_actions;

	/**
	 * Sales_Window_Effects constructor.
	 *
	 * @since TBD
	 *
	 * @param Tickets_Handler $tickets_handler The tickets handler.
	 * @param Ticket_Actions  $ticket_actions  The scheduler of the sales actions.
	 */
	public function __construct( Tickets_Handler $tickets_handler, Ticket_Actions $ticket_actions ) {
		$this->tickets_handler = $tickets_handler;
		$this->ticket_actions  = $ticket_actions;
	}

	/**
	 * Moves the start of a sales window that opens at once to now, when the ticket start is later than now.
	 *
	 * A Now start puts the ticket on sale. A start that has already passed, or no start, is left to the ticket, so saving
	 * a ticket on sale again does not move its start.
	 *
	 * @since TBD
	 *
	 * @param Resolved_Window $window       The resolved window.
	 * @param Rule            $rule         The rule the window was resolved from.
	 * @param string          $ticket_start The start the ticket is saved with, as `Y-m-d H:i:s` in the event timezone, or `''`.
	 *
	 * @return Resolved_Window The window, with now as its start when the start moves.
	 */
	public function apply_now_start( Resolved_Window $window, Rule $rule, string $ticket_start ): Resolved_Window {
		if ( ! $rule->get_kind()->owns_ticket_sales_dates() || ! $rule->opens_at_once() ) {
			return $window;
		}

		$now_start = $this->get_now_start( $ticket_start, $window->get_timezone() );

		return $now_start ? new Resolved_Window( $now_start, $window->get_end(), $window->get_timezone() ) : $window;
	}

	/**
	 * Gets the metas written along with the dates of a window, beyond the dates themselves.
	 *
	 * The sales dates are wall-clock times in the event timezone, so the timezone is kept with them: an event moved to
	 * another timezone with the same times changes the instants the sales actions announce.
	 *
	 * @since TBD
	 *
	 * @param Resolved_Window $window The resolved window.
	 * @param Window_Kind     $kind   The kind of the window.
	 *
	 * @return array<string,string> The values, keyed by their meta key.
	 */
	public function get_extra_metas( Resolved_Window $window, Window_Kind $kind ): array {
		if ( ! $kind->owns_ticket_sales_dates() ) {
			return [];
		}

		return [ Ticket_Dates::TIMEZONE_META_KEY => $window->get_timezone()->getName() ];
	}

	/**
	 * Reschedules the sales actions of a ticket whose window dates an event move changed.
	 *
	 * A move can push a relative boundary past a specific one. The inverted window is kept, so the ticket is off sale,
	 * but Ticket_Actions skips it before unscheduling, which would leave the old sales actions behind. Every pending
	 * action goes, not only the next one: overlapping saves can leave two.
	 *
	 * @since TBD
	 *
	 * @param int         $ticket_id The ticket post ID.
	 * @param Window_Kind $kind      The kind of the window whose dates changed.
	 *
	 * @return void
	 */
	public function reschedule_sales_actions( int $ticket_id, Window_Kind $kind ): void {
		if ( ! $kind->owns_ticket_sales_dates() ) {
			return;
		}

		as_unschedule_all_actions( Ticket_Actions::TICKET_START_SALES_HOOK, [ $ticket_id ], Ticket_Actions::AS_TICKET_ACTIONS_GROUP );
		as_unschedule_all_actions( Ticket_Actions::TICKET_END_SALES_HOOK, [ $ticket_id ], Ticket_Actions::AS_TICKET_ACTIONS_GROUP );
		$this->ticket_actions->sync_ticket_dates_actions( $ticket_id );
	}

	/**
	 * Returns whether the ticket's sale end may follow the event start when the event moves.
	 *
	 * A specific end leaves the end to the ticket, as a ticket without a rule does, and the classic editor stores such a
	 * rule the first time it saves a ticket made before the feature. The end stays put when the start counts back from
	 * the event end, which could fall after an end moved to the event start.
	 *
	 * @since TBD
	 *
	 * @param Rule $rule The ticket's rule.
	 *
	 * @return bool Whether the sale end may follow the event start.
	 */
	public function lets_end_follow_event_start( Rule $rule ): bool {
		return $rule->get_kind()->owns_ticket_sales_dates()
			&& Rule::MODE_SPECIFIC === $rule->get_end()->get_mode()
			&& Rule::ANCHOR_END !== $rule->get_start()->get_anchor();
	}

	/**
	 * Flags an end a new sales window rule leaves to the ticket as set by hand, so it stays where it is.
	 *
	 * A relative or default end had its date written by the rule, so it carries no manual-update flag, and switching it
	 * to a specific end on the same date writes no new end date that would add one. Without the flag, the next event
	 * move would give that end the event start.
	 *
	 * @since TBD
	 *
	 * @param int       $ticket_id The ticket post ID.
	 * @param Rule|null $previous  The rule of the same kind stored before this save, or `null` for none.
	 * @param Rule      $rule      The rule saved.
	 *
	 * @return void
	 */
	public function flag_end_left_to_ticket( int $ticket_id, ?Rule $previous, Rule $rule ): void {
		if (
			! $rule->get_kind()->owns_ticket_sales_dates()
			|| ! $previous
			|| Rule::MODE_SPECIFIC === $previous->get_end()->get_mode()
			|| Rule::MODE_SPECIFIC !== $rule->get_end()->get_mode()
			|| $this->tickets_handler->has_manual_update( $ticket_id, $this->tickets_handler->key_end_date )
		) {
			return;
		}

		add_post_meta( $ticket_id, $this->tickets_handler->key_manual_updated, $this->tickets_handler->key_end_date );
	}

	/**
	 * Gives the ticket end back to the event start once its sales window rule is removed.
	 *
	 * The end the rule resolved was flagged as a manual one when the ticket was saved, which stops it from following the
	 * event start; unless the save that removes the rule keeps an end of its own, the flag goes with the rule.
	 *
	 * @since TBD
	 *
	 * @param int         $ticket_id The ticket post ID.
	 * @param Window_Kind $kind      The kind of the removed rule.
	 * @param bool        $keeps_end Whether the save that removes the rule sends an end date of its own.
	 *
	 * @return void
	 */
	public function release_end( int $ticket_id, Window_Kind $kind, bool $keeps_end ): void {
		if ( ! $kind->owns_ticket_sales_dates() || $keeps_end ) {
			return;
		}

		delete_post_meta( $ticket_id, $this->tickets_handler->key_manual_updated, $this->tickets_handler->key_end_date );
	}

	/**
	 * Deletes the timezone the sales window dates were written in once the sales window rule is removed, as nothing
	 * reads it without the rule.
	 *
	 * @since TBD
	 *
	 * @param int         $ticket_id The ticket post ID.
	 * @param Window_Kind $kind      The kind of the removed rule.
	 *
	 * @return void
	 */
	public function forget_timezone( int $ticket_id, Window_Kind $kind ): void {
		if ( ! $kind->owns_ticket_sales_dates() ) {
			return;
		}

		delete_post_meta( $ticket_id, Ticket_Dates::TIMEZONE_META_KEY );
	}

	/**
	 * Gets the start a Now boundary moves the ticket to: now, when the ticket start is later.
	 *
	 * @since TBD
	 *
	 * @param string       $ticket_start The start the ticket is saved with, as `Y-m-d H:i:s` in the event timezone, or `''`.
	 * @param DateTimeZone $timezone     The event timezone.
	 *
	 * @return DateTimeImmutable|null Now, in the event timezone, or `null` to leave the start to the ticket.
	 */
	private function get_now_start( string $ticket_start, DateTimeZone $timezone ): ?DateTimeImmutable {
		if ( '' === $ticket_start ) {
			return null;
		}

		try {
			$start = new DateTimeImmutable( $ticket_start, $timezone );
		} catch ( Exception $e ) {
			return null;
		}

		$now = new DateTimeImmutable( 'now', $timezone );

		return $start > $now ? $now : null;
	}
}
