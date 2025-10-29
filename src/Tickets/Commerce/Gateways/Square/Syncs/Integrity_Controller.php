<?php
/**
 * Ensures the data integrity of the Square sync.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Contracts\Container;
use TEC\Common\StellarWP\Schema\Register;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Item;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Event_Item;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Inventory_Change;
use TEC\Tickets\Commerce\Gateways\Square\Requests;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Ticket_Item;
use TEC\Tickets\Commerce\Ticket as Ticket_Data;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\NoChangeNeededException;
use TEC\Tickets\Commerce\Settings as Commerce_Settings;
use TEC\Tickets\Commerce\Meta as Commerce_Meta;

/**
 * Integrity_Controller class.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */
class Integrity_Controller extends Controller_Contract {
	/**
	 * Hook to check the data integrity.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const HOOK_CHECK_DATA_INTEGRITY = 'tec_tickets_commerce_square_check_data_integrity';

	/**
	 * Hook to delete the items.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const HOOK_DATA_INTEGRITY_DELETE_ITEMS = 'tec_tickets_commerce_square_data_integrity_delete_items';

	/**
	 * Hook to check the items.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const HOOK_DATA_INTEGRITY_CHECK_ITEMS = 'tec_tickets_commerce_square_data_integrity_check_items';

	/**
	 * Hook to sync the items.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const HOOK_DATA_INTEGRITY_SYNC_ITEMS = 'tec_tickets_commerce_square_data_integrity_sync_items';

	/**
	 * Hook to sync the inventory.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const HOOK_DATA_INTEGRITY_SYNC_INVENTORY = 'tec_tickets_commerce_square_data_integrity_sync_inventory';

	/**
	 * Option to store the items to delete.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const OPTION_INTEGRITY_CHECK_DELETED_ITEMS = 'tickets_commerce_square_integrity_items_to_delete_%s';

	/**
	 * Option to store the items to check.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const OPTION_INTEGRITY_CHECK_ITEMS = 'tickets_commerce_square_integrity_items_to_check_%s';

	/**
	 * Option to store the items to sync.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const OPTION_INTEGRITY_SYNC_ITEMS = 'tickets_commerce_square_integrity_items_to_sync_%s';

	/**
	 * Option to store the item's inventory to sync.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const OPTION_INTEGRITY_SYNC_INVENTORY = 'tickets_commerce_square_integrity_items_inventory_to_sync_%s';

	/**
	 * Items to store.
	 *
	 * @since 5.24.0
	 *
	 * @var array
	 */
	protected static array $items = [];

	/**
	 * Whether the mode is production.
	 *
	 * @since 5.24.0
	 *
	 * @var bool
	 */
	protected static bool $is_prod_mode;

	/**
	 * Regulator.
	 *
	 * @since 5.24.0
	 *
	 * @var Regulator
	 */
	private Regulator $regulator;

	/**
	 * Items sync.
	 *
	 * @since 5.24.0
	 *
	 * @var Items_Sync
	 */
	private Items_Sync $items_sync;

	/**
	 * Inventory sync.
	 *
	 * @since 5.24.0
	 *
	 * @var Inventory_Sync
	 */
	private Inventory_Sync $inventory_sync;

	/**
	 * Ticket data.
	 *
	 * @since 5.24.0
	 *
	 * @var Ticket_Data
	 */
	private Ticket_Data $ticket_data;

	/**
	 * Constructor.
	 *
	 * @since 5.24.0
	 *
	 * @param Container      $container      Container.
	 * @param Regulator      $regulator      Regulator.
	 * @param Items_Sync     $items_sync     Items sync.
	 * @param Inventory_Sync $inventory_sync Inventory sync.
	 * @param Ticket_Data    $ticket_data    Ticket data.
	 */
	public function __construct( Container $container, Regulator $regulator, Items_Sync $items_sync, Inventory_Sync $inventory_sync, Ticket_Data $ticket_data ) {
		parent::__construct( $container );
		$this->regulator      = $regulator;
		$this->items_sync     = $items_sync;
		$this->inventory_sync = $inventory_sync;
		$this->ticket_data    = $ticket_data;
		self::$is_prod_mode   = ! $this->container->get( Merchant::class )->is_test_mode();
	}

	/**
	 * Register the controller's hooks.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function do_register(): void {
		add_action( 'tec_tickets_commerce_square_object_synced', [ $this, 'enqueue_objects_for_storage' ], 10, 4 );
		add_action( 'tec_shutdown', [ $this, 'store_and_flush_items' ] );
		add_action( 'init', [ $this, 'schedule_data_integrity_check' ] );
		Register::table( Integrity_Table::class );
	}

	/**
	 * Unregister the controller's hooks.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_commerce_square_object_synced', [ $this, 'enqueue_objects_for_storage' ] );
		remove_action( 'tec_shutdown', [ $this, 'store_and_flush_items' ] );
		remove_action( 'init', [ $this, 'schedule_data_integrity_check' ] );
	}

	/**
	 * Schedule the batch sync.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function schedule_data_integrity_check(): void {
		$this->regulator->schedule( self::HOOK_CHECK_DATA_INTEGRITY, [], 6 * HOUR_IN_SECONDS );
	}

	/**
	 * Store and flush the items.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function store_and_flush_items(): void {
		if ( empty( self::$items ) ) {
			return;
		}

		Integrity_Table::insert_many( self::$items );
		self::$items = [];
	}

	/**
	 * Enqueue the objects for storage.
	 *
	 * @since 5.24.0
	 *
	 * @param string $square_object_id The Square object ID.
	 * @param int    $wp_object_id     The WordPress object ID.
	 * @param array  $square_object    The Square object.
	 * @param Item   $item             The item object.
	 *
	 * @return void
	 */
	public function enqueue_objects_for_storage( string $square_object_id, int $wp_object_id, array $square_object, Item $item ): void {
		$wp_controlled_fields = $item->get_wp_controlled_fields( $square_object );

		self::$items[] = [
			'square_object_id'   => $square_object_id,
			'wp_object_id'       => $wp_object_id,
			'square_object_hash' => md5( wp_json_encode( $wp_controlled_fields ) ),
			'mode'               => self::$is_prod_mode,
			'last_checked'       => current_time( 'mysql', true ),
		];

		if ( count( self::$items ) > 99 ) {
			$this->store_and_flush_items();
		}
	}

	/**
	 * Check the data integrity of the items in the table.
	 *
	 * This could be an intense process, thats why we do it just 4 times a day.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function check_data_integrity(): void {
		$half_a_minute_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-30 seconds' ) );

		$to_be_deleted   = [];
		$to_local_delete = [];
		$to_be_checked   = [];

		foreach ( Integrity_Table::get_all( 100, "WHERE last_checked < '{$half_a_minute_ago}' AND mode = " . (int) self::$is_prod_mode ) as $item ) {
			$post_object = get_post( $item['wp_object_id'] );

			if ( ! $post_object ) {
				$to_be_deleted[] = $item['id'];
				continue;
			}

			if ( Item::get_remote_object_id( $item['wp_object_id'] ) !== $item['square_object_id'] ) {
				$to_be_deleted[] = $item['id'];
				continue;
			}

			if ( isset( $to_be_checked[ $item['square_object_id'] ] ) ) {
				$previous_item     = $to_be_checked[ $item['square_object_id'] ];
				$to_local_delete[] = $previous_item['last_checked'] > $item['last_checked'] ? $item : $previous_item;

				$to_be_checked[ $item['square_object_id'] ] = $previous_item['last_checked'] > $item['last_checked'] ? $previous_item : $item;
				continue;
			}

			$to_be_checked[ $item['square_object_id'] ] = $item;
		}

		if ( ! empty( $to_local_delete ) ) {
			Integrity_Table::delete_many( wp_list_pluck( $to_local_delete, 'id' ) );
		}


		unset( $to_local_delete );

		if ( ! empty( $to_be_deleted ) ) {
			Commerce_Settings::set( self::OPTION_INTEGRITY_CHECK_DELETED_ITEMS, $to_be_deleted );
			$this->regulator->schedule( self::HOOK_DATA_INTEGRITY_DELETE_ITEMS, [], HOUR_IN_SECONDS );
		}

		unset( $to_be_deleted );

		if ( empty( $to_be_checked ) ) {
			return;
		}

		if ( 1000 < count( $to_be_checked ) ) {
			Commerce_Settings::set( self::OPTION_INTEGRITY_CHECK_ITEMS, $to_be_checked );
			$this->regulator->schedule( self::HOOK_DATA_INTEGRITY_CHECK_ITEMS, [] );
			return;
		}

		// If they are less than 1000, lets save ourselves some time and figure out which ones need to be actually synced.
		$this->check_items( $to_be_checked );
	}

	/**
	 * Delete the items.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function delete_items(): void {
		$ids = Commerce_Settings::get( self::OPTION_INTEGRITY_CHECK_DELETED_ITEMS );

		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return;
		}

		$ids = array_filter( array_map( 'intval', $ids ) );

		if ( empty( $ids ) ) {
			return;
		}

		$deleted = [];

		foreach ( Integrity_Table::get_all( 100, 'WHERE id IN (' . implode( ',', $ids ) . ')' ) as $item ) {
			$deleted[ $item['square_object_id'] ] = $item['id'];

			if ( count( $deleted ) > 999 ) {
				$this->regulator->schedule( self::HOOK_DATA_INTEGRITY_DELETE_ITEMS, [], 5 * MINUTE_IN_SECONDS );
				break;
			}
		}

		$payload = [
			'object_ids' => array_keys( $deleted ),
		];

		$response = Requests::post(
			'catalog/batch-delete',
			[],
			[ 'body' => $payload ]
		);

		if ( ! empty( $response['errors'] ) ) {
			do_action(
				'tribe_log',
				'error',
				'Error during deleting objects for data integrity check',
				$response['errors']
			);
			return;
		}

		Integrity_Table::delete_many( array_keys( $deleted ), 'square_object_id' );
		$diff = array_diff( $ids, array_values( $deleted ) );

		if ( empty( $diff ) ) {
			Commerce_Settings::delete( self::OPTION_INTEGRITY_CHECK_DELETED_ITEMS );
			return;
		}

		Commerce_Settings::set( self::OPTION_INTEGRITY_CHECK_DELETED_ITEMS, $diff );
		$this->regulator->schedule( self::HOOK_DATA_INTEGRITY_DELETE_ITEMS, [], 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Check the items.
	 *
	 * @since 5.24.0
	 *
	 * @param array $to_be_checked The items to check.
	 *
	 * @return void
	 */
	public function check_items( array $to_be_checked = [] ): void {
		if ( empty( $to_be_checked ) ) {
			$to_be_checked = Commerce_Settings::get( self::OPTION_INTEGRITY_CHECK_ITEMS );
		}

		if ( empty( $to_be_checked ) ) {
			return;
		}

		// Keep the first 1000 items.
		$first_thousand = array_slice( $to_be_checked, 0, 1000 );
		$remaining      = array_slice( $to_be_checked, 1000 );
		$to_be_checked  = $first_thousand;

		$payload = [
			'object_ids'              => array_keys( $to_be_checked ),
			'include_related_objects' => false,
			'include_deleted_objects' => false,
		];

		$response = Requests::post(
			'catalog/batch-retrieve',
			[],
			[ 'body' => $payload ]
		);

		if ( ! empty( $response['errors'] ) ) {
			do_action(
				'tribe_log',
				'error',
				'Error during retrieving objects for data integrity check',
				$response['errors']
			);
			return;
		}

		Commerce_Settings::delete( self::OPTION_INTEGRITY_CHECK_ITEMS );

		if ( empty( $response['objects'] ) ) {
			// Weird.... Lets throw error.
			do_action(
				'tribe_log',
				'error',
				'No objects found for data integrity check',
				$response
			);
			return;
		}

		$tickets_batch = [];
		foreach ( $to_be_checked as $object ) {
			$is_ticket = in_array( get_post_type( $object->wp_object_id ), tribe_tickets()->ticket_types(), true );
			if ( ! $is_ticket ) {
				continue;
			}

			$tickets_batch[ $object->wp_object_id ] = $this->ticket_data->load_ticket_object( $object->wp_object_id );
		}

		$location_id = null;

		if ( $tickets_batch ) {
			// since we are here, lets set up our object's inventory cache so we can check both together.
			$location_id = tribe( Remote_Objects::class )->cache_remote_object_state( [ $tickets_batch ] );
		}

		$actually_in_need_of_sync       = [];
		$items_availability_out_of_sync = [];

		foreach ( $response['objects'] as $object ) {
			if ( ! isset( $to_be_checked[ $object['id'] ] ) ) {
				// What is going on here ??
				continue;
			}

			$is_ticket = in_array( get_post_type( $to_be_checked[ $object['id'] ]->wp_object_id ), tribe_tickets()->ticket_types(), true );

			$item_object = $is_ticket ?
			new Ticket_Item( $this->ticket_data->load_ticket_object( $to_be_checked[ $object['id'] ]->wp_object_id ) ) :
			new Event_Item( $to_be_checked[ $object['id'] ]->wp_object_id );

			$wp_controlled_fields = $item_object->get_wp_controlled_fields( $object );

			// We always update the object's version so that we can modify it.
			Commerce_Meta::set( $to_be_checked[ $object['id'] ]->wp_object_id, Item::SQUARE_VERSION_META, $object['version'] );

			$is_remote_up_to_date_with_latest_snapshot = $to_be_checked[ $object['id'] ]->square_object_hash === md5( wp_json_encode( $wp_controlled_fields ) );
			$is_local_up_to_date_with_remote           = $is_ticket || ! $item_object->needs_sync();

			if ( ! ( $is_remote_up_to_date_with_latest_snapshot && $is_local_up_to_date_with_remote ) ) {
				// As to allow it to be synced again.
				Commerce_Meta::delete( $to_be_checked[ $object['id'] ]->wp_object_id, Event_Item::SQUARE_LATEST_OBJECT_SNAPSHOT );

				$actually_in_need_of_sync[] = $to_be_checked[ $object['id'] ]->id;
			}

			if ( ! $is_ticket ) {
				continue;
			}

			if ( $location_id && ! is_string( $location_id ) ) {
				continue;
			}

			try {
				new Inventory_Change( 'ADJUSTMENT', $item_object, [ 'location_id' => $location_id ] );
			} catch ( NoChangeNeededException $e ) {
				continue;
			}

			$items_availability_out_of_sync[] = $to_be_checked[ $object['id'] ]->id;
		}

		if ( ! empty( $remaining ) ) {
			Commerce_Settings::set( self::OPTION_INTEGRITY_CHECK_ITEMS, $remaining );
			$this->regulator->schedule( self::HOOK_DATA_INTEGRITY_CHECK_ITEMS, [], MINUTE_IN_SECONDS / 2 );
		}

		if ( empty( $remaining ) ) {
			$this->regulator->schedule( self::HOOK_DATA_INTEGRITY_SYNC_ITEMS, [], MINUTE_IN_SECONDS / 6 );
			$this->regulator->schedule( self::HOOK_DATA_INTEGRITY_SYNC_INVENTORY, [], MINUTE_IN_SECONDS / 6 );
		}

		if ( ! empty( $items_availability_out_of_sync ) ) {
			$previous = (array) Commerce_Settings::get( self::OPTION_INTEGRITY_SYNC_INVENTORY );
			Commerce_Settings::set( self::OPTION_INTEGRITY_SYNC_INVENTORY, array_merge( $previous, $items_availability_out_of_sync ) );
		}

		if ( empty( $actually_in_need_of_sync ) ) {
			return;
		}

		$previous = (array) Commerce_Settings::get( self::OPTION_INTEGRITY_SYNC_ITEMS );
		Commerce_Settings::set( self::OPTION_INTEGRITY_SYNC_ITEMS, array_merge( $previous, $actually_in_need_of_sync ) );
	}

	/**
	 * Sync the items.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function sync_items(): void {
		$ids = Commerce_Settings::get( self::OPTION_INTEGRITY_SYNC_ITEMS );

		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return;
		}

		$ids = array_filter( array_map( 'intval', $ids ) );

		if ( empty( $ids ) ) {
			Commerce_Settings::delete( self::OPTION_INTEGRITY_SYNC_ITEMS );
			return;
		}

		$objects_to_sync = [];
		$skipped_tickets = [];
		$batch           = [];

		$ticket_types = array_flip( tribe_tickets()->ticket_types() );

		foreach ( Integrity_Table::get_all( 100, 'WHERE id IN (' . implode( ',', $ids ) . ')' ) as $item ) {
			$is_ticket = isset( $ticket_types[ get_post_type( $item['wp_object_id'] ) ] );

			if ( $is_ticket ) {
				$ticket_object = $this->ticket_data->load_ticket_object( $item['wp_object_id'] );
				if ( ! $ticket_object ) {
					$skipped_tickets[] = $item['id'];
					continue;
				}

				$event_id = $ticket_object->get_event_id();

				if ( isset( $batch[ $event_id ] ) ) {
					continue;
				}

				// Set the parent event as a whole as in need of sync.
				$item['wp_object_id'] = $ticket_object->get_event_id();
			}

			$tickets = $this->items_sync->sync_event( $item['wp_object_id'], false );

			if ( ! $tickets ) {
				continue;
			}

			$batch[ $item['wp_object_id'] ] = $tickets;

			$objects_to_sync[] = $item['id'];
			if ( count( $batch ) > 999 ) {
				$this->regulator->schedule( self::HOOK_DATA_INTEGRITY_SYNC_ITEMS, [], MINUTE_IN_SECONDS / 6 );
				break;
			}
		}

		if ( empty( $batch ) ) {
			Commerce_Settings::delete( self::OPTION_INTEGRITY_SYNC_ITEMS );
			return;
		}

		$this->items_sync->process_batch( $batch );
		$this->inventory_sync->process_batch( $batch );

		$remaining = array_diff( $ids, $objects_to_sync, $skipped_tickets );

		if ( empty( $remaining ) ) {
			Commerce_Settings::delete( self::OPTION_INTEGRITY_SYNC_ITEMS );
			return;
		}

		Commerce_Settings::set( self::OPTION_INTEGRITY_SYNC_ITEMS, $remaining );
		$this->regulator->schedule( self::HOOK_DATA_INTEGRITY_SYNC_ITEMS, [], MINUTE_IN_SECONDS / 6 );
	}

	/**
	 * Sync the inventory.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function sync_inventory(): void {
		$ids = Commerce_Settings::get( self::OPTION_INTEGRITY_SYNC_INVENTORY );

		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return;
		}

		$ids = array_filter( array_map( 'intval', $ids ) );

		if ( empty( $ids ) ) {
			Commerce_Settings::delete( self::OPTION_INTEGRITY_SYNC_INVENTORY );
			return;
		}

		$objects_to_sync = [];
		$batch           = [];

		foreach ( Integrity_Table::get_all( 100, 'WHERE id IN (' . implode( ',', $ids ) . ')' ) as $item ) {
			$ticket_object = $this->ticket_data->load_ticket_object( $item['wp_object_id'] );
			if ( ! $ticket_object ) {
				continue;
			}

			$event_id = $ticket_object->get_event_id();

			if ( isset( $batch[ $event_id ] ) ) {
				continue;
			}

			// Set the parent event as a whole as in need of sync.
			$item['wp_object_id'] = $ticket_object->get_event_id();

			$event_id = $ticket_object->get_event_id();

			$tickets = $this->items_sync->sync_event( $event_id, false );

			if ( ! $tickets ) {
				continue;
			}

			$batch[ $event_id ] = $tickets;

			$objects_to_sync[] = $item['id'];
			if ( count( $batch ) > 999 ) {
				$this->regulator->schedule( self::HOOK_DATA_INTEGRITY_SYNC_INVENTORY, [], MINUTE_IN_SECONDS / 6 );
				break;
			}
		}

		if ( empty( $batch ) ) {
			Commerce_Settings::delete( self::OPTION_INTEGRITY_SYNC_INVENTORY );
			return;
		}

		$this->inventory_sync->process_batch( $batch );

		$remaining = array_diff( $ids, $objects_to_sync );

		if ( empty( $remaining ) ) {
			Commerce_Settings::delete( self::OPTION_INTEGRITY_SYNC_INVENTORY );
			return;
		}

		Commerce_Settings::set( self::OPTION_INTEGRITY_SYNC_INVENTORY, $remaining );
		$this->regulator->schedule( self::HOOK_DATA_INTEGRITY_SYNC_INVENTORY, [], MINUTE_IN_SECONDS / 6 );
	}
}
