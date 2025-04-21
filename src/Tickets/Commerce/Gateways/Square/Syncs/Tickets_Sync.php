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
use TEC\Common\StellarWP\DB\DB;
use ActionScheduler_Store;
use TEC\Tickets\Ticket_Data;

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
	 * The option that marks the sync action as completed.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const SYNC_ACTION_COMPLETED_OPTION = 'tec_tickets_commerce_square_sync_action_completed';

	/**
	 * The action that syncs the tickets of a ticket-able post type with Square.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const SYNC_TICKET_ABLE_POST_TYPE_TICKETS_ACTION = 'tec_tickets_commerce_square_sync_ticket_able_post_type_tickets';

	/**
	 * The action that cleans up the tickets of a ticket-able post type with Square.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const SYNC_TICKET_ABLE_POST_TYPE_TICKETS_CLEANUP_ACTION = 'tec_tickets_commerce_square_sync_ticket_able_post_type_tickets_cleanup';

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
		add_action( self::SYNC_TICKET_ABLE_POST_TYPE_TICKETS_CLEANUP_ACTION, [ $this, 'cleanup_ticket_able_post_type_tickets' ] );
		add_action( 'tec_tickets_ticket_upserted', [ $this, 'schedule_ticket_sync' ], 10, 2 );
		add_action( 'tec_tickets_ticket_start_date_trigger', [ $this, 'schedule_ticket_sync_on_date_trigger' ], 10, 4 );
		add_action( 'tec_tickets_ticket_end_date_trigger', [ $this, 'schedule_ticket_sync_on_date_trigger' ], 10, 4 );
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
		remove_action( self::SYNC_TICKET_ABLE_POST_TYPE_TICKETS_CLEANUP_ACTION, [ $this, 'cleanup_ticket_able_post_type_tickets' ] );
	}

	public function schedule_ticket_sync( int $ticket_id, int $parent_id ): void {}

	public function schedule_ticket_sync_on_date_trigger( int $ticket_id, bool $its_happening, int $timestamp, WP_Post $parent ): void {}

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

		foreach ( $ticket_able_post_types as $ticket_able_post_type ) {
			as_unschedule_action( self::SYNC_TICKET_ABLE_POST_TYPE_TICKETS_ACTION, [ $ticket_able_post_type ], self::SYNC_ACTION_GROUP );
			as_schedule_single_action( time(), self::SYNC_TICKET_ABLE_POST_TYPE_TICKETS_ACTION, [ $ticket_able_post_type ], self::SYNC_ACTION_GROUP );
		}
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

		$query = new WP_Query( $args );

		if ( ! $query->have_posts() ) {
			// Post type is synced! No more syncing needed. DB clean up scheduling takes place next.
			as_unschedule_action( self::SYNC_TICKET_ABLE_POST_TYPE_TICKETS_CLEANUP_ACTION, [], self::SYNC_ACTION_GROUP );
			as_schedule_single_action( time(), self::SYNC_TICKET_ABLE_POST_TYPE_TICKETS_CLEANUP_ACTION, [], self::SYNC_ACTION_GROUP );
			return;
		}

		// Reschedule myself to continue in 2 minutes.
		as_schedule_single_action( time() + MINUTE_IN_SECONDS * 2, self::SYNC_TICKET_ABLE_POST_TYPE_TICKETS_ACTION, [ $ticket_able_post_type ], self::SYNC_ACTION_GROUP );

		$post_ids = $query->posts;

		$batch = [];

		foreach ( $post_ids as $post_id ) {
			$tickets_stats = $this->ticket_data->get_posts_tickets_data( $post_id, [ 'rsvp', Series_Passes::TICKET_TYPE ] );

			if (
				empty( $tickets_stats['tickets_on_sale'] ) &&
				empty( $tickets_stats['tickets_about_to_go_to_sale'] ) &&
				empty( $tickets_stats['tickets_have_ended_sales'] )
			) {
				update_post_meta( $post_id, Item::SQUARE_SYNCED_META, time() );
				continue;
			}

			$ticket_ids = array_unique(
				array_merge(
					$tickets_stats['tickets_on_sale'],
					$tickets_stats['tickets_about_to_go_to_sale'],
					$tickets_stats['tickets_have_ended_sales']
				)
			);

			$batch[ $post_id ] = array_filter(
				array_map(
					static fn ( $ticket_id ) => Tickets::load_ticket_object( $ticket_id ),
					$ticket_ids
				)
			);

			if ( empty( $batch[ $post_id ] ) ) {
				update_post_meta( $post_id, Item::SQUARE_SYNCED_META, time() );
			}
		}

		$batch = array_filter( $batch );

		if ( empty( $batch ) ) {
			return;
		}

		$square_batches = $this->remote_objects->transform_batch( $batch );

		$args = [
			'body'    => [
				'idempotency_key' => uniqid( 'tec-square-', true ),
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

		if ( empty( $response['id_mappings']) ) {
			do_action( 'tribe_log', 'error', 'Square Sync', empty( $response['errors'] ) ? 'No ID mappings returned from Square' : $response['errors'] );
			return;
		}

		if ( ! empty( $response['errors'] ) ) {
			do_action( 'tribe_log', 'error', 'Square Sync', $response['errors'] );
		}

		$id_mappings = $response['id_mappings'];

		foreach ( $id_mappings as $id_mapping ) {
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

		if ( empty( $response['objects'] ) ) {
			return;
		}

		foreach ( $response['objects'] as $object ) {
			$this->fire_sync_object_hooks( $object );
		}
	}

	/**
	 * Cleanup the tickets of a ticket-able post type with Square.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function cleanup_ticket_able_post_type_tickets(): void {
		if ( $this->sync_is_in_progress() ) {
			return;
		}

		$query = DB::prepare(
			"UPDATE %i SET meta_key=%s WHERE meta_key=%s ORDER BY meta_id DESC LIMIT 500",
			DB::prefix( 'postmeta' ),
			Item::SQUARE_SYNC_HISTORY_META,
			Item::SQUARE_SYNCED_META,
		);

		$rows = (int) DB::query( $query );

		wp_cache_flush_group( 'post_meta' );

		if ( ! $rows ) {
			update_option( self::SYNC_ACTION_COMPLETED_OPTION, true );
			return;
		}

		as_schedule_single_action( time() + ( MINUTE_IN_SECONDS / 6 ), self::SYNC_TICKET_ABLE_POST_TYPE_TICKETS_CLEANUP_ACTION, [], self::SYNC_ACTION_GROUP );
	}

	protected function get_as_actions_with_status( string $hook, array $status = [], ?array $args = null ): array {
		if ( ! $status ) {
			// Cant be set as default arguments since ActionScheduler_Store class is not available during parsing this.
			$status = [ ActionScheduler_Store::STATUS_PENDING, ActionScheduler_Store::STATUS_RUNNING ];
		}

		$params = [
			'hook'     => $hook,
			'status'   => $status,
			'orderby'  => 'date',
			'order'    => 'ASC',
			'group'    => self::SYNC_ACTION_GROUP,
			'per_page' => 100,
			'offset'   => 0,
		];

		if ( is_array( $args ) ) {
			$params['args'] = $args;
		}

		return as_get_scheduled_actions( $params, OBJECT );
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
		return count( $this->get_as_actions_with_status( self::SYNC_TICKET_ABLE_POST_TYPE_TICKETS_ACTION ) ) > 0;
	}

	protected function sync_is_completed(): bool {
		return (bool) get_option( self::SYNC_ACTION_COMPLETED_OPTION, false );
	}
}
