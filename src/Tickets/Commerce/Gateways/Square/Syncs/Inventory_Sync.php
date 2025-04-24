<?php
/**
 * Syncs tickets with Square controller.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use WP_Query;
use TEC\Common\Contracts\Container;
use TEC\Tickets\Commerce\Gateways\Square\Requests;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Item;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Controller as Sync_Controller;

/**
 * Class Tickets_Sync
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */
class Inventory_Sync extends Controller_Contract {
	/**
	 * The action that syncs the inventory of a ticket-able post type with Square.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const HOOK_SYNC_ACTION = 'tec_tickets_commerce_square_sync_inventory';

	/**
	 * The action that syncs an individual event's inventory with Square.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const HOOK_SYNC_EVENT_ACTION = 'tec_tickets_commerce_square_sync_events_inventory';

	/**
	 * The remote objects instance.
	 *
	 * @since TBD
	 *
	 * @var Remote_Objects
	 */
	private Remote_Objects $remote_objects;

	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param Container      $container      The container instance.
	 * @param Remote_Objects $remote_objects The remote objects instance.
	 */
	public function __construct( Container $container, Remote_Objects $remote_objects ) {
		parent::__construct( $container );
		$this->remote_objects = $remote_objects;
	}

	/**
	 * Register the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function do_register(): void {
		add_action( self::HOOK_SYNC_ACTION, [ $this, 'sync_post_type' ] );
		add_action( self::HOOK_SYNC_EVENT_ACTION, [ $this, 'sync_event' ], 10, 3 );
	}

	/**
	 * Unregister the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( self::HOOK_SYNC_ACTION, [ $this, 'sync_post_type' ] );
		remove_action( self::HOOK_SYNC_EVENT_ACTION, [ $this, 'sync_event' ] );
	}

	/**
	 * Syncs the inventory of a ticket-able post type with Square.
	 *
	 * @since TBD
	 *
	 * @param string $ticket_able_post_type The ticket-able post type.
	 *
	 * @return void
	 */
	public function sync_post_type( string $ticket_able_post_type ): void {
		if ( Sync_Controller::is_sync_completed() ) {
			return;
		}

		$args = [
			/**
			 * Filters the number of "events" aka ticket-able post types to sync with Square per batch.
			 *
			 * Each "event" is created as an Event Item in Square. Each ticket the event is created with is created as a Variation within the Event Item.
			 *
			 * We can send up to 1000 items per batch to Square. We can't send less than 1 as well -_- !
			 *
			 * @since TBD
			 *
			 * @param int $posts_per_page The number of posts to sync.
			 */
			'posts_per_page'    => min( 1000, max( 1, (int) apply_filters( 'tec_tickets_commerce_square_sync_ticket_able_post_type_inventory_posts_per_page', 100 ) ) ),
			'post_type'         => $ticket_able_post_type,
			'tribe-has-tickets' => true,
			'post_status'       => 'publish',
			'fields'            => 'ids',
			'meta_query'        => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				[
					'key'     => Item::SQUARE_SYNCED_META,
					'compare' => 'EXISTS',
				],
			],
		];

		$query = new WP_Query(
			/**
			 * Filters the query arguments for the ticket-able post type tickets sync.
			 *
			 * @since TBD
			 *
			 * @param array $args The query arguments.
			 */
			(array) apply_filters( 'tec_tickets_commerce_square_sync_inventory_query_args', $args )
		);

		if ( ! $query->have_posts() ) {
			tribe_update_option( sprintf( Sync_Controller::OPTION_SYNC_ACTIONS_COMPLETED, $ticket_able_post_type ), time() );
			tribe_remove_option( sprintf( Sync_Controller::OPTION_SYNC_ACTIONS_IN_PROGRESS, $ticket_able_post_type ) );

			if ( Sync_Controller::is_sync_in_progress() ) {
				// Another post type is still syncing.
				return;
			}

			// All post types are synced!
			$this->fire_sync_completed_hook();
		}

		// Reschedules itself to continue in 2 minutes.
		as_schedule_single_action( time() + MINUTE_IN_SECONDS * 2, self::HOOK_SYNC_ACTION, [ $ticket_able_post_type ], Sync_Controller::AS_SYNC_ACTION_GROUP );

		$post_ids = $query->posts;

		$batch = [];

		foreach ( $post_ids as $post_id ) {
			$tickets = $this->sync_event( $post_id, false );

			if ( ! $tickets ) {
				$this->clean_up_synced_meta( $post_id );
				continue;
			}

			$batch[ $post_id ] = $tickets;
		}

		$batch = array_filter( $batch );

		if ( empty( $batch ) ) {
			return;
		}

		$this->process_batch( $batch );
	}

	/**
	 * Syncs the inventory of an event with Square.
	 *
	 * @since TBD
	 *
	 * @param int   $event_id The event ID.
	 * @param bool  $execute  Whether to execute the sync.
	 * @param array $tickets  The tickets.
	 *
	 * @return array The tickets.
	 */
	public function sync_event( int $event_id, bool $execute = true, array $tickets = [] ): array {
		if ( empty( $tickets ) ) {
			$tickets = Sync_Controller::get_sync_able_tickets_of_event( $event_id );
		}

		if ( ! $execute ) {
			return $tickets;
		}

		$this->process_batch( [ $event_id => $tickets ] );

		return $tickets;
	}

	/**
	 * Process the batch.
	 *
	 * @since TBD
	 *
	 * @param array $batch The batch.
	 *
	 * @return void
	 */
	protected function process_batch( array $batch ): void {
		$this->remote_objects->cache_remote_object_state( $batch );

		$square_batches = $this->remote_objects->transform_inventory_batch( $batch );

		$rejected_objects = tribe_cache()['square_sync_discarded_objects'] ?? [];

		foreach ( $rejected_objects as $post_id => $tickets ) {
			if ( count( $tickets ) === count( $batch[ $post_id ] ) ) {
				$this->clean_up_synced_meta( $post_id );
			}
			foreach ( $tickets as $ticket ) {
				$this->clean_up_synced_meta( $ticket->ID );
			}
		}

		if ( empty( $square_batches ) ) {
			return;
		}

		$args = [
			'body'    => [
				'idempotency_key' => uniqid( 'tec-square-', true ),
				'changes'         => $square_batches,
			],
			'headers' => [
				'Content-Type' => 'application/json',
			],
		];

		$response = Requests::post(
			'inventory/changes/batch-create',
			[],
			$args
		);

		if ( ! empty( $response['errors'] ) ) {
			do_action( 'tribe_log', 'error', 'Square Inventory Sync', $response['errors'] );
		}

		if ( empty( $response['counts'] ) ) {
			return;
		}

		foreach ( $response['counts'] as $count ) {
			do_action( 'tec_tickets_commerce_square_sync_inventory_changed_' . $count['catalog_object_id'], $count['state'], $count['quantity'] );
			do_action( 'tec_tickets_commerce_square_sync_inventory_changed', $count['state'], $count['quantity'], $count );
		}

		foreach ( $batch as $post_id => $tickets ) {
			$this->clean_up_synced_meta( $post_id, true );
			foreach ( $tickets as $ticket ) {
				$this->clean_up_synced_meta( $ticket->ID, true );
			}
		}
	}

	/**
	 * Cleans up the synced meta.
	 *
	 * @since TBD
	 *
	 * @param int $object_id The object ID.
	 *
	 * @return void
	 */
	protected function clean_up_synced_meta( int $object_id, bool $force_add_history = false ): void {
		$square_synced = get_post_meta( $object_id, Item::SQUARE_SYNCED_META, true );
		delete_post_meta( $object_id, Item::SQUARE_SYNCED_META );

		if ( ! $force_add_history && ! $square_synced ) {
			return;
		}

		$square_synced = $square_synced && $square_synced > time() - DAY_IN_SECONDS ? $square_synced : time();

		add_post_meta( $object_id, Item::SQUARE_SYNC_HISTORY_META, $square_synced );
	}

	/**
	 * Fires the sync completed hook.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function fire_sync_completed_hook(): void {
		$ticket_able_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

		foreach ( $ticket_able_post_types as $ticket_able_post_type ) {
			tribe_remove_option( sprintf( Sync_Controller::OPTION_SYNC_ACTIONS_IN_PROGRESS, $ticket_able_post_type ) );
			tribe_remove_option( sprintf( Sync_Controller::OPTION_SYNC_ACTIONS_COMPLETED, $ticket_able_post_type ) );
		}

		tribe_update_option( Sync_Controller::OPTION_SYNC_ACTION_COMPLETED, time() );

		/**
		 * Fires when the sync is completed.
		 *
		 * @since TBD
		 */
		do_action( 'tec_tickets_commerce_square_sync_completed' );
	}
}
