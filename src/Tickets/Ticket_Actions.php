<?php
/**
 * The Ticket Actions controller.
 *
 * @since 5.20.0
 * @package TEC\Tickets
 */

namespace TEC\Tickets;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use TEC\Tickets\Commerce\Ticket;
use WP_Post;
use Exception;
use TEC\Common\StellarWP\DB\DB;

/**
 * Class Ticket_Actions.
 *
 * @since 5.20.0
 * @package TEC\Tickets
 */
class Ticket_Actions extends Controller_Contract {
	/**
	 * The action that will be fired when a ticket's start time is almost reached or reached or just reached briefly in the past.
	 *
	 * @since 5.20.0
	 *
	 * @var string
	 */
	public const TICKET_START_SALES_HOOK = 'tec_tickets_ticket_start_sales';

	/**
	 * The action that will be fired when a ticket's end time is almost reached or reached or just reached briefly in the past.
	 *
	 * @since 5.20.0
	 *
	 * @var string
	 */
	public const TICKET_END_SALES_HOOK = 'tec_tickets_ticket_end_sales';

	/**
	 * The action scheduler group for ticket actions.
	 *
	 * @since 5.20.0
	 *
	 * @var string
	 */
	public const AS_TICKET_ACTIONS_GROUP = 'tec_tickets_ticket_actions';

	/**
	 * The keys of interest for syncing ticket dates actions.
	 *
	 * @since 5.20.0
	 *
	 * @var array
	 */
	protected static array $keys_of_interest = [];

	/**
	 * The RSVP IDs to sync.
	 *
	 * @since 5.20.0
	 *
	 * @var array
	 */
	protected static array $rsvp_ids_to_sync = [];

	/**
	 * The pre-update stock.
	 *
	 * @since 5.20.0
	 *
	 * @var array
	 */
	protected static array $pre_update_stock = [];

	/**
	 * Ticket_Actions constructor.
	 *
	 * @param Container $container The DI container.
	 */
	public function __construct( Container $container ) {
		parent::__construct( $container );

		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		self::$keys_of_interest = [
			$tickets_handler->key_start_date,
			$tickets_handler->key_start_time,
			$tickets_handler->key_end_date,
			$tickets_handler->key_end_time,
		];
	}

	/**
	 * Registers the controller by subscribing to front-end hooks and binding implementations.
	 *
	 * @since 5.20.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tec_tickets_ticket_upserted', [ $this, 'sync_ticket_dates_actions' ], 1000 );
		add_action( 'update_post_meta', [ $this, 'pre_update_listener' ], 1000, 3 );
		add_action( 'added_post_meta', [ $this, 'meta_keys_listener' ], 1000, 4 );
		add_action( 'updated_postmeta', [ $this, 'meta_keys_listener' ], 1000, 4 );
		add_action( 'tec_shutdown', [ $this, 'sync_rsvp_dates' ] );
		add_action( self::TICKET_START_SALES_HOOK, [ $this, 'fire_ticket_start_date_action' ], 10, 2 );
		add_action( self::TICKET_END_SALES_HOOK, [ $this, 'fire_ticket_end_date_action' ], 10, 2 );
	}

	/**
	 * Un-registers the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since 5.20.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_ticket_upserted', [ $this, 'sync_ticket_dates_actions' ], 1000 );
		remove_action( 'update_post_meta', [ $this, 'pre_update_listener' ], 1000 );
		remove_action( 'added_post_meta', [ $this, 'meta_keys_listener' ], 1000 );
		remove_action( 'updated_postmeta', [ $this, 'meta_keys_listener' ], 1000 );
		remove_action( 'tec_shutdown', [ $this, 'sync_rsvp_dates' ] );
		remove_action( self::TICKET_START_SALES_HOOK, [ $this, 'fire_ticket_start_date_action' ] );
		remove_action( self::TICKET_END_SALES_HOOK, [ $this, 'fire_ticket_end_date_action' ] );
	}

	/**
	 * Fires the ticket end sales action.
	 *
	 * @since 5.20.0
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
	 * @since 5.20.0
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
	 * Syncs ticket dates actions.
	 *
	 * @since 5.20.0
	 *
	 * @param int $ticket_id The ticket id.
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

		if ( ! $event instanceof WP_Post || 0 === $event->ID ) {
			// Parent event, no longer exists.
			return;
		}

		$ticket_start_timestamp = $ticket->start_date();
		$ticket_end_timestamp   = $ticket->end_date();

		if ( ! ( $ticket_start_timestamp && $ticket_end_timestamp ) ) {
			// No timestamps... we might be too early. Lets wait.
			return;
		}

		$this->sync_action_scheduler_date_actions( $ticket->ID, $ticket_start_timestamp, $ticket_end_timestamp );

		/**
		 * Fires when the dates of a ticket are updated.
		 *
		 * @since 5.20.0
		 *
		 * @param int $ticket_id              The ticket ID.
		 * @param int $ticket_start_timestamp The ticket start timestamp.
		 * @param int $ticket_end_timestamp   The ticket end timestamp.
		 * @param int $parent_id              The parent event ID.
		 */
		do_action( 'tec_tickets_ticket_dates_updated', $ticket->ID, $ticket_start_timestamp, $ticket_end_timestamp, $event->ID );
	}

	/**
	 * Listens for changes to the _stock meta keys.
	 *
	 * The method will store for the request's lifecycle the stock value before the update.
	 *
	 * @since 5.20.0
	 *
	 * @param int    $meta_id  The meta ID.
	 * @param int    $ticket_id The ticket ID.
	 * @param string $meta_key  The meta key.
	 */
	public function pre_update_listener( int $meta_id, int $ticket_id, string $meta_key ): void {
		if ( $meta_key !== Ticket::$stock_meta_key ) {
			// We only care about _stock pre update!
			return;
		}

		$ptype = get_post_type( $ticket_id );

		if ( ! in_array( $ptype, tribe_tickets()->ticket_types(), true ) ) {
			// Not a ticket.
			return;
		}

		// Direct DB query for performance and also to avoid triggering any hooks from get_post_meta.
		self::$pre_update_stock[ $meta_id ] = (int) DB::get_var( DB::prepare( 'SELECT meta_value from %i WHERE meta_id = %d', DB::prefix( 'postmeta' ), $meta_id ) );
	}

	/**
	 * Listens for changes to the meta keys of interest.
	 *
	 * If a change is found and the change is for a ticket, an event is fired.
	 *
	 * @since 5.20.0
	 *
	 * @param int    $meta_id    The meta ID.
	 * @param int    $ticket_id  The ticket ID.
	 * @param string $meta_key   The meta key.
	 * @param mixed  $meta_value The meta value.
	 */
	public function meta_keys_listener( int $meta_id, int $ticket_id, string $meta_key, $meta_value = null ): void {
		if ( ! in_array( $meta_key, array_merge( self::$keys_of_interest, [ Ticket::$stock_meta_key ] ), true ) ) {
			return;
		}

		$ptype = get_post_type( $ticket_id );

		if ( ! in_array( $ptype, tribe_tickets()->ticket_types(), true ) ) {
			// Not a ticket.
			return;
		}

		if ( Ticket::$stock_meta_key === $meta_key ) {
			$this->fire_stock_update_action( $ticket_id, (int) $meta_value, self::$pre_update_stock[ $meta_id ] ?? null );
			return;
		}

		if ( 'tribe_rsvp_tickets' !== $ptype ) {
			return;
		}

		// We avoid checking in_array multiple times and we will rather do array_unique once.
		self::$rsvp_ids_to_sync[] = $ticket_id;
	}

	/**
	 * Syncs the RSVP dates for all RSVPs that had an update to a related
	 * meta during the request.
	 *
	 * @since 5.20.0
	 *
	 * @return void
	 */
	public function sync_rsvp_dates() {
		/**
		 * Filters the RSVP IDs to sync.
		 *
		 * @since 5.20.0
		 *
		 * @param array $rsvp_ids The RSVP IDs to sync.
		 */
		self::$rsvp_ids_to_sync = (array) apply_filters( 'tec_tickets_rsvp_ids_to_sync', array_unique( self::$rsvp_ids_to_sync ) );

		foreach ( self::$rsvp_ids_to_sync as $offset => $rsvp_id ) {
			// Protect ourselves against multiple calls during the same request.
			unset( self::$rsvp_ids_to_sync[ $offset ] );
			$this->sync_ticket_dates_actions( $rsvp_id );
		}
	}

	/**
	 * Listens for changes to the _stock meta key.
	 *
	 * If a change is found and the change is for a Ticket Object, the event is fired.
	 *
	 * @since 5.20.0
	 *
	 * @param int  $ticket_id The ticket ID.
	 * @param int  $new_stock The new stock value.
	 * @param ?int $old_stock The old stock value.
	 */
	protected function fire_stock_update_action( int $ticket_id, int $new_stock, ?int $old_stock = null ): void {
		$ticket = get_post( $ticket_id );

		if ( ! $ticket instanceof WP_Post || 0 === $ticket->ID ) {
			// Deleted ?
			return;
		}

		if ( null === $old_stock ) {
			/**
			 * Fires when the stock of a ticket is added.
			 *
			 * @since 5.20.0
			 *
			 * @param int $ticket_id The ticket id.
			 * @param int $new_stock The new stock value that has just been set.
			 */
			do_action( 'tec_tickets_ticket_stock_added', $ticket->ID, $new_stock );
			return;
		}

		if ( $new_stock === $old_stock ) {
			return;
		}

		/**
		 * Fires when the stock of a ticket changes.
		 *
		 * @since 5.20.0
		 *
		 * @param int $ticket_id The ticket id.
		 * @param int $new_stock The new stock value.
		 * @param int $old_stock The old stock value.
		 */
		do_action( 'tec_tickets_ticket_stock_changed', $ticket->ID, $new_stock, $old_stock );
	}

	/**
	 * Fires the ticket date action.
	 *
	 * @since 5.20.0
	 *
	 * @param int  $ticket_id The ticket ID.
	 * @param bool $is_start  Whether the action is for the start or end date.
	 *
	 * @return void
	 * @throws Exception If the action fails.
	 */
	protected function fire_ticket_date_action( int $ticket_id, bool $is_start = true ): void {
		$ticket = Tickets::load_ticket_object( $ticket_id );

		if ( ! $ticket instanceof Ticket_Object ) {
			// Not a ticket anymore...
			return;
		}

		$event = $ticket->get_event();

		if ( ! $event instanceof WP_Post || 0 === $event->ID ) {
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

		$now = time();

		if ( $now + 30 < $timestamp ) {
			$its_happening = false;
			$method        = "schedule_date_{$prefix}_action";
			// The actual timestamp is not immediate. Lets reschedule closer to the actual event.
			$this->$method( $ticket_id, $now, $timestamp );
		}

		try {
			/**
			 * Fires when a ticket's start/end time is almost reached or reached or just reached briefly in the past.
			 *
			 * In your callbacks you should use the value of $its_happening to reliably determine if this event is going to be fired
			 * again in the future or not. If $its_happening is false, the event will be fired again in the future otherwise it won't.
			 *
			 * @since 5.20.0
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
					'now'           => $now,
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
	 * @since 5.20.0
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

		$now = time();

		if ( $now > $end_timestamp ) {
			// The ticket sale has already ended. Do nothing.
			return;
		}

		$this->schedule_date_start_action( $ticket_id, $now, $start_timestamp );
		$this->schedule_date_end_action( $ticket_id, $now, $end_timestamp );
	}

	/**
	 * Schedules the ticket start action.
	 *
	 * @since 5.20.0
	 *
	 * @param int $ticket_id       The ticket ID.
	 * @param int $now             The current timestamp.
	 * @param int $start_timestamp The ticket start date.
	 *
	 * @return void
	 */
	protected function schedule_date_start_action( int $ticket_id, int $now, int $start_timestamp ): void {
		$minus_30_minutes = ( - 30 * MINUTE_IN_SECONDS );
		$minus_20_minutes = ( - 20 * MINUTE_IN_SECONDS );
		$minus_10_minutes = ( - 10 * MINUTE_IN_SECONDS );

		if ( $now > $start_timestamp ) {
			// The ticket sale has already started. Fire the action immediately.
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
	 * @since 5.20.0
	 *
	 * @param int $ticket_id     The ticket ID.
	 * @param int $now           The current timestamp.
	 * @param int $end_timestamp The ticket end date.
	 *
	 * @return void
	 */
	protected function schedule_date_end_action( int $ticket_id, int $now, int $end_timestamp ): void {
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
