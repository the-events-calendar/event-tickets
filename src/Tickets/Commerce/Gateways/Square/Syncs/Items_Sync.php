<?php
/**
 * Syncs tickets with Square controller.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use WP_Query;
use TEC\Tickets\Commerce\Gateways\Square\Requests;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Item;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Controller as Sync_Controller;
use TEC\Tickets\Commerce\Settings as Commerce_Settings;
use TEC\Tickets\Commerce\Meta as Commerce_Meta;

/**
 * Class Tickets_Sync
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */
class Items_Sync {
	/**
	 * The action that syncs an individual event and its tickets with Square.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const HOOK_SYNC_EVENT_ACTION = 'tec_tickets_commerce_square_sync_event';

	/**
	 * The action that syncs the deletion of an event or ticket with Square.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const HOOK_SYNC_DELETE_EVENT_ACTION = 'tec_tickets_commerce_square_sync_delete_event';

	/**
	 * The action that syncs the tickets of a ticket-able post type with Square.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const HOOK_SYNC_ACTION = 'tec_tickets_commerce_square_sync_post_type';

	/**
	 * The remote objects instance.
	 *
	 * @since 5.24.0
	 *
	 * @var Remote_Objects
	 */
	private Remote_Objects $remote_objects;

	/**
	 * Constructor.
	 *
	 * @since 5.24.0
	 *
	 * @param Remote_Objects $remote_objects The remote objects instance.
	 */
	public function __construct( Remote_Objects $remote_objects ) {
		$this->remote_objects = $remote_objects;
	}

	/**
	 * Sync the deletion of an event or ticket with Square.
	 *
	 * @since 5.24.0
	 *
	 * @param int    $object_id        The object ID.
	 * @param string $remote_object_id The remote object ID.
	 *
	 * @return void
	 */
	public function sync_delete_event( int $object_id = 0, string $remote_object_id = '' ): void {
		$this->remote_objects->delete( $object_id, $remote_object_id );
	}

	/**
	 * Sync the tickets for a ticket type.
	 *
	 * @since 5.24.0
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
			 * @since 5.24.0
			 *
			 * @param int $posts_per_page The number of posts to sync.
			 */
			'posts_per_page'         => min( 1000, max( 1, (int) apply_filters( 'tec_tickets_commerce_square_sync_post_type_posts_per_page', 100 ) ) ),
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'post_type'              => $ticket_able_post_type,
			'tribe-has-tickets'      => true,
			'post_status'            => 'publish',
			'fields'                 => 'ids',
			'meta_query'             => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				[
					'key'     => Commerce_Settings::get_key( Item::SQUARE_SYNCED_META ),
					'compare' => 'NOT EXISTS',
				],
			],
		];

		$query = new WP_Query(
			/**
			 * Filters the query arguments for the ticket-able post type tickets sync.
			 *
			 * @since 5.24.0
			 *
			 * @param array $args The query arguments.
			 */
			(array) apply_filters( 'tec_tickets_commerce_square_sync_post_type_query_args', $args )
		);

		$regulator = tribe( Regulator::class );

		if ( ! $query->have_posts() ) {
			// Post type is synced! Now on to sync the inventory.
			$regulator->schedule( Inventory_Sync::HOOK_SYNC_ACTION, [ $ticket_able_post_type ] );
			return;
		}

		Commerce_Settings::set( Sync_Controller::OPTION_SYNC_ACTIONS_IN_PROGRESS, time(), [ $ticket_able_post_type ] );

		// Reschedules itself to continue in 2 minutes.
		$regulator->schedule( self::HOOK_SYNC_ACTION, [ $ticket_able_post_type ], MINUTE_IN_SECONDS * 2, false );

		$post_ids = $query->posts;

		$batch = [];

		foreach ( $post_ids as $post_id ) {
			$tickets = $this->sync_event( $post_id, false );

			if ( ! $tickets ) {
				Commerce_Meta::set( $post_id, Item::SQUARE_SYNCED_META, false );
				continue;
			}

			$batch[ $post_id ] = $tickets;
		}

		$batch = array_filter( $batch );

		if ( empty( $batch ) ) {
			return;
		}

		$this->process_batch( $batch );

		$discarded_objects = tribe_cache()['square_items_sync_discarded_objects'] ?? [];

		foreach ( $discarded_objects as $post_id ) {
			Commerce_Meta::set( $post_id, Item::SQUARE_SYNCED_META, false );
		}
	}

	/**
	 * Sync the event.
	 *
	 * @since 5.24.0
	 *
	 * @param int  $event_id The event ID.
	 * @param bool $execute  Whether to execute the sync.
	 *
	 * @return array The tickets.
	 */
	public function sync_event( int $event_id, bool $execute = true ): array {
		$tickets = Sync_Controller::get_sync_able_tickets_of_event( $event_id );

		if ( ! $execute ) {
			return $tickets;
		}

		if ( empty( $tickets ) ) {
			$this->remote_objects->delete( $event_id );
			return [];
		}

		$this->process_batch( [ $event_id => $tickets ] );

		/**
		 * Sync the events inventory.
		 *
		 * @since 5.24.0
		 *
		 * @param int $event_id  The event ID.
		 * @param bool $execute  Whether to execute the sync.
		 * @param array $tickets The tickets.
		 */
		do_action( Inventory_Sync::HOOK_SYNC_EVENT_ACTION, $event_id, true, $tickets );

		return $tickets;
	}

	/**
	 * Process the batch.
	 *
	 * @since 5.24.0
	 *
	 * @param array $batch The batch.
	 *
	 * @return void
	 */
	public function process_batch( array $batch ): void {
		$square_batches = $this->remote_objects->transform_batch( $batch );

		if ( empty( $square_batches ) ) {
			return;
		}

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
				 * @since 5.24.0
				 *
				 * @param string $object_id The object ID.
				 * @param array  $id_mapping The ID mapping.
				 */
				do_action( 'tec_tickets_commerce_square_sync_ticket_id_mapping_' . $id_mapping['client_object_id'], $id_mapping['object_id'], $id_mapping );

				/**
				 * Fires when a ticket ID mapping is received from Square.
				 *
				 * @since 5.24.0
				 *
				 * @param array $id_mapping The ID mapping.
				 */
				do_action( 'tec_tickets_commerce_square_sync_ticket_id_mapping', $id_mapping );
			}
		}

		if ( empty( $response['objects'] ) ) {
			do_action(
				'tribe_log',
				'error',
				'Square Sync',
				[
					'idempotency_key' => $idempotency_key,
					'response'        => $response,
				]
			);
			return;
		}

		foreach ( $response['objects'] as $object ) {
			$this->fire_sync_object_hooks( $object );
		}
	}

	/**
	 * Fires the sync object hooks.
	 *
	 * @since 5.24.0
	 *
	 * @param array $square_object The object.
	 *
	 * @return void
	 */
	protected function fire_sync_object_hooks( array $square_object ): void {
		/**
		 * Fires when a object is received from Square.
		 *
		 * @since 5.24.0
		 *
		 * @param array $square_object The sync object.
		 */
		do_action( 'tec_tickets_commerce_square_sync_object_' . $square_object['id'], $square_object );

		/**
		 * Fires when a object is received from Square.
		 *
		 * @since 5.24.0
		 *
		 * @param array $square_object The sync object.
		 */
		do_action( 'tec_tickets_commerce_square_sync_object', $square_object );

		if ( empty( $square_object['item_data']['variations'] ) || ! is_array( $square_object['item_data']['variations'] ) ) {
			return;
		}

		foreach ( $square_object['item_data']['variations'] as $variation ) {
			$this->fire_sync_object_hooks( $variation );
		}
	}
}
