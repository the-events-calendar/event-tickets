<?php
/**
 * Resolves ticket dates again when an event's dates change.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use TEC\Tickets\Ticket_Actions;

/**
 * Keeps the dates of the tickets that have a rule in step with their event.
 *
 * The Events Calendar saves an event's occurrences once per save, after the save has written every date meta, so the
 * tickets are resolved then, once. With its custom tables turned off nothing saves occurrences, and the tickets keep
 * their dates until they are saved again, as Series Passes do.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Event_Listener {
	/**
	 * The store of the ticket rules.
	 *
	 * @since TBD
	 *
	 * @var Rule_Store
	 */
	private Rule_Store $rule_store;

	/**
	 * The resolver and writer of the ticket dates.
	 *
	 * @since TBD
	 *
	 * @var Ticket_Dates
	 */
	private Ticket_Dates $ticket_dates;

	/**
	 * The scheduler of the "sales started" and "sales ended" actions.
	 *
	 * @since TBD
	 *
	 * @var Ticket_Actions
	 */
	private Ticket_Actions $ticket_actions;

	/**
	 * Event_Listener constructor.
	 *
	 * @since TBD
	 *
	 * @param Rule_Store     $rule_store     The store of the ticket rules.
	 * @param Ticket_Dates   $ticket_dates   The resolver and writer of the ticket dates.
	 * @param Ticket_Actions $ticket_actions The scheduler of the sales actions.
	 */
	public function __construct( Rule_Store $rule_store, Ticket_Dates $ticket_dates, Ticket_Actions $ticket_actions ) {
		$this->rule_store     = $rule_store;
		$this->ticket_dates   = $ticket_dates;
		$this->ticket_actions = $ticket_actions;
	}

	/**
	 * Rewrites the resolved dates of an event's ruled tickets and reschedules the sales actions of those whose dates
	 * changed, once the event's occurrences are saved.
	 *
	 * A window the move inverts keeps no sales action: the ticket is off sale until its dates are fixed.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The event post ID.
	 *
	 * @return void
	 */
	public function update_ticket_dates( int $post_id ): void {
		$ticket_ids = $this->rule_store->get_ticket_ids_for_event( $post_id );
		// The query returns IDs only, which leaves the meta of each ticket to a query of its own.
		update_meta_cache( 'post', $ticket_ids );

		foreach ( $ticket_ids as $ticket_id ) {
			$rule = $this->get_stored_rule( $ticket_id );

			if ( ! $rule ) {
				continue;
			}

			// Rescheduling fires the sales actions other plugins listen to, so a ticket whose dates stay put keeps its own.
			if ( ! $this->ticket_dates->write( $ticket_id, $post_id, $rule ) ) {
				continue;
			}

			/*
			 * A move can push a relative boundary past a specific one. The inverted window is kept, so the ticket is
			 * off sale, but Ticket_Actions skips it before unscheduling, which would leave the old sales actions behind.
			 * Every pending action goes, not only the next one: overlapping saves can leave two.
			 */
			as_unschedule_all_actions( Ticket_Actions::TICKET_START_SALES_HOOK, [ $ticket_id ], Ticket_Actions::AS_TICKET_ACTIONS_GROUP );
			as_unschedule_all_actions( Ticket_Actions::TICKET_END_SALES_HOOK, [ $ticket_id ], Ticket_Actions::AS_TICKET_ACTIONS_GROUP );
			$this->ticket_actions->sync_ticket_dates_actions( $ticket_id );
		}
	}

	/**
	 * Returns whether a ticket's sale end date follows its event's start: not when the ticket's rule resolves the end.
	 *
	 * @since TBD
	 *
	 * @param bool $follows   Whether the ticket's sale end date follows the event start.
	 * @param int  $ticket_id The ticket post ID.
	 *
	 * @return bool Whether the ticket's sale end date follows the event start.
	 */
	public function filter_end_date_follows_event_start( $follows, int $ticket_id ): bool {
		$rule = $this->get_stored_rule( $ticket_id );

		return tribe_is_truthy( $follows ) && ( ! $rule || $rule->lets_end_follow_event_start() );
	}

	/**
	 * Copies the rules of the tickets duplicated to another event.
	 *
	 * @since TBD
	 *
	 * @param array<int,int|false> $duplicated_ticket_ids The duplicated ticket IDs, keyed by the original ticket IDs; `false`
	 *                                                    for a ticket that could not be cloned.
	 *
	 * @return void
	 */
	public function copy_rules_to_duplicates( $duplicated_ticket_ids ): void {
		if ( ! is_array( $duplicated_ticket_ids ) ) {
			return;
		}

		foreach ( $duplicated_ticket_ids as $original_ticket_id => $duplicate_ticket_id ) {
			$stored = $this->rule_store->get( absint( $original_ticket_id ) );

			if ( ! $duplicate_ticket_id || ! $stored ) {
				continue;
			}

			$this->rule_store->save( absint( $duplicate_ticket_id ), $stored );
		}
	}

	/**
	 * Rewrites the dates of the tickets duplicated to another event against that event's dates.
	 *
	 * Cloning a ticket copies its dates only, so without this a duplicate would keep the original event's dates.
	 *
	 * @since TBD
	 *
	 * @param array<int,int|false> $duplicated_ticket_ids The duplicated ticket IDs, keyed by the original ticket IDs; `false`
	 *                                                    for a ticket that could not be cloned.
	 * @param int                  $new_post_id           The post the tickets were duplicated to.
	 *
	 * @return void
	 */
	public function update_duplicated_tickets( $duplicated_ticket_ids, int $new_post_id ): void {
		$duplicate_ids = is_array( $duplicated_ticket_ids ) ? array_filter( array_map( 'absint', $duplicated_ticket_ids ) ) : [];

		// Only when a duplicate got rules, as before: the event's other ruled tickets are already resolved.
		if ( array_intersect( $duplicate_ids, $this->rule_store->get_ticket_ids_for_event( $new_post_id ) ) ) {
			$this->update_ticket_dates( $new_post_id );
		}
	}

	/**
	 * Reads the stored sales window rule of a ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return Rule|null The rule, or `null` when the ticket has no valid stored rule.
	 */
	private function get_stored_rule( int $ticket_id ): ?Rule {
		return Rule::from_stored( $this->rule_store->get( $ticket_id ) );
	}
}
