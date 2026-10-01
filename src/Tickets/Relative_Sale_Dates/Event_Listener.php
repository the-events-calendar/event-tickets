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

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Ticket_Actions;

/**
 * Keeps the dates of the tickets that have a rule in step with their event.
 *
 * An event save writes its start, end and timezone one meta at a time, so the event is only marked here and its
 * tickets are resolved once, when the save is done.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Event_Listener extends Controller_Contract {
	/**
	 * The event metas a sales window is resolved from.
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
	 * @param Container      $container      The DI container.
	 * @param Rule_Store     $rule_store     The store of the ticket rules.
	 * @param Ticket_Dates   $ticket_dates   The resolver and writer of the ticket dates.
	 * @param Ticket_Actions $ticket_actions The scheduler of the sales actions.
	 */
	public function __construct( Container $container, Rule_Store $rule_store, Ticket_Dates $ticket_dates, Ticket_Actions $ticket_actions ) {
		parent::__construct( $container );

		$this->rule_store     = $rule_store;
		$this->ticket_dates   = $ticket_dates;
		$this->ticket_actions = $ticket_actions;
	}

	/**
	 * Unregisters the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'added_post_meta', [ $this, 'mark_moved_event' ] );
		remove_action( 'updated_postmeta', [ $this, 'mark_moved_event' ] );
		remove_action( 'wp_after_insert_post', [ $this, 'resolve_saved_event' ] );
		remove_action( 'tec_shutdown', [ $this, 'resolve_moved_events' ] );
		remove_filter( 'tec_tickets_ticket_end_date_follows_event_start', [ $this, 'filter_end_date_follows_event_start' ] );
		remove_action( 'tec_tickets_tickets_duplicated', [ $this, 'copy_rules_to_duplicates' ] );
	}

	/**
	 * Marks an event whose start, end or timezone was written.
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
		if ( in_array( $meta_key, self::EVENT_DATE_META_KEYS, true ) && 'tribe_events' === get_post_type( $post_id ) ) {
			$this->moved_event_ids[ $post_id ] = true;
		}
	}

	/**
	 * Resolves the tickets of an event once its save, meta included, is done.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The saved post ID.
	 *
	 * @return void
	 */
	public function resolve_saved_event( int $post_id ): void {
		if ( isset( $this->moved_event_ids[ $post_id ] ) ) {
			$this->resolve_event( $post_id );
		}
	}

	/**
	 * Resolves the tickets of the events whose dates changed outside a post save.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function resolve_moved_events(): void {
		foreach ( array_keys( $this->moved_event_ids ) as $post_id ) {
			$this->resolve_event( $post_id );
		}
	}

	/**
	 * Returns whether a ticket's sale end date follows its event's start: not when the ticket's rule resolves the end.
	 *
	 * A rule whose end is a specific date leaves the end to the ticket, as a ticket without a rule does, and the classic
	 * editor stores such a rule the first time it saves a ticket made before the feature. The end stays put when the
	 * rule's start counts back from the event end, which could fall after an end moved to the event start.
	 *
	 * @since TBD
	 *
	 * @param bool $follows   Whether the ticket's sale end date follows the event start.
	 * @param int  $ticket_id The ticket post ID.
	 *
	 * @return bool Whether the ticket's sale end date follows the event start.
	 */
	public function filter_end_date_follows_event_start( $follows, int $ticket_id ): bool {
		$rule = $this->get_rule( $ticket_id );

		if ( ! $rule ) {
			return tribe_is_truthy( $follows );
		}

		return tribe_is_truthy( $follows )
			&& Rule::MODE_SPECIFIC === $rule->get_end()->get_mode()
			&& Rule::ANCHOR_END !== $rule->get_start()->get_anchor();
	}

	/**
	 * Copies the rules of the tickets duplicated to another event and resolves the duplicates against that event's dates.
	 *
	 * Cloning a ticket copies its dates only, so without its rules a duplicate would keep the original event's dates.
	 *
	 * @since TBD
	 *
	 * @param array<int,int|false> $duplicated_ticket_ids The duplicated ticket IDs, keyed by the original ticket IDs; `false`
	 *                                                    for a ticket that could not be cloned.
	 * @param int                  $new_post_id           The post the tickets were duplicated to.
	 *
	 * @return void
	 */
	public function copy_rules_to_duplicates( $duplicated_ticket_ids, int $new_post_id ): void {
		if ( ! is_array( $duplicated_ticket_ids ) ) {
			return;
		}

		$copied = false;

		foreach ( $duplicated_ticket_ids as $original_ticket_id => $duplicate_ticket_id ) {
			$stored = $this->rule_store->get( absint( $original_ticket_id ) );

			if ( ! $duplicate_ticket_id || ! $stored ) {
				continue;
			}

			$this->rule_store->save( absint( $duplicate_ticket_id ), $stored );
			$copied = true;
		}

		if ( $copied ) {
			$this->resolve_event( $new_post_id );
		}
	}

	/**
	 * Registers the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'added_post_meta', [ $this, 'mark_moved_event' ], 10, 3 );
		add_action( 'updated_postmeta', [ $this, 'mark_moved_event' ], 10, 3 );
		// The latest hook that sees every event meta written by both the classic and the block editor.
		add_action( 'wp_after_insert_post', [ $this, 'resolve_saved_event' ] );
		add_action( 'tec_shutdown', [ $this, 'resolve_moved_events' ] );
		add_filter( 'tec_tickets_ticket_end_date_follows_event_start', [ $this, 'filter_end_date_follows_event_start' ], 10, 2 );
		add_action( 'tec_tickets_tickets_duplicated', [ $this, 'copy_rules_to_duplicates' ], 10, 2 );
	}

	/**
	 * Resolves every ticket of the event that has a rule and reschedules its sales actions.
	 *
	 * A window the move inverts keeps no sales action: the ticket is off sale until its dates are fixed.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The event post ID.
	 *
	 * @return void
	 */
	private function resolve_event( int $post_id ): void {
		unset( $this->moved_event_ids[ $post_id ] );

		foreach ( $this->get_ruled_ticket_ids( $post_id ) as $ticket_id ) {
			$rule = $this->get_rule( $ticket_id );

			if ( ! $rule ) {
				continue;
			}

			$this->ticket_dates->write( $ticket_id, $post_id, $rule );

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
	 * Gets the Tickets Commerce tickets of an event that have a stored rule.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The event post ID.
	 *
	 * @return int[] The ticket post IDs.
	 */
	private function get_ruled_ticket_ids( int $post_id ): array {
		return array_map(
			'absint',
			get_posts(
				[
					'post_type'      => Ticket::POSTTYPE,
					'post_status'    => 'any',
					'fields'         => 'ids',
					'posts_per_page' => -1,
					'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Tickets are only related to their event through meta.
						[
							'key'   => Ticket::$event_relation_meta_key,
							'value' => $post_id,
						],
						[
							'key'     => Rule_Store::META_KEY,
							'compare' => 'EXISTS',
						],
					],
				]
			)
		);
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
	private function get_rule( int $ticket_id ): ?Rule {
		return Rule::from_stored( $this->rule_store->get( $ticket_id ) );
	}
}
