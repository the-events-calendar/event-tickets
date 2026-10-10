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
 * tickets are resolved then, once. With its custom tables off nothing saves occurrences: the event is marked when one of
 * its dates is written, and its tickets are resolved once the save is done, or on shutdown for a change made outside a
 * post save.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Event_Listener {
	/**
	 * The event metas the sales window is resolved from.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	private const EVENT_DATE_META_KEYS = [ '_EventStartDate', '_EventEndDate', '_EventTimezone' ];

	/**
	 * The store of the ticket rules.
	 *
	 * @since TBD
	 *
	 * @var Rule_Store
	 */
	private Rule_Store $rule_store;

	/**
	 * The writer of the dates of every kind of window.
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
	 * The events whose dates changed in this request and whose tickets are not resolved yet, as keys.
	 *
	 * @since TBD
	 *
	 * @var array<int,true>
	 */
	private array $moved_event_ids = [];

	/**
	 * Event_Listener constructor.
	 *
	 * @since TBD
	 *
	 * @param Rule_Store     $rule_store     The store of the ticket rules.
	 * @param Ticket_Dates   $ticket_dates   The writer of the dates of every kind of window.
	 * @param Ticket_Actions $ticket_actions The scheduler of the sales actions.
	 */
	public function __construct( Rule_Store $rule_store, Ticket_Dates $ticket_dates, Ticket_Actions $ticket_actions ) {
		$this->rule_store     = $rule_store;
		$this->ticket_dates   = $ticket_dates;
		$this->ticket_actions = $ticket_actions;
	}

	/**
	 * Rewrites the resolved dates of every rule of an event's ruled tickets and reschedules the sales actions of those
	 * whose sales window dates changed, once the event's occurrences are saved.
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
		$this->update_tickets( $post_id, $this->rule_store->get_ticket_ids_for_event( $post_id ) );
	}

	/**
	 * Marks an event whose start, end or timezone was written while The Events Calendar's custom tables are not active.
	 *
	 * @since TBD
	 *
	 * @param int    $meta_id  The meta ID.
	 * @param int    $post_id  The post ID.
	 * @param string $meta_key The meta key.
	 *
	 * @return void
	 */
	public function mark_moved_event( int $meta_id, int $post_id, string $meta_key ): void {
		if (
			in_array( $meta_key, self::EVENT_DATE_META_KEYS, true )
			&& 'tribe_events' === get_post_type( $post_id )
			&& ! $this->saves_occurrences()
		) {
			$this->moved_event_ids[ $post_id ] = true;
		}
	}

	/**
	 * Rewrites the dates of the ruled tickets of a marked event once its save, meta included, is done.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The saved post ID.
	 *
	 * @return void
	 */
	public function update_saved_event_tickets( int $post_id ): void {
		if ( ! isset( $this->moved_event_ids[ $post_id ] ) ) {
			return;
		}

		unset( $this->moved_event_ids[ $post_id ] );
		$this->update_ticket_dates( $post_id );
	}

	/**
	 * Rewrites the dates of the ruled tickets of the events marked outside a post save, such as by `tribe_update_event()`.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function update_moved_event_tickets(): void {
		foreach ( array_keys( $this->moved_event_ids ) as $post_id ) {
			unset( $this->moved_event_ids[ $post_id ] );
			$this->update_ticket_dates( $post_id );
		}
	}

	/**
	 * Resolves a ruled ticket moved to another event against that event, or removes its rules when it moves to a post
	 * that is not an event, where the dates they resolved stay as the ticket's own.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id           The moved ticket post ID.
	 * @param int $destination_post_id The post the ticket moved to.
	 *
	 * @return void
	 */
	public function update_moved_ticket( int $ticket_id, int $destination_post_id ): void {
		$stored = $this->rule_store->get( $ticket_id );

		if ( ! $stored ) {
			return;
		}

		if ( 'tribe_events' !== get_post_type( $destination_post_id ) ) {
			$this->rule_store->remove( $ticket_id, array_keys( $stored ) );

			return;
		}

		$this->update_ticket( $ticket_id, $destination_post_id );
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
		$rule = Rule::from_stored( $this->rule_store->get( $ticket_id ), Window_Kind::sales() );

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
		$ticket_ids    = $this->rule_store->get_ticket_ids_for_event( $new_post_id );

		// Only when a duplicate got rules: the event's other ruled tickets are already resolved.
		if ( array_intersect( $duplicate_ids, $ticket_ids ) ) {
			$this->update_tickets( $new_post_id, $ticket_ids );
		}
	}

	/**
	 * Rewrites the resolved dates of the given ruled tickets of an event.
	 *
	 * @since TBD
	 *
	 * @param int   $post_id    The event post ID.
	 * @param int[] $ticket_ids The ruled ticket post IDs.
	 *
	 * @return void
	 */
	private function update_tickets( int $post_id, array $ticket_ids ): void {
		// The query returns IDs only, which leaves the meta of each ticket to a query of its own.
		update_meta_cache( 'post', $ticket_ids );

		foreach ( $ticket_ids as $ticket_id ) {
			$this->update_ticket( $ticket_id, $post_id );
		}
	}

	/**
	 * Rewrites the resolved dates of every rule of a ticket and reschedules its sales actions when its sales window
	 * dates changed.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 * @param int $post_id   The event post ID.
	 *
	 * @return void
	 */
	private function update_ticket( int $ticket_id, int $post_id ): void {
		$stored     = $this->rule_store->get( $ticket_id );
		$reschedule = false;

		foreach ( Window_Kind::all() as $kind ) {
			$rule = Rule::from_stored( $stored, $kind );

			if ( $rule && $this->ticket_dates->write( $ticket_id, $post_id, $rule ) && $kind->owns_ticket_sales_dates() ) {
				$reschedule = true;
			}
		}

		// Rescheduling fires the sales actions other plugins listen to, so a ticket whose sales dates stay put keeps its own.
		if ( ! $reschedule ) {
			return;
		}

		/*
		 * A move can push a relative boundary past a specific one. The inverted window is kept, so the ticket is off
		 * sale, but Ticket_Actions skips it before unscheduling, which would leave the old sales actions behind. Every
		 * pending action goes, not only the next one: overlapping saves can leave two.
		 */
		as_unschedule_all_actions( Ticket_Actions::TICKET_START_SALES_HOOK, [ $ticket_id ], Ticket_Actions::AS_TICKET_ACTIONS_GROUP );
		as_unschedule_all_actions( Ticket_Actions::TICKET_END_SALES_HOOK, [ $ticket_id ], Ticket_Actions::AS_TICKET_ACTIONS_GROUP );
		$this->ticket_actions->sync_ticket_dates_actions( $ticket_id );

		if ( $this->ticket_dates->ends_before_it_starts( $ticket_id ) ) {
			$this->announce_inverted_window( $ticket_id, $post_id );
		}
	}

	/**
	 * Logs and announces a ticket whose window an event move left ending before it starts, which puts it off sale.
	 *
	 * The save of the ticket rejects such a window, but nothing rejects an event move.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 * @param int $post_id   The event post ID.
	 *
	 * @return void
	 */
	private function announce_inverted_window( int $ticket_id, int $post_id ): void {
		do_action(
			'tribe_log',
			'warning',
			__CLASS__,
			[
				'message'   => 'An event move left the sales window of a ticket ending before it starts.',
				'ticket_id' => $ticket_id,
				'event_id'  => $post_id,
			]
		);

		/**
		 * Fires when an event move leaves the window of a ticket with relative sale dates ending before it starts.
		 *
		 * The dates are written as the rule resolves them, so the ticket is off sale until they are fixed.
		 *
		 * @since TBD
		 *
		 * @param int $ticket_id The ticket post ID.
		 * @param int $post_id   The event post ID.
		 */
		do_action( 'tec_tickets_relative_sale_dates_window_inverted', $ticket_id, $post_id );
	}

	/**
	 * Returns whether The Events Calendar saves the event occurrences, which it does only with its custom tables active.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the occurrences save resolves the tickets.
	 */
	private function saves_occurrences(): bool {
		return tribe_is_truthy( tribe()->getVar( 'ct1_fully_activated' ) );
	}
}
