<?php
/**
 * Regulator for Square syncs.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\SquareRateLimitedException;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Controller as Sync_Controller;
use TEC\Tickets\Commerce\Gateways\Square\Order;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Contracts\Container;
use TEC\Tickets\Commerce\Settings as Commerce_Settings;

/**
 * Regulator for Square syncs.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */
class Regulator extends Controller_Contract {
	/**
	 * The action that initializes the sync of events and tickets with Square.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const HOOK_INIT_SYNC_ACTION = 'tec_tickets_commerce_square_sync';

	/**
	 * The maximum delay for the sync.
	 *
	 * @since 5.24.0
	 *
	 * @var int
	 */
	public const MAX_DELAY = 2 * HOUR_IN_SECONDS;

	/**
	 * The random delay ranges for the sync.
	 *
	 * @since 5.24.0
	 *
	 * @var array
	 */
	protected const RANDOM_DELAY_RANGES = [
		[ 0, MINUTE_IN_SECONDS / 2 ],
		[ MINUTE_IN_SECONDS / 2, MINUTE_IN_SECONDS ],
		[ MINUTE_IN_SECONDS, 5 * MINUTE_IN_SECONDS ],
		[ 5 * MINUTE_IN_SECONDS, 15 * MINUTE_IN_SECONDS ],
		[ 15 * MINUTE_IN_SECONDS, 30 * MINUTE_IN_SECONDS ],
		[ 30 * MINUTE_IN_SECONDS, 45 * MINUTE_IN_SECONDS ],
		[ 45 * MINUTE_IN_SECONDS, 1 * HOUR_IN_SECONDS ],
		[ 1 * HOUR_IN_SECONDS, 75 * MINUTE_IN_SECONDS ],
		[ 75 * MINUTE_IN_SECONDS, 90 * MINUTE_IN_SECONDS ],
		[ 90 * MINUTE_IN_SECONDS, 105 * MINUTE_IN_SECONDS ],
		[ 105 * MINUTE_IN_SECONDS, 2 * HOUR_IN_SECONDS ],
	];

	/**
	 * The rate limited data.
	 *
	 * @since 5.24.0
	 *
	 * @var array
	 */
	protected ?array $rate_limited_data = null;

	/**
	 * The random delays.
	 *
	 * @since 5.24.0
	 *
	 * @var array
	 */
	private static array $random_delays = [];

	/**
	 * The inventory sync.
	 *
	 * @since 5.24.0
	 *
	 * @var Inventory_Sync
	 */
	private Inventory_Sync $inventory_sync;

	/**
	 * The items sync.
	 *
	 * @since 5.24.0
	 *
	 * @var Items_Sync
	 */
	private Items_Sync $items_sync;

	/**
	 * Constructor.
	 *
	 * @since 5.24.0
	 *
	 * @param Container      $container The container.
	 * @param Inventory_Sync $inventory_sync The inventory sync.
	 * @param Items_Sync     $items_sync The items sync.
	 */
	public function __construct( Container $container, Inventory_Sync $inventory_sync, Items_Sync $items_sync ) {
		parent::__construct( $container );
		$this->inventory_sync = $inventory_sync;
		$this->items_sync     = $items_sync;
	}

	/**
	 * Registers the actions.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function do_register(): void {
		add_action( self::HOOK_INIT_SYNC_ACTION, [ $this, 'schedule_sync_for_each_post_type' ] );
		add_action( Items_Sync::HOOK_SYNC_DELETE_EVENT_ACTION, [ $this, 'items_sync_delete_event' ], 10, 2 );
		add_action( Items_Sync::HOOK_SYNC_ACTION, [ $this, 'items_sync_post_type' ] );
		add_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $this, 'items_sync_event' ] );
		add_action( Inventory_Sync::HOOK_SYNC_ACTION, [ $this, 'inventory_sync_post_type' ] );
		add_action( Inventory_Sync::HOOK_SYNC_EVENT_ACTION, [ $this, 'inventory_sync_event' ] );
		add_action( Inventory_Sync::HOOK_CHECK_TICKET_INVENTORY_SYNC, [ $this, 'inventory_sync_ticket' ], 10, 3 );
		add_action( Listeners::HOOK_SYNC_RESET_SYNCED_POST_TYPE, [ $this, 'listeners_reset_post_type_data' ] );
		add_action( Integrity_Controller::HOOK_CHECK_DATA_INTEGRITY, [ $this, 'check_data_integrity' ] );
		add_action( Integrity_Controller::HOOK_DATA_INTEGRITY_DELETE_ITEMS, [ $this, 'integrity_delete_items' ] );
		add_action( Integrity_Controller::HOOK_DATA_INTEGRITY_CHECK_ITEMS, [ $this, 'integrity_check_items' ] );
		add_action( Integrity_Controller::HOOK_DATA_INTEGRITY_SYNC_ITEMS, [ $this, 'integrity_sync_items' ] );
		add_action( Integrity_Controller::HOOK_DATA_INTEGRITY_SYNC_INVENTORY, [ $this, 'integrity_sync_inventory' ] );
		add_action( 'tec_tickets_commerce_square_sync_request_completed', [ $this, 'reset_rate_limited_storage' ] );
		add_action( Order::HOOK_PULL_ORDER_ACTION, [ $this, 'pull_order' ] );
	}

	/**
	 * Un-registers the actions.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( self::HOOK_INIT_SYNC_ACTION, [ $this, 'schedule_sync_for_each_post_type' ] );
		remove_action( Items_Sync::HOOK_SYNC_DELETE_EVENT_ACTION, [ $this, 'items_sync_delete_event' ] );
		remove_action( Items_Sync::HOOK_SYNC_ACTION, [ $this, 'items_sync_post_type' ] );
		remove_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $this, 'items_sync_event' ] );
		remove_action( Inventory_Sync::HOOK_SYNC_ACTION, [ $this, 'inventory_sync_post_type' ] );
		remove_action( Inventory_Sync::HOOK_SYNC_EVENT_ACTION, [ $this, 'inventory_sync_event' ] );
		remove_action( Inventory_Sync::HOOK_CHECK_TICKET_INVENTORY_SYNC, [ $this, 'inventory_sync_ticket' ] );
		remove_action( Listeners::HOOK_SYNC_RESET_SYNCED_POST_TYPE, [ $this, 'listeners_reset_post_type_data' ] );
		remove_action( Integrity_Controller::HOOK_CHECK_DATA_INTEGRITY, [ $this, 'check_data_integrity' ] );
		remove_action( Integrity_Controller::HOOK_DATA_INTEGRITY_DELETE_ITEMS, [ $this, 'integrity_delete_items' ] );
		remove_action( Integrity_Controller::HOOK_DATA_INTEGRITY_CHECK_ITEMS, [ $this, 'integrity_check_items' ] );
		remove_action( Integrity_Controller::HOOK_DATA_INTEGRITY_SYNC_ITEMS, [ $this, 'integrity_sync_items' ] );
		remove_action( Integrity_Controller::HOOK_DATA_INTEGRITY_SYNC_INVENTORY, [ $this, 'integrity_sync_inventory' ] );
		remove_action( 'tec_tickets_commerce_square_sync_request_completed', [ $this, 'reset_rate_limited_storage' ] );
		remove_action( Order::HOOK_PULL_ORDER_ACTION, [ $this, 'pull_order' ] );
	}

	/**
	 * Resets the random delays.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public static function reset_random_delays(): void {
		self::$random_delays = [];
	}

	/**
	 * Resets the rate limited storage.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function reset_rate_limited_storage(): void {
		tribe_remove_option( 'square_rate_limited' );
	}

	/**
	 * Schedules the action.
	 *
	 * @since 5.24.0
	 *
	 * @param string $hook The hook.
	 * @param array  $args The arguments.
	 * @param int    $minimum_delay The minimum delay.
	 * @param bool   $unique Whether the action should be unique.
	 *
	 * @return void
	 */
	public function schedule( string $hook, array $args, int $minimum_delay = 0, bool $unique = true ): void {
		if ( $unique && as_has_scheduled_action( $hook, $args, Sync_Controller::AS_SYNC_ACTION_GROUP ) ) {
			return;
		}

		$minimum_delay = $this->get_rate_limited_minimum_delay( $minimum_delay );

		$delay = $this->get_random_delay( $minimum_delay );

		as_schedule_single_action( time() + $delay, $hook, $args, Sync_Controller::AS_SYNC_ACTION_GROUP );
	}

	/**
	 * Un-schedules an action.
	 *
	 * @since 5.24.0
	 *
	 * @param string $hook The hook.
	 * @param array  $args The arguments.
	 *
	 * @return void
	 */
	public function unschedule( string $hook, array $args = [] ): void {
		as_unschedule_action( $hook, $args, Sync_Controller::AS_SYNC_ACTION_GROUP );
	}

	/**
	 * Sync the tickets.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function schedule_sync_for_each_post_type(): void {
		if ( Sync_Controller::is_sync_completed() ) {
			return;
		}

		$ticket_able_post_types = Sync_Controller::ticket_able_post_types_to_sync();
		if ( empty( $ticket_able_post_types ) ) {
			return;
		}

		foreach ( $ticket_able_post_types as $ticket_able_post_type ) {
			Commerce_Settings::set( Sync_Controller::OPTION_SYNC_ACTIONS_IN_PROGRESS, time(), [ $ticket_able_post_type ] );
			$this->schedule( Items_Sync::HOOK_SYNC_ACTION, [ $ticket_able_post_type ] );
		}
	}

	/**
	 * Pulls the order.
	 *
	 * @since 5.24.0
	 *
	 * @param string $square_order_id The Square order ID.
	 *
	 * @return void
	 */
	public function pull_order( string $square_order_id ): void {
		try {
			tribe( Order::class )->upsert_local_from_square_order( $square_order_id );
			$this->fire_square_request_completed();
		} catch ( SquareRateLimitedException $e ) {
			$this->schedule( Order::HOOK_PULL_ORDER_ACTION, [ $square_order_id ], HOUR_IN_SECONDS / 2, false );
		}
	}
	/**
	 * Checks the data integrity.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function check_data_integrity(): void {
		try {
			tribe( Integrity_Controller::class )->check_data_integrity();
			$this->fire_square_request_completed();
		} catch ( SquareRateLimitedException $e ) {
			$this->schedule( Integrity_Controller::HOOK_CHECK_DATA_INTEGRITY, [], HOUR_IN_SECONDS / 2, false );
		}
	}

	/**
	 * Deletes the items.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function integrity_delete_items(): void {
		try {
			tribe( Integrity_Controller::class )->delete_items();
			$this->fire_square_request_completed();
		} catch ( SquareRateLimitedException $e ) {
			$this->schedule( Integrity_Controller::HOOK_DATA_INTEGRITY_DELETE_ITEMS, [], HOUR_IN_SECONDS, false );
		}
	}

	/**
	 * Checks the items.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function integrity_check_items(): void {
		try {
			tribe( Integrity_Controller::class )->check_items();
			$this->fire_square_request_completed();
		} catch ( SquareRateLimitedException $e ) {
			$this->schedule( Integrity_Controller::HOOK_DATA_INTEGRITY_CHECK_ITEMS, [], 5 * MINUTE_IN_SECONDS, false );
		}
	}

	/**
	 * Syncs the items.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function integrity_sync_items(): void {
		try {
			tribe( Integrity_Controller::class )->sync_items();
			$this->fire_square_request_completed();
		} catch ( SquareRateLimitedException $e ) {
			$this->schedule( Integrity_Controller::HOOK_DATA_INTEGRITY_SYNC_ITEMS, [], 2 * MINUTE_IN_SECONDS, false );
		}
	}

	/**
	 * Syncs the inventory.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function integrity_sync_inventory(): void {
		try {
			tribe( Integrity_Controller::class )->sync_inventory();
			$this->fire_square_request_completed();
		} catch ( SquareRateLimitedException $e ) {
			$this->schedule( Integrity_Controller::HOOK_DATA_INTEGRITY_SYNC_INVENTORY, [], 2 * MINUTE_IN_SECONDS, false );
		}
	}

	/**
	 * Resets the post type data.
	 *
	 * @since 5.24.0
	 *
	 * @param string $post_type The post type.
	 *
	 * @return void
	 */
	public function listeners_reset_post_type_data( string $post_type = '' ): void {
		try {
			tribe( Listeners::class )->reset_post_type_data( $post_type );
			$this->fire_square_request_completed();
		} catch ( SquareRateLimitedException $e ) {
			$this->schedule( Listeners::HOOK_SYNC_RESET_SYNCED_POST_TYPE, [ $post_type ], 15 * MINUTE_IN_SECONDS, false );
		}
	}

	/**
	 * Syncs the delete event.
	 *
	 * @since 5.24.0
	 *
	 * @param int    $object_id        The object ID.
	 * @param string $remote_object_id The remote object ID.
	 *
	 * @return void
	 */
	public function items_sync_delete_event( int $object_id, string $remote_object_id ): void {
		try {
			$this->items_sync->sync_delete_event( $object_id, $remote_object_id );
			$this->fire_square_request_completed();
		} catch ( SquareRateLimitedException $e ) {
			$this->schedule( Items_Sync::HOOK_SYNC_DELETE_EVENT_ACTION, [ $object_id, $remote_object_id ], MINUTE_IN_SECONDS / 3, false );
		}
	}

	/**
	 * Syncs the post type.
	 *
	 * @since 5.24.0
	 *
	 * @param string $post_type The post type.
	 *
	 * @return void
	 */
	public function items_sync_post_type( string $post_type ): void {
		try {
			$this->items_sync->sync_post_type( $post_type );
			$this->fire_square_request_completed();
		} catch ( SquareRateLimitedException $e ) {
			$this->schedule( Items_Sync::HOOK_SYNC_ACTION, [ $post_type ], MINUTE_IN_SECONDS / 6, false );
		}
	}

	/**
	 * Syncs the event.
	 *
	 * @since 5.24.0
	 *
	 * @param int  $event_id The event ID.
	 * @param bool $execute The execute.
	 *
	 * @return void
	 */
	public function items_sync_event( int $event_id, bool $execute = true ): void {
		try {
			$this->items_sync->sync_event( $event_id, $execute );
			$this->fire_square_request_completed();
		} catch ( SquareRateLimitedException $e ) {
			$this->schedule( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $event_id, $execute ], MINUTE_IN_SECONDS / 3, false );
		}
	}

	/**
	 * Syncs the post type.
	 *
	 * @since 5.24.0
	 *
	 * @param string $post_type The post type.
	 *
	 * @return void
	 */
	public function inventory_sync_post_type( string $post_type ): void {
		try {
			$this->inventory_sync->sync_post_type( $post_type );
			$this->fire_square_request_completed();
		} catch ( SquareRateLimitedException $e ) {
			$this->schedule( Inventory_Sync::HOOK_SYNC_ACTION, [ $post_type ], MINUTE_IN_SECONDS / 6, false );
		}
	}

	/**
	 * Syncs the event.
	 *
	 * @since 5.24.0
	 *
	 * @param int   $event_id The event ID.
	 * @param bool  $execute  The execute.
	 * @param array $tickets  The tickets.
	 *
	 * @return void
	 */
	public function inventory_sync_event( int $event_id, bool $execute = true, array $tickets = [] ): void {
		try {
			$this->inventory_sync->sync_event( $event_id, $execute, $tickets );
			$this->fire_square_request_completed();
		} catch ( SquareRateLimitedException $e ) {
			$this->schedule( Inventory_Sync::HOOK_SYNC_EVENT_ACTION, [ $event_id, $execute, $tickets ], MINUTE_IN_SECONDS / 3, false );
		}
	}

	/**
	 * Syncs the ticket.
	 *
	 * @since 5.24.0
	 *
	 * @param int    $ticket_id The ticket ID.
	 * @param int    $quantity  The quantity of tickets.
	 * @param string $state     The state of the inventory.
	 *
	 * @return void
	 */
	public function inventory_sync_ticket( int $ticket_id, int $quantity, string $state ): void {
		try {
			$this->inventory_sync->sync_ticket( $ticket_id, $quantity, $state );
			$this->fire_square_request_completed();
		} catch ( SquareRateLimitedException $e ) {
			$this->schedule( Inventory_Sync::HOOK_CHECK_TICKET_INVENTORY_SYNC, [ $ticket_id, $quantity, $state ], 2 * MINUTE_IN_SECONDS, false );
		}
	}

	/**
	 * Fires the square request completed.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	protected function fire_square_request_completed(): void {
		/**
		 * Fires when a Square request is completed.
		 *
		 * @since 5.24.0
		 */
		do_action( 'tec_tickets_commerce_square_sync_request_completed' );
	}

	/**
	 * Gets the rate limited minimum delay.
	 *
	 * @since 5.24.0
	 *
	 * @param int $minimum_delay The minimum delay.
	 *
	 * @return int
	 */
	protected function get_rate_limited_minimum_delay( int $minimum_delay ): int {
		$this->set_rate_limited_data();
		if ( ! $this->rate_limited_data ) {
			return $minimum_delay;
		}

		if ( $minimum_delay > self::MAX_DELAY ) {
			return $minimum_delay;
		}

		if ( count( $this->rate_limited_data ) > 1 ) {
			$size = count( $this->rate_limited_data );
			return min(
				max(
					3 * $size * abs( $this->rate_limited_data[ $size - 1 ] - $this->rate_limited_data[ $size - 2 ] ) / 2,
					MINUTE_IN_SECONDS / 2
				) + $minimum_delay,
				self::MAX_DELAY
			);
		}

		return ( MINUTE_IN_SECONDS / 2 ) + $minimum_delay;
	}

	/**
	 * Gets the random delay.
	 *
	 * @since 5.24.0
	 *
	 * @param int $minimum_delay The minimum delay.
	 *
	 * @return int
	 */
	protected function get_random_delay( int $minimum_delay ): int {
		$this->set_rate_limited_data();
		if ( ! $this->rate_limited_data ) {
			return $minimum_delay;
		}

		if ( $minimum_delay >= self::MAX_DELAY ) {
			return $minimum_delay;
		}

		$desired_offset = 10;
		foreach ( self::RANDOM_DELAY_RANGES as $offset => $delay ) {
			if ( $minimum_delay >= $delay[1] ) {
				continue;
			}
			$desired_offset = $offset;
			break;
		}

		$delay = $minimum_delay + min( wp_rand( self::RANDOM_DELAY_RANGES[ $desired_offset ][0], self::RANDOM_DELAY_RANGES[ $desired_offset ][1] ), self::MAX_DELAY );

		$last_item = end( self::$random_delays );
		if ( $last_item && $last_item >= $delay ) {
			// Ensure actions are executed in the order they were created.
			$delay += wp_rand( 10, 100 );
		}

		self::$random_delays[] = $delay;

		return $delay;
	}

	/**
	 * Sets the rate limited data.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	protected function set_rate_limited_data(): void {
		if ( null !== $this->rate_limited_data ) {
			return;
		}

		$this->rate_limited_data = (array) tribe_get_option( 'square_rate_limited', [] );
	}
}
