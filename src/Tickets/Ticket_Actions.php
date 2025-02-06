<?php
/**
 * The Ticket Actions controller.
 *
 * @since TBD
 * @package TEC\Tickets
 */

namespace TEC\Tickets;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use TEC\Tickets\Commerce\Ticket;
use WP_Post;
use Exception;

/**
 * Class Ticket_Actions.
 *
 * @since TBD
 * @package TEC\Tickets
 */
class Ticket_Actions extends Controller_Contract {
	/**
	 * The action that will be fired when a ticket's start time is almost reached or reached or just reached briefly in the past.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const TICKET_START_SALES_HOOK = 'tec_tickets_ticket_start_sales';

	/**
	 * The action that will be fired when a ticket's end time is almost reached or reached or just reached briefly in the past.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const TICKET_END_SALES_HOOK = 'tec_tickets_ticket_end_sales';

	/**
	 * The action scheduler group for ticket actions.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const AS_TICKET_ACTIONS_GROUP = 'tec_tickets_ticket_actions';

	/**
	 * Registers the controller by subscribing to front-end hooks and binding implementations.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tec_tickets_ticket_upserted', [ $this, 'sync_ticket_dates_actions' ], PHP_INT_MAX );
		add_action( 'added_post_meta', [ $this, 'sync_rsvp_dates_actions' ], PHP_INT_MAX, 3 );
		add_action( 'updated_postmeta', [ $this, 'sync_rsvp_dates_actions' ], PHP_INT_MAX, 3 );
		add_action( 'added_post_meta', [ $this, 'fire_stock_update_action' ], PHP_INT_MAX, 3 );
		add_action( 'updated_postmeta', [ $this, 'fire_stock_update_action' ], PHP_INT_MAX, 3 );
		add_action( self::TICKET_START_SALES_HOOK, [ $this, 'fire_ticket_start_date_action' ], 10, 2 );
		add_action( self::TICKET_END_SALES_HOOK, [ $this, 'fire_ticket_end_date_action' ], 10, 2 );
	}

	/**
	 * Un-registers the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_ticket_upserted', [ $this, 'sync_ticket_dates_actions' ], PHP_INT_MAX );
		remove_action( 'added_post_meta', [ $this, 'sync_rsvp_dates_actions' ], PHP_INT_MAX );
		remove_action( 'updated_postmeta', [ $this, 'sync_rsvp_dates_actions' ], PHP_INT_MAX );
		remove_action( 'added_post_meta', [ $this, 'fire_stock_update_action' ], PHP_INT_MAX );
		remove_action( 'updated_postmeta', [ $this, 'fire_stock_update_action' ], PHP_INT_MAX );
		remove_action( self::TICKET_START_SALES_HOOK, [ $this, 'fire_ticket_start_date_action' ] );
		remove_action( self::TICKET_END_SALES_HOOK, [ $this, 'fire_ticket_end_date_action' ] );
	}

	/**
	 * Fires the ticket end sales action.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return void
	 * @throws Exception If the action fails.
	 */
	public function fire_ticket_start_date_action( int $ticket_id ): void {
		as_unschedule_action( self::TICKET_START_SALES_HOOK, [ $ticket_id ], self::AS_TICKET_ACTIONS_GROUP );
		$this->fire_ticket_date_action( $ticket_id );
	}

	/**
	 * Fires the ticket end sales action.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return void
	 */
	public function fire_ticket_end_date_action( int $ticket_id ): void {
		as_unschedule_action( self::TICKET_END_SALES_HOOK, [ $ticket_id ], self::AS_TICKET_ACTIONS_GROUP );
		$this->fire_ticket_date_action( $ticket_id, false );
	}

	/**
	 * Listens for changes to the _stock meta key.
	 *
	 * If a change is found and the change is for a Ticket Object, the event is fired.
	 *
	 * @since TBD
	 *
	 * @param int    $meta_id  The meta ID.
	 * @param int    $ticket_id The ticket ID.
	 * @param string $meta_key The meta key.
	 */
	public function fire_stock_update_action( int $meta_id, int $ticket_id, string $meta_key ): void {
		if ( Ticket::$stock_meta_key !== $meta_key ) {
			// Not a stock update.
			return;
		}

		$ticket = Tickets::load_ticket_object( $ticket_id );

		if ( ! $ticket instanceof Ticket_Object ) {
			// Not a ticket object.
			return;
		}

		$event = $ticket->get_event();

		if ( ! $event instanceof WP_Post || 1 > $event->ID ) {
			// Parent event, no longer exists.
			return;
		}

		/**
		 * Fires when the stock of a ticket changes.
		 *
		 * @since TBD
		 *
		 * @param Ticket_Object $ticket The ticket object.
		 * @param WP_Post       $event  The event post object.
		 */
		do_action( 'tec_tickets_ticket_stock_changed', $ticket, $event );
	}

	/**
	 * Syncs ticket dates actions.
	 *
	 * @since TBD
	 *
	 * @param int    $meta_id   The meta ID.
	 * @param int    $ticket_id The ticket id.
	 * @param string $meta_key  The meta key.
	 *
	 * @return void
	 */
	public function sync_ticket_dates_actions( int $ticket_id ): void {
		$ticket = Tickets::load_ticket_object( $ticket_id );

		if ( ! $ticket instanceof Ticket_Object ) {
			// Not a ticket anymore?
			return;
		}

		$event = $ticket->get_event();

		if ( ! $event instanceof WP_Post || 1 > $event->ID ) {
			// Parent event, no longer exists.
			return;
		}

		$ticket_start_timestamp = $ticket->start_date();
		$ticket_end_timestamp   = $ticket->end_date();

		if ( ! $ticket_start_timestamp || ! $ticket_end_timestamp ) {
			// No timestamps... we might be too early. Lets wait.
			return;
		}

		$this->sync_action_scheduler_date_actions( $ticket->ID, $ticket_start_timestamp, $ticket_end_timestamp );

		/**
		 * Fires when the dates of a ticket are updated.
		 *
		 * @since TBD
		 *
		 * @param int $ticket_id              The ticket ID.
		 * @param int $ticket_start_timestamp The ticket start timestamp.
		 * @param int $ticket_end_timestamp   The ticket end timestamp.
		 * @param int $parent_id              The parent event ID.
		 */
		do_action( 'tec_tickets_ticket_dates_updated', $ticket->ID, $ticket_start_timestamp, $ticket_end_timestamp, $event->ID );
	}

	/**
	 * Syncs rsvp dates actions.
	 *
	 * @since TBD
	 *
	 * @param int    $meta_id   The meta ID.
	 * @param int    $ticket_id The ticket id.
	 * @param string $meta_key  The meta key.
	 *
	 * @return void
	 */
	public function sync_rsvp_dates_actions( int $meta_id, int $ticket_id, string $meta_key ): void {
		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$keys_of_interest = [
			$tickets_handler->key_start_date,
			$tickets_handler->key_start_time,
			$tickets_handler->key_end_date,
			$tickets_handler->key_end_time
		];

		if ( ! in_array( $meta_key, $keys_of_interest, true ) ) {
			return;
		}

		if ( 'tribe_rsvp_tickets' !== get_post_type( $ticket_id ) ) {
			return;
		}

		$ticket = Tickets::load_ticket_object( $ticket_id );

		if ( ! $ticket instanceof Ticket_Object ) {
			// Not a ticket anymore...
			return;
		}

		$event = $ticket->get_event();

		if ( ! $event instanceof WP_Post || 1 > $event->ID ) {
			// Parent event, no longer exists.
			return;
		}

		$cache     = tribe_cache();
		$cache_key = __METHOD__ . $ticket_id;

		/**
		 * This is hooking into the save/update process of an RSVP ticket.
		 *
		 * More specifically we are hooking into the save/update of specific meta keys that are related to the rsvp's start and end date.
		 *
		 * During the request, these may be multiple and the most important issue is that they WILL be one by one.
		 *
		 * e.g.
		 *
		 * Save start date first, save end date second and so on.
		 *
		 * We don't want to fire the action multiple times. so if we reached this point we delegate the syncing to happen later.
		 */
		if ( ! empty( $cache[ $cache_key ] ) ) {
			return;
		}

		$cache[ $cache_key ] = true;

		add_action(
			'tec_shutdown',
			function () use ( $ticket_id ) {
				$this->sync_ticket_dates_actions( $ticket_id );
			}
		);
	}

	/**
	 * Fires the ticket date action.
	 *
	 * @since TBD
	 *
	 * @param int  $ticket_id The ticket ID.
	 * @param bool $is_start  Whether the action is for the start or end date.
	 *
	 * @return void
	 */
	protected function fire_ticket_date_action( int $ticket_id, bool $is_start = true ): void {
		$ticket = Tickets::load_ticket_object( $ticket_id );

		if ( ! $ticket instanceof Ticket_Object ) {
			// Not a ticket anymore...
			return;
		}

		$event = $ticket->get_event();

		if ( ! $event instanceof WP_Post || 1 > $event->ID ) {
			// Parent event, no longer exists.
			return;
		}

		$its_happening = true;

		$prefix = $is_start ? 'start' : 'end';

		$method = "{$prefix}_date";
		$timestamp = $ticket->$method();

		if ( ! $timestamp ) {
			// No timestamp...
			return;
		}

		if ( time() + 30 < $timestamp ) {
			$its_happening = false;
			$method        = "schedule_date_{$prefix}_action";
			// The actual timestamp is not immediate. Lets reschedule closer to the actual event.
			$this->$method( $ticket_id, $timestamp );
		}

		try {
			/**
			 * Fires when a ticket's start/end time is almost reached or reached or just reached briefly in the past.
			 *
			 * In your callbacks you should use the value of $its_happening to reliably determine if this event is going to be fired
			 * again in the future or not. If $its_happening is false, the event will be fired again in the future otherwise it won't.
			 *
			 * @since TBD
			 *
			 * @param int     $ticket_id     The ticket ID.
			 * @param bool    $its_happening Whether the event is happening or not.
			 * @param int     $timestamp     The ticket start/end timestamp.
			 * @param WP_Post $event         The event post object.
			 */
			do_action( "tec_tickets_ticket_{$prefix}_date_trigger", $ticket_id, $its_happening, $timestamp, $event );
		} catch ( Exception $e ) {
			do_action(
				'tribe_log',
				'error',
				__( 'Ticket date action failed!', 'event-tickets' ),
				[
					'method'        => __METHOD__,
					'error'         => $e->getMessage(),
					'code'          => $e->getCode(),
					'prefix'        => $prefix,
					'timestamp'     => $timestamp,
					'its_happening' => $its_happening,
					'now'           => time(),
				]
			);

			if ( did_action( 'action_scheduler_before_process_queue' ) ) {
				/**
				 * We are in AS context and AS expects to catch exceptions to mark an action as failed using the exception's message as the reason.
				 */
				throw $e;
			}
		}
	}

	/**
	 * Syncs the action scheduler date actions.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id         The ticket ID.
	 * @param int $start_timestamp   The ticket start date.
	 * @param int $end_timestamp     The ticket end date.
	 *
	 * @return void
	 */
	protected function sync_action_scheduler_date_actions( int $ticket_id, int $start_timestamp, int $end_timestamp ): void {
		if ( $start_timestamp >= $end_timestamp ) {
			// What is going on ? We cant work with that...
			return;
		}

		// Unschedule first pre-existing actions.
		as_unschedule_action( self::TICKET_START_SALES_HOOK, [ $ticket_id ], self::AS_TICKET_ACTIONS_GROUP );
		as_unschedule_action( self::TICKET_END_SALES_HOOK, [ $ticket_id ], self::AS_TICKET_ACTIONS_GROUP );

		if ( time() > $end_timestamp ) {
			// The ticket has already ended. Do nothing.
			return;
		}

		$this->schedule_date_start_action( $ticket_id, $start_timestamp );
		$this->schedule_date_end_action( $ticket_id, $end_timestamp );
	}

	/**
	 * Schedules the ticket start action.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id       The ticket ID.
	 * @param int $start_timestamp The ticket start date.
	 *
	 * @return void
	 */
	protected function schedule_date_start_action( int $ticket_id, int $start_timestamp ): void {
		$now              = time();
		$minus_30_minutes = ( - 30 * MINUTE_IN_SECONDS );
		$minus_20_minutes = ( - 20 * MINUTE_IN_SECONDS );
		$minus_10_minutes = ( - 10 * MINUTE_IN_SECONDS );

		if ( $now > $start_timestamp ) {
			// The ticket has already started. Fire the action immediately.
			do_action( self::TICKET_START_SALES_HOOK, $ticket_id );
			return;
		}

		if ( $now < ( $start_timestamp + $minus_30_minutes ) ) {
			as_schedule_single_action( $start_timestamp + $minus_30_minutes, self::TICKET_START_SALES_HOOK, [ $ticket_id ], self::AS_TICKET_ACTIONS_GROUP );
			return;
		}

		if ( $now < ( $start_timestamp + $minus_20_minutes ) ) {
			as_schedule_single_action( $start_timestamp + $minus_20_minutes, self::TICKET_START_SALES_HOOK, [ $ticket_id ], self::AS_TICKET_ACTIONS_GROUP );
			return;
		}

		if ( $now < ( $start_timestamp + $minus_10_minutes ) ) {
			as_schedule_single_action( $start_timestamp + $minus_10_minutes, self::TICKET_START_SALES_HOOK, [ $ticket_id ], self::AS_TICKET_ACTIONS_GROUP );
			return;
		}

		as_schedule_single_action( $start_timestamp, self::TICKET_START_SALES_HOOK, [ $ticket_id ], self::AS_TICKET_ACTIONS_GROUP );
	}

	/**
	 * Schedule the date end action.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id     The ticket ID.
	 * @param int $end_timestamp The ticket end date.
	 *
	 * @return void
	 */
	protected function schedule_date_end_action( int $ticket_id, int $end_timestamp ): void {
		$now              = time();
		$minus_30_minutes = ( - 30 * MINUTE_IN_SECONDS );
		$minus_20_minutes = ( - 20 * MINUTE_IN_SECONDS );
		$minus_10_minutes = ( - 10 * MINUTE_IN_SECONDS );

		if ( $now < ( $end_timestamp + $minus_30_minutes ) ) {
			as_schedule_single_action( $end_timestamp + $minus_30_minutes, self::TICKET_END_SALES_HOOK, [ $ticket_id ], self::AS_TICKET_ACTIONS_GROUP );
			return;
		}

		if ( $now < ( $end_timestamp + $minus_20_minutes ) ) {
			as_schedule_single_action( $end_timestamp + $minus_20_minutes, self::TICKET_END_SALES_HOOK, [ $ticket_id ], self::AS_TICKET_ACTIONS_GROUP );
			return;
		}

		if ( $now < ( $end_timestamp + $minus_10_minutes ) ) {
			as_schedule_single_action( $end_timestamp + $minus_10_minutes, self::TICKET_END_SALES_HOOK, [ $ticket_id ], self::AS_TICKET_ACTIONS_GROUP );
			return;
		}

		as_schedule_single_action( $end_timestamp, self::TICKET_END_SALES_HOOK, [ $ticket_id ], self::AS_TICKET_ACTIONS_GROUP );
	}
}
