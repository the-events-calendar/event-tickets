<?php
/**
 * Ensures the data integrity of the Square sync.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Contracts\Container;
use TEC\Common\StellarWP\Schema\Register;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Item;
use TEC\Tickets\Commerce\Gateways\Square\Settings;
use TEC\Tickets\Commerce\Gateways\Square\Requests;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\SquareRateLimitedException;

/**
 * Integrity_Controller class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */
class Integrity_Controller extends Controller_Contract {
	/**
	 * Hook to check the data integrity.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const HOOK_CHECK_DATA_INTEGRITY = 'tec_tickets_commerce_square_check_data_integrity';

	/**
	 * Hook to delete the items.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const HOOK_DATA_INTEGRITY_DELETE_ITEMS = 'tec_tickets_commerce_square_data_integrity_delete_items';

	/**
	 * Hook to check the items.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const HOOK_DATA_INTEGRITY_CHECK_ITEMS = 'tec_tickets_commerce_square_data_integrity_check_items';

	/**
	 * Hook to sync the items.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const HOOK_DATA_INTEGRITY_SYNC_ITEMS = 'tec_tickets_commerce_square_data_integrity_sync_items';

	/**
	 * Option to store the items to delete.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const OPTION_INTEGRITY_CHECK_DELETED_ITEMS = 'tickets_commerce_square_integrity_items_to_delete_%s';

	/**
	 * Option to store the items to check.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const OPTION_INTEGRITY_CHECK_ITEMS = 'tickets_commerce_square_integrity_items_to_check_%s';

	/**
	 * Option to store the items to sync.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const OPTION_INTEGRITY_SYNC_ITEMS = 'tickets_commerce_square_integrity_items_to_sync_%s';

	/**
	 * Items to store.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected static array $items = [];

	/**
	 * Whether the mode is production.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	protected static bool $is_prod_mode;

	/**
	 * Regulator.
	 *
	 * @since TBD
	 *
	 * @var Regulator
	 */
	private Regulator $regulator;

	/**
	 * Items sync.
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
	 * @param Container $container Container.
	 * @param Regulator $regulator Regulator.
	 * @param Items_Sync $items_sync Items sync.
	 */
	public function __construct( Container $container, Regulator $regulator, Items_Sync $items_sync ) {
		parent::__construct( $container );
		$this->regulator  = $regulator;
		$this->items_sync = $items_sync;

		self::$is_prod_mode = ! $this->container->get( Merchant::class )->is_test_mode();
	}

	/**
	 * Register the controller's hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function do_register(): void {
		add_action( 'tec_tickets_commerce_square_object_synced', [ $this, 'enqueue_objects_for_storage' ], 10, 3 );
		add_action( 'tec_shutdown', [ $this, 'store_and_flush_items' ] );
		add_action( 'init', [ $this, 'schedule_data_integrity_check' ] );
		Register::table( Integrity_Table::class );
	}

	/**
	 * Unregister the controller's hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_commerce_square_object_synced', [ $this, 'enqueue_objects_for_storage' ], 10, 3 );
		remove_action( 'tec_shutdown', [ $this, 'store_and_flush_items' ] );
		remove_action( 'init', [ $this, 'schedule_data_integrity_check' ] );
	}

	/**
	 * Schedule the batch sync.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function schedule_data_integrity_check(): void {
		$this->regulator->schedule( self::HOOK_CHECK_DATA_INTEGRITY, [], 6 * HOUR_IN_SECONDS );
	}

	/**
	 * Store and flush the items.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @param string $square_object_id The Square object ID.
	 * @param int    $wp_object_id     The WordPress object ID.
	 * @param array  $square_object    The Square object.
	 *
	 * @return void
	 */
	public function enqueue_objects_for_storage( string $square_object_id, int $wp_object_id, array $square_object ): void {
		self::$items[] = [
			[
				'square_object_id'   => $square_object_id,
				'wp_object_id'       => $wp_object_id,
				'square_object_hash' => md5( wp_json_encode( $square_object ) ),
				'last_checked'       => current_time( 'mysql' ),
				'mode'               => self::$is_prod_mode,
			],
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
	 * @since TBD
	 *
	 * @return void
	 */
	public function check_data_integrity(): void {
		$a_few_minutes_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-5 minutes' ) );

		$to_be_deleted   = [];
		$to_local_delete = [];
		$to_be_checked   = [];

		foreach ( Integrity_Table::fetch_all( 100, OBJECT, "last_checked < '{$a_few_minutes_ago}' AND mode = " . (int) self::$is_prod_mode ) as $item ) {
			$post_object = get_post( $item->wp_object_id );

			if ( ! $post_object ) {
				$to_be_deleted[] = $item->id;
				continue;
			}

			if ( Item::get_remote_object_id( $item->wp_object_id ) !== $item->square_object_id ) {
				$to_be_deleted[] = $item->id;
				continue;
			}

			if ( isset( $to_be_checked[ $item->square_object_id ] ) ) {
				$previous_item = $to_be_checked[ $item->square_object_id ];
				$to_local_delete[] = $previous_item->last_checked > $item->last_checked ? $item : $previous_item;
				$to_be_checked[ $item->square_object_id ] = $previous_item->last_checked > $item->last_checked ? $previous_item : $item;
				continue;
			}

			$to_be_checked[ $item->square_object_id ] = $item;
		}

		if ( ! empty( $to_local_delete ) ) {
			$remote_objects = tribe( Remote_Objects::class );
			foreach ( $to_local_delete as $item ) {
				$remote_objects->delete_remote_object_data( $item->wp_object_id );
			}

			Integrity_Table::delete_many( wp_list_pluck( $to_local_delete, 'id' ) );
		}


		unset( $to_local_delete );

		if ( ! empty( $to_be_deleted ) ) {
			Settings::set_environmental_option( self::OPTION_INTEGRITY_CHECK_DELETED_ITEMS, $to_be_deleted );
			$this->regulator->schedule( self::HOOK_DATA_INTEGRITY_DELETE_ITEMS, [], HOUR_IN_SECONDS );
		}

		unset( $to_be_deleted );

		if ( empty( $to_be_checked ) ) {
			return;
		}

		if ( 1000 < count( $to_be_checked ) ) {
			Settings::set_environmental_option( self::OPTION_INTEGRITY_CHECK_ITEMS, $to_be_checked );
			$this->regulator->schedule( self::HOOK_DATA_INTEGRITY_CHECK_ITEMS, [] );
			return;
		}

		// If they are less than 1000, lets save ourselves some time and figure out which ones need to be actually synced.
		$this->check_items( $to_be_checked );
	}

	/**
	 * Delete the items.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function delete_items(): void {
		$ids = Settings::get_environmental_option( self::OPTION_INTEGRITY_CHECK_DELETED_ITEMS );

		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return;
		}

		$ids = array_filter( array_map( 'intval', $ids ) );

		if ( empty( $ids ) ) {
			return;
		}

		$deleted = [];

		// Guard against rate limiting.
		try {
			$deleted_object_ids = [];
			foreach ( Integrity_Table::fetch_all( 100, OBJECT, "id IN (" . implode( ',', $ids ) . ")" ) as $item ) {
				if ( in_array( $item->square_object_id, $deleted_object_ids, true ) ) {
					continue;
				}

				$response = Requests::delete( sprintf( 'catalog/object/%s', $item->square_object_id ) );

				if ( empty( $response['deleted_object_ids'] ) || ! is_array( $response['deleted_object_ids'] ) ) {
					continue;
				}

				$deleted_object_ids = array_merge( $deleted_object_ids, $response['deleted_object_ids'] );

				$deleted[] = $item->id;
			}

			unset( $deleted_object_ids );
		} catch ( SquareRateLimitedException $e ) {
			Integrity_Table::delete_many( $deleted );
			Settings::set_environmental_option( self::OPTION_INTEGRITY_CHECK_DELETED_ITEMS, array_diff( $ids, $deleted ) );
			// Throw again so that it can be handled by the regulator.
			throw $e;
		}

		Integrity_Table::delete_many( $ids );
		Settings::delete_environmental_option( self::OPTION_INTEGRITY_CHECK_DELETED_ITEMS );
	}

	/**
	 * Check the items.
	 *
	 * @since TBD
	 *
	 * @param array $to_be_checked The items to check.
	 *
	 * @return void
	 */
	public function check_items( array $to_be_checked = [] ): void {
		if ( empty( $to_be_checked ) ) {
			$to_be_checked = Settings::get_environmental_option( self::OPTION_INTEGRITY_CHECK_ITEMS );
		}

		if ( empty( $to_be_checked ) ) {
			return;
		}

		// Keep the first 1000 items.
		$first_thousand = array_slice( $to_be_checked, 0, 1000 );
		$remaining = array_slice( $to_be_checked, 1000 );
		$to_be_checked = $first_thousand;

		$payload = [
			'object_ids'              => wp_list_pluck( $to_be_checked, 'square_object_id' ),
			'include_related_objects' => false,
			'include_deleted_objects' => false,
		];

		$response = Requests::post(
			'catalog/batch-retrieve'
			[],
			$payload
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

		Settings::delete_environmental_option( self::OPTION_INTEGRITY_CHECK_ITEMS );

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

		$actually_in_need_of_sync = [];

		foreach ( $response['objects'] as $object ) {
			if ( ! isset( $to_be_checked[ $object['id'] ] ) ) {
				// What is going on here ??
				continue;
			}

			if ( $to_be_checked[ $object['id'] ]->square_object_hash === md5( wp_json_encode( $object ) ) ) {
				continue;
			}

			$versioned_object = $object;
			$versioned_object['version'] = (int) Settings::get_environmental_meta( $to_be_checked[ $object['id'] ]->wp_object_id, Item::SQUARE_VERSION_META );

			// We always update the object's version so that we can modify it.
			Settings::set_environmental_meta( $to_be_checked[ $object['id'] ]->wp_object_id, Item::SQUARE_VERSION_META, $object['version'] );

			if ( $to_be_checked[ $object['id'] ]->square_object_hash === md5( wp_json_encode( $versioned_object ) ) ) {
				// Only the version was different, so we updating the version brings it in sync.
				continue;
			}

			$actually_in_need_of_sync[] = $to_be_checked[ $object['id'] ]->id;
		}

		if ( ! empty( $remaining ) ) {
			Settings::set_environmental_option( self::OPTION_INTEGRITY_CHECK_ITEMS, $remaining );
			$this->regulator->schedule( self::HOOK_DATA_INTEGRITY_CHECK_ITEMS, [], 5 * MINUTE_IN_SECONDS );
		}

		if ( empty( $remaining ) ) {
			$this->regulator->schedule( self::HOOK_DATA_INTEGRITY_SYNC_ITEMS, [], 5 * MINUTE_IN_SECONDS );
		}

		if ( empty( $actually_in_need_of_sync ) ) {
			return;
		}

		$previous = (array) Settings::get_environmental_option( self::OPTION_INTEGRITY_SYNC_ITEMS );
		Settings::set_environmental_option( self::OPTION_INTEGRITY_SYNC_ITEMS, array_merge( $previous, $actually_in_need_of_sync ) );
	}

	/**
	 * Sync the items.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function sync_items(): void {
		$ids = Settings::get_environmental_option( self::OPTION_INTEGRITY_SYNC_ITEMS );

		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return;
		}

		$ids = array_filter( array_map( 'intval', $ids ) );

		if ( empty( $ids ) ) {
			return;
		}

		$objects_to_sync = [];
		$skipped_tickets = [];
		$batch           = [];

		foreach ( Integrity_Table::fetch_all( 100, OBJECT, "id IN (" . implode( ',', $ids ) . ")" ) as $item ) {
			$is_ticket = in_array( get_post_type( $item->wp_object_id ), tribe_tickets()->ticket_types(), true );

			if ( $is_ticket ) {
				$skipped_tickets[] = $item->id;
				continue;
			}

			$tickets = $this->items_sync->sync_event( $item->wp_object_id, false );

			if ( ! $tickets ) {
				continue;
			}

			$batch[ $item->wp_object_id ] = $tickets;

			$objects_to_sync[] = $item->id;
			if ( count( $batch ) > 999 ) {
				$this->regulator->schedule( self::HOOK_DATA_INTEGRITY_SYNC_ITEMS, [], 5 * MINUTE_IN_SECONDS );
				break;
			}
		}

		$this->items_sync->process_batch( $batch );

		$remaining = array_diff( $ids, $objects_to_sync, $skipped_tickets );

		if ( empty( $remaining ) ) {
			Settings::delete_environmental_option( self::OPTION_INTEGRITY_SYNC_ITEMS );
			return;
		}

		Settings::set_environmental_option( self::OPTION_INTEGRITY_SYNC_ITEMS, $remaining );
		$this->regulator->schedule( self::HOOK_DATA_INTEGRITY_SYNC_ITEMS, [], 5 * MINUTE_IN_SECONDS );
	}
}
