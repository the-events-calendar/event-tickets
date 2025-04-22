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
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use Tribe__Tickets__Tickets as Tickets;
use TEC\Tickets\Commerce\Gateways\Square\Requests;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Item;
use TEC\Tickets\Ticket_Data;
use WP_Post;

/**
 * Class Tickets_Sync
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */
class Tickets_Sync extends Controller_Contract {
	/**
	 * The group that the sync action belongs to.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const SYNC_ACTION_GROUP = 'tec_tickets_commerce_square_syncs';

	/**
	 * The action that syncs tickets with Square.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const SYNC_ACTION = 'tec_tickets_commerce_square_sync_tickets';

	/**
	 * The action that syncs an individual event and its tickets with Square.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const SYNC_EVENT_ACTION = 'tec_tickets_commerce_square_sync_event';

	/**
	 * The action that syncs the inventory of a ticket-able post type with Square.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const SYNC_INVENTORY_ACTION = 'tec_tickets_commerce_square_sync_inventory';

	/**
	 * The action that syncs the tickets of a ticket-able post type with Square.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const SYNC_TICKET_ABLE_POST_TYPE_TICKETS_ACTION = 'tec_tickets_commerce_square_sync_ticket_able_post_type_tickets';

	/**
	 * The option that marks the sync action as completed.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const SYNC_ACTION_COMPLETED_OPTION = 'tickets_commerce_square_sync_action_completed';

	protected const SYNC_ACTIONS_IN_PROGRESS_OPTION = 'tickets_commerce_square_sync_ptypes_in_progress_%s';

	protected const SYNC_ACTIONS_COMPLETED_OPTION = 'tickets_commerce_square_sync_ptypes_completed_%s';

	/**
	 * The remote objects instance.
	 *
	 * @since TBD
	 *
	 * @var Remote_Objects
	 */
	private Remote_Objects $remote_objects;

	/**
	 * The ticket data instance.
	 *
	 * @since TBD
	 *
	 * @var Ticket_Data
	 */
	private Ticket_Data $ticket_data;

	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param Container      $container      The container instance.
	 * @param Remote_Objects $remote_objects The remote objects instance.
	 * @param Ticket_Data    $ticket_data    The ticket data instance.
	 */
	public function __construct( Container $container, Remote_Objects $remote_objects, Ticket_Data $ticket_data ) {
		parent::__construct( $container );
		$this->remote_objects = $remote_objects;
		$this->ticket_data    = $ticket_data;
	}

	/**
	 * Register the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function do_register(): void {
		add_action( 'init', [ $this, 'schedule_tickets_sync' ] );
		add_action( self::SYNC_ACTION, [ $this, 'sync_tickets' ] );
		add_action( self::SYNC_TICKET_ABLE_POST_TYPE_TICKETS_ACTION, [ $this, 'sync_ticket_able_post_type_tickets' ] );
		add_action( self::SYNC_EVENT_ACTION, [ $this, 'sync_event' ] );
		add_action( self::SYNC_INVENTORY_ACTION, [ $this, 'sync_inventory' ] );
		add_action( 'tec_tickets_ticket_upserted', [ $this, 'schedule_ticket_sync' ], 10, 2 );
		add_action( 'tec_tickets_ticket_start_date_trigger', [ $this, 'schedule_ticket_sync_on_date_start' ], 10, 4 );
		add_action( 'tec_tickets_ticket_end_date_trigger', [ $this, 'schedule_ticket_sync_on_date_end' ], 10, 4 );
	}

	/**
	 * Unregister the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'init', [ $this, 'schedule_tickets_sync' ] );
		remove_action( self::SYNC_ACTION, [ $this, 'sync_tickets' ] );
		remove_action( self::SYNC_TICKET_ABLE_POST_TYPE_TICKETS_ACTION, [ $this, 'sync_ticket_able_post_type_tickets' ] );
		remove_action( self::SYNC_EVENT_ACTION, [ $this, 'sync_event' ] );
		remove_action( self::SYNC_INVENTORY_ACTION, [ $this, 'sync_inventory' ] );
		remove_action( 'tec_tickets_ticket_upserted', [ $this, 'schedule_ticket_sync' ] );
		remove_action( 'tec_tickets_ticket_start_date_trigger', [ $this, 'schedule_ticket_sync_on_date_start' ] );
		remove_action( 'tec_tickets_ticket_end_date_trigger', [ $this, 'schedule_ticket_sync_on_date_end' ] );
	}

	/**
	 * Schedule the ticket sync.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 * @param int $parent_id The parent ID.
	 *
	 * @return void
	 */
	public function schedule_ticket_sync( int $ticket_id, int $parent_id ): void {
		as_schedule_single_action( time() + MINUTE_IN_SECONDS / 3, self::SYNC_EVENT_ACTION, [ $parent_id ], self::SYNC_ACTION_GROUP );
	}

	/**
	 * Schedule the ticket sync on date start.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 * @param bool $its_happening Whether the ticket is about to go to sale or is already on sale.
	 * @param int $timestamp The timestamp.
	 *
	 * @return void
	 */
	public function schedule_ticket_sync_on_date_start( int $ticket_id, bool $its_happening, int $timestamp, WP_Post $parent ): void {
		$should_sync = $its_happening || time() >= $timestamp - Ticket_Data::get_ticket_about_to_go_to_sale_seconds( $ticket_id );

		if ( ! $should_sync ) {
			return;
		}

		as_schedule_single_action( time(), self::SYNC_EVENT_ACTION, [ $parent->ID ], self::SYNC_ACTION_GROUP );
	}

	/**
	 * Schedule the ticket sync on date end.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 * @param bool $its_happening Whether the ticket is about to go to sale or is already on sale.
	 * @param int $timestamp The timestamp.
	 *
	 * @return void
	 */
	public function schedule_ticket_sync_on_date_end( int $ticket_id, bool $its_happening, int $timestamp, WP_Post $parent ): void {
		if ( ! $its_happening ) {
			// Remove the synced tickets going out of sale at the very last moment.
			as_unschedule_action( self::SYNC_EVENT_ACTION, [ $parent->ID ], self::SYNC_ACTION_GROUP );
			return;
		}

		as_schedule_single_action( time(), self::SYNC_EVENT_ACTION, [ $parent->ID ], self::SYNC_ACTION_GROUP );
	}

	/**
	 * Schedule the tickets sync.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function schedule_tickets_sync(): void {
		if ( as_has_scheduled_action( self::SYNC_ACTION, [], self::SYNC_ACTION_GROUP ) ) {
			return;
		}

		if ( $this->sync_is_completed() || $this->sync_is_in_progress() ) {
			return;
		}

		as_schedule_single_action( time(), self::SYNC_ACTION, [], self::SYNC_ACTION_GROUP );
	}

	/**
	 * Sync the tickets.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function sync_tickets(): void {
		if ( $this->sync_is_completed() ) {
			return;
		}

		$ticket_able_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

		tribe_update_option( sprintf( self::SYNC_ACTIONS_IN_PROGRESS_OPTION, 'default' ), time() );

		foreach ( $ticket_able_post_types as $ticket_able_post_type ) {
			as_unschedule_action( self::SYNC_TICKET_ABLE_POST_TYPE_TICKETS_ACTION, [ $ticket_able_post_type ], self::SYNC_ACTION_GROUP );
			as_schedule_single_action( time(), self::SYNC_TICKET_ABLE_POST_TYPE_TICKETS_ACTION, [ $ticket_able_post_type ], self::SYNC_ACTION_GROUP );
		}
	}

	public function sync_inventory( string $ticket_able_post_type ): void {
		if ( $this->sync_is_completed() ) {
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
			'meta_query'        => [
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
			(array) apply_filters( 'tec_tickets_commerce_square_sync_ticket_able_post_type_tickets_query_args', $args )
		);

		if ( ! $query->have_posts() ) {
			tribe_update_option( sprintf( self::SYNC_ACTIONS_COMPLETED_OPTION, $ticket_able_post_type ), time() );
			tribe_remove_option( sprintf( self::SYNC_ACTIONS_IN_PROGRESS_OPTION, $ticket_able_post_type ) );

			if ( $this->sync_is_in_progress() ) {
				// Another post type is still syncing.
				return;
			}

			// All post types are synced!
			$this->fire_sync_completed_hook();
		}

		// Reschedules itself to continue in 2 minutes.
		as_schedule_single_action( time() + MINUTE_IN_SECONDS * 2, self::SYNC_INVENTORY_ACTION, [ $ticket_able_post_type ], self::SYNC_ACTION_GROUP );

		$post_ids = $query->posts;

		$batch = [];

		foreach ( $post_ids as $post_id ) {
			$tickets = $this->sync_events_inventory( $post_id, false );

			if ( ! $tickets ) {
				continue;
			}

			$batch[ $post_id ] = $tickets;
		}

		$batch = array_filter( $batch );

		if ( empty( $batch ) ) {
			return;
		}

		$this->process_inventory_batch( $batch );
	}


	/**
	 * Sync the tickets for a ticket type.
	 *
	 * @since TBD
	 *
	 * @param string $ticket_able_post_type The ticket-able post type.
	 *
	 * @return void
	 */
	public function sync_ticket_able_post_type_tickets( string $ticket_able_post_type ): void {
		if ( $this->sync_is_completed() ) {
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
			'posts_per_page'    => min( 1000, max( 1, (int) apply_filters( 'tec_tickets_commerce_square_sync_ticket_able_post_type_tickets_posts_per_page', 100 ) ) ),
			'post_type'         => $ticket_able_post_type,
			'tribe-has-tickets' => true,
			'post_status'       => 'publish',
			'fields'            => 'ids',
			'meta_query'        => [
				[
					'key'     => Item::SQUARE_SYNCED_META,
					'compare' => 'NOT EXISTS',
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
			(array) apply_filters( 'tec_tickets_commerce_square_sync_ticket_able_post_type_tickets_query_args', $args )
		);

		if ( ! $query->have_posts() ) {
			tribe_remove_option( sprintf( self::SYNC_ACTIONS_COMPLETED_OPTION, 'default' ) );
			// Post type is synced! Now on to sync the inventory.
			as_schedule_single_action( time(), self::SYNC_INVENTORY_ACTION, [ $ticket_able_post_type ], self::SYNC_ACTION_GROUP );
			return;
		}

		tribe_update_option( sprintf( self::SYNC_ACTIONS_IN_PROGRESS_OPTION, $ticket_able_post_type ), time() );

		// Reschedules itself to continue in 2 minutes.
		as_schedule_single_action( time() + MINUTE_IN_SECONDS * 2, self::SYNC_TICKET_ABLE_POST_TYPE_TICKETS_ACTION, [ $ticket_able_post_type ], self::SYNC_ACTION_GROUP );

		$post_ids = $query->posts;

		$batch = [];

		foreach ( $post_ids as $post_id ) {
			$tickets = $this->sync_event( $post_id, false );

			if ( ! $tickets ) {
				update_post_meta( $post_id, Item::SQUARE_SYNCED_META, time() );
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
	 * Sync the event.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event ID.
	 * @param bool $execute Whether to execute the sync.
	 *
	 * @return array The tickets.
	 */
	public function sync_event( int $event_id, bool $execute = true ): array {
		$tickets_stats = $this->ticket_data->get_posts_tickets_data( $event_id, [ 'rsvp', Series_Passes::TICKET_TYPE ] );

		if (
			empty( $tickets_stats['tickets_on_sale'] ) &&
			empty( $tickets_stats['tickets_about_to_go_to_sale'] ) &&
			empty( $tickets_stats['tickets_have_ended_sales'] )
		) {
			return [];
		}

		$ticket_ids = array_unique(
			array_merge(
				$tickets_stats['tickets_on_sale'],
				$tickets_stats['tickets_about_to_go_to_sale'],
				$tickets_stats['tickets_have_ended_sales']
			)
		);

		$tickets = array_filter(
			array_map(
				static fn ( $ticket_id ) => Tickets::load_ticket_object( $ticket_id ),
				$ticket_ids
			)
		);

		if ( ! $execute ) {
			return $tickets;
		}

		$this->process_batch( [ $event_id => $tickets ] );

		return $tickets;
	}

	public function sync_events_inventory( int $event_id, bool $execute = true ): array {
		$tickets_stats = $this->ticket_data->get_posts_tickets_data( $event_id, [ 'rsvp', Series_Passes::TICKET_TYPE ] );

		if (
			empty( $tickets_stats['tickets_on_sale'] ) &&
			empty( $tickets_stats['tickets_about_to_go_to_sale'] ) &&
			empty( $tickets_stats['tickets_have_ended_sales'] )
		) {
			return [];
		}

		$ticket_ids = array_unique(
			array_merge(
				$tickets_stats['tickets_on_sale'],
				$tickets_stats['tickets_about_to_go_to_sale'],
				$tickets_stats['tickets_have_ended_sales']
			)
		);

		$tickets = array_filter(
			array_map(
				static fn ( $ticket_id ) => Tickets::load_ticket_object( $ticket_id ),
				$ticket_ids
			)
		);

		if ( ! $execute ) {
			return $tickets;
		}

		$this->process_inventory_batch( [ $event_id => $tickets ] );

		return $tickets;
	}

	protected function process_inventory_batch( array $batch ): void {
		$this->remote_objects->cache_remote_object_state( $batch );

		$square_batches = $this->remote_objects->transform_inventory_batch( $batch );

		$rejected_objects = tribe_cache()['square_sync_synced_objects'] ?? [];

		foreach( $rejected_objects as $post_id => $tickets ) {
			if ( count( $tickets ) === count( $batch[ $post_id ] ) ) {
				$this->clean_up_synced_meta( $post_id );
			}
			foreach( $tickets as $ticket ) {
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

		foreach( $batch as $post_id => $tickets ) {
			$this->clean_up_synced_meta( $post_id );
			foreach( $tickets as $ticket ) {
				$this->clean_up_synced_meta( $ticket->ID );
			}
		}
	}

	protected function clean_up_synced_meta( int $object_id ): void {
		$square_synced = get_post_meta( $object_id, Item::SQUARE_SYNCED_META, true );

		$square_synced = $square_synced && $square_synced > time() - DAY_IN_SECONDS ? $square_synced : time();

		delete_post_meta( $object_id, Item::SQUARE_SYNCED_META );
		add_post_meta( $object_id, Item::SQUARE_SYNC_HISTORY_META, $square_synced );
	}

	protected function process_batch( array $batch ): void {
		$square_batches = $this->remote_objects->transform_batch( $batch );

		$idempotency_key = uniqid( 'tec-square-', true );

		$args = [
			'body'    => [
				'idempotency_key' => $idempotency_key,
				'batches'         => $square_batches,
			],
			'headers' => [
				'Content-Type' => 'application/json',
			],
		];

		$response = Requests::post(
			'catalog/batch-upsert',
			[],
			$args
		);

		if ( ! empty( $response['errors'] ) ) {
			do_action( 'tribe_log', 'error', 'Square Sync', (array) $response['errors'] );
			return;
		}

		if ( ! empty( $response['id_mappings'] ) ) {
			foreach ( $response['id_mappings'] as $id_mapping ) {
				/**
				 * Fires when a ticket ID mapping is received from Square.
				 *
				 * @since TBD
				 *
				 * @param string $object_id The object ID.
				 * @param array  $id_mapping The ID mapping.
				 */
				do_action( 'tec_tickets_commerce_square_sync_ticket_id_mapping_' . $id_mapping['client_object_id'], $id_mapping['object_id'], $id_mapping );

				/**
				 * Fires when a ticket ID mapping is received from Square.
				 *
				 * @since TBD
				 *
				 * @param array $id_mapping The ID mapping.
				 */
				do_action( 'tec_tickets_commerce_square_sync_ticket_id_mapping', $id_mapping );
			}
		}

		if ( empty( $response['objects'] ) ) {
			do_action( 'tribe_log', 'error', 'Square Sync', [ 'idempotency_key' => $idempotency_key, 'response' => $response ] );
			return;
		}

		foreach ( $response['objects'] as $object ) {
			$this->fire_sync_object_hooks( $object );
		}
	}

	protected function fire_sync_object_hooks( array $object ): void {
		/**
		 * Fires when a object is received from Square.
		 *
		 * @since TBD
		 *
		 * @param array $object The object.
		 */
		do_action( 'tec_tickets_commerce_square_sync_object_' . $object['id'], $object );

		/**
		 * Fires when a object is received from Square.
		 *
		 * @since TBD
		 *
		 * @param array $object The sync object.
		 */
		do_action( 'tec_tickets_commerce_square_sync_object', $object );

		if ( empty( $object['item_data']['variations'] ) || ! is_array( $object['item_data']['variations'] ) ) {
			return;
		}

		foreach ( $object['item_data']['variations'] as $variation ) {
			$this->fire_sync_object_hooks( $variation );
		}
	}

	protected function sync_is_in_progress(): bool {
		$ticket_able_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );
		foreach ( $ticket_able_post_types as $ticket_able_post_type ) {
			if ( tribe_get_option( sprintf( self::SYNC_ACTIONS_IN_PROGRESS_OPTION, $ticket_able_post_type ), false ) ) {
				return true;
			}
		}

		if ( tribe_get_option( sprintf( self::SYNC_ACTIONS_IN_PROGRESS_OPTION, 'default' ), false ) ) {
			return true;
		}

		return false;
	}

	protected function fire_sync_completed_hook(): void {
		$ticket_able_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

		foreach ( $ticket_able_post_types as $ticket_able_post_type ) {
			tribe_remove_option( sprintf( self::SYNC_ACTIONS_IN_PROGRESS_OPTION, $ticket_able_post_type ) );
			tribe_remove_option( sprintf( self::SYNC_ACTIONS_COMPLETED_OPTION, $ticket_able_post_type ) );
		}

		tribe_update_option( self::SYNC_ACTION_COMPLETED_OPTION, time() );

		/**
		 * Fires when the sync is completed.
		 *
		 * @since TBD
		 */
		do_action( 'tec_tickets_commerce_square_sync_completed' );
	}

	protected function sync_is_completed(): bool {
		return (bool) tribe_get_option( self::SYNC_ACTION_COMPLETED_OPTION, false );
	}
}
