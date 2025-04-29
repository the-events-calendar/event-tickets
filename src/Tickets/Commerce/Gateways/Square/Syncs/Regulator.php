<?php
/**
 * Regulator for Square syncs.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\SquareRateLimitedException;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Controller as Sync_Controller;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Contracts\Container;

/**
 * Regulator for Square syncs.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */
class Regulator extends Controller_Contract {
	/**
	 * The action that initializes the sync of events and tickets with Square.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const HOOK_INIT_SYNC_ACTION = 'tec_tickets_commerce_square_sync';

	/**
	 * The maximum delay for the sync.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected const MAX_DELAY = 2 * HOUR_IN_SECONDS;

	/**
	 * The random delay ranges for the sync.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @var array
	 */
	protected ?array $rate_limited_data = null;

	/**
	 * The random delays.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	private static array $random_delays = [];

	/**
	 * The inventory sync.
	 *
	 * @since TBD
	 *
	 * @var Inventory_Sync
	 */
	private Inventory_Sync $inventory_sync;

	/**
	 * The items sync.
	 *
	 * @since TBD
	 *
	 * @var Items_Sync
	 */
	private Items_Sync $items_sync;

	/**
	 * Constructor.
	 *
	 * @since TBD
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
	 * @since TBD
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
		add_action( Listeners::HOOK_SYNC_RESET_SYNCED_POST_TYPE, [ $this, 'listeners_reset_post_type_data' ] );
		add_action( 'tec_tickets_commerce_square_sync_request_completed', [ $this, 'reset_rate_limited_storage' ] );
	}

	/**
	 * Un-registers the actions.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( self::HOOK_INIT_SYNC_ACTION, [ $this, 'schedule_sync_for_each_post_type' ] );
		remove_action( Items_Sync::HOOK_SYNC_DELETE_EVENT_ACTION, [ $this, 'items_sync_delete_event' ], 10, 2 );
		remove_action( Items_Sync::HOOK_SYNC_ACTION, [ $this, 'items_sync_post_type' ] );
		remove_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $this, 'items_sync_event' ] );
		remove_action( Inventory_Sync::HOOK_SYNC_ACTION, [ $this, 'inventory_sync_post_type' ] );
		remove_action( Inventory_Sync::HOOK_SYNC_EVENT_ACTION, [ $this, 'inventory_sync_event' ] );
		remove_action( Listeners::HOOK_SYNC_RESET_SYNCED_POST_TYPE, [ $this, 'listeners_reset_post_type_data' ] );
		remove_action( 'tec_tickets_commerce_square_sync_request_completed', [ $this, 'reset_rate_limited_storage' ] );
	}

	/**
	 * Resets the rate limited storage.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function reset_rate_limited_storage(): void {
		tribe_remove_option( 'square_rate_limited' );
	}

	/**
	 * Schedules the action.
	 *
	 * @since TBD
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
	 * Sync the tickets.
	 *
	 * @since TBD
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
			tribe_update_option( sprintf( Sync_Controller::OPTION_SYNC_ACTIONS_IN_PROGRESS, $ticket_able_post_type ), time() );
			$this->schedule( Items_Sync::HOOK_SYNC_ACTION, [ $ticket_able_post_type ] );
		}
	}

	/**
	 * Resets the post type data.
	 *
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
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
	 * Fires the square request completed.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function fire_square_request_completed(): void {
		/**
		 * Fires when a Square request is completed.
		 *
		 * @since TBD
		 */
		do_action( 'tec_tickets_commerce_square_sync_request_completed' );
	}

	/**
	 * Gets the rate limited minimum delay.
	 *
	 * @since TBD
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

		if ( count( $this->rate_limited_data ) > 1 ) {
			$size = count( $this->rate_limited_data );
			return ( $this->rate_limited_data[ $size - 1 ] - $this->rate_limited_data[ $size - 2 ] ) + $minimum_delay;
		}

		return ( MINUTE_IN_SECONDS / 2 ) + $minimum_delay;
	}

	/**
	 * Gets the random delay.
	 *
	 * @since TBD
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

		$desired_offset = 10;
		foreach ( self::RANDOM_DELAY_RANGES as $offset => $delay ) {
			if ( $minimum_delay >= $delay[0] ) {
				$desired_offset = $offset;
				break;
			}
		}

		$delay = max( wp_rand( self::RANDOM_DELAY_RANGES[ $desired_offset ][0], self::RANDOM_DELAY_RANGES[ $desired_offset ][1] ), self::MAX_DELAY );

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
	 * @since TBD
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
