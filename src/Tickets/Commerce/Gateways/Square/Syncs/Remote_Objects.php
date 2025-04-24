<?php
/**
 * Remote objects.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Event_Item;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Item;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Inventory_Change;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Ticket_Item;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;
use TEC\Tickets\Commerce\Gateways\Square\Requests;
use TEC\Tickets\Ticket_Data;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\NoChangeNeededException;
use InvalidArgumentException;

/**
 * Remote objects.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */
class Remote_Objects {
	/**
	 * The Square date time format.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const SQUARE_DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.v\Z';

	/**
	 * Transform the batch.
	 *
	 * @since TBD
	 *
	 * @param array $batch The batch.
	 *
	 * @return array The transformed batch.
	 */
	public function transform( array $batch ): array {
		$transformed = [];

		foreach ( $batch as $post_id => $tickets ) {
			$transformed[] = new Event_Item( $post_id, $tickets );
		}

		return $transformed;
	}

	/**
	 * Transform the batch.
	 *
	 * @since TBD
	 *
	 * @param array $batch The batch.
	 *
	 * @return array The transformed batch.
	 */
	public function transform_batch( array $batch ): array {
		$transformed = $this->transform( $batch );

		$batches = [];

		foreach ( $transformed as $batch ) {
			$batches[] = [
				'objects' => [ $batch ],
			];
		}

		return $batches;
	}

	/**
	 * Transform the inventory batch.
	 *
	 * @since TBD
	 *
	 * @param array $batch The batch.
	 *
	 * @return array The transformed batch.
	 */
	public function transform_inventory_batch( array $batch ): array {
		$location_ids = $this->get_location_ids();

		if ( empty( $location_ids ) ) {
			return [];
		}

		$cache = tribe_cache();

		$cache_key = 'square_sync_discarded_objects';

		$transformed = [];
		$discarded   = [];

		foreach ( $batch as $post_id => $tickets ) {
			foreach ( $tickets as $ticket ) {
				foreach ( $location_ids as $location_id ) {
					try {
						$change = new Inventory_Change( 'ADJUSTMENT', new Ticket_Item( $ticket ), [ 'location_id' => $location_id ] );
					} catch ( NoChangeNeededException $e ) {
						if ( ! isset( $discarded[ $post_id ] ) ) {
							$discarded[ $post_id ] = [];
						}

						$discarded[ $post_id ][] = $ticket;
						continue;
					}

					$transformed[] = $change;
				}
			}
		}

		$cache[ $cache_key ] = $discarded;

		return $transformed;
	}

	/**
	 * Delete the remote object.
	 *
	 * @since TBD
	 *
	 * @param int    $object_id        The object ID.
	 * @param string $remote_object_id The remote object ID.
	 *
	 * @return void
	 * @throws InvalidArgumentException If no event ID or remote object ID is provided.
	 */
	public function delete( int $object_id = 0, string $remote_object_id = '' ): void {
		if ( ! $object_id && ! $remote_object_id ) {
			throw new InvalidArgumentException( 'Either event ID or remote object ID must be provided' );
		}

		if ( $object_id ) {
			$remote_object_id = $this->delete_remote_object_data( $object_id );
		}

		$response = Requests::delete( sprintf( 'catalog/object/%s', $remote_object_id ) );

		if ( ! empty( $response['errors'] ) ) {
			do_action( 'tribe_log', 'error', 'Square Delete event', $response['errors'] );
		}
	}

	/**
	 * Delete the remote object data.
	 *
	 * @since TBD
	 *
	 * @param int $object_id The object ID.
	 *
	 * @return string The remote object ID.
	 */
	public function delete_remote_object_data( int $object_id ): string {
		// Careful! We store it in local var and then we delete it!
		$remote_object_id = Item::get_remote_object_id( $object_id );
		Item::delete( $object_id );

		$is_event = in_array( get_post_type( $object_id ), (array) tribe_get_option( 'ticket-enabled-post-types', [] ), true );

		if ( ! $is_event ) {
			return $remote_object_id;
		}

		foreach ( tribe( Ticket_Data::class )->get_posts_tickets( $object_id, [] ) as $ticket ) {
			Item::delete( $ticket->ID );
		}

		return $remote_object_id;
	}

	/**
	 * Cache the remote object state.
	 *
	 * @since TBD
	 *
	 * @param array $batch The batch.
	 *
	 * @return void
	 */
	public function cache_remote_object_state( array $batch ): void {
		$data = [
			'location_ids'       => $this->get_location_ids(),
			'catalog_object_ids' => [],
		];

		foreach ( $batch as $tickets ) {
			foreach ( $tickets as $ticket ) {
				$ticket_item                  = new Ticket_Item( $ticket );
				$data['catalog_object_ids'][] = $ticket_item->get_id();
			}
		}

		$args = [
			'body'    => $data,
			'headers' => [
				'Content-Type' => 'application/json',
			],
		];

		$response = Requests::post(
			'inventory/counts/batch-retrieve',
			[],
			$args
		);

		if ( ! empty( $response['errors'] ) ) {
			do_action( 'tribe_log', 'error', 'Square Inventory Sync', $response['errors'] );
		}

		if ( empty( $response['counts'] ) ) {
			return;
		}

		$cache = tribe_cache();

		foreach ( $response['counts'] as $count ) {
			$cache_key           = 'square_sync_object_state_' . $count['catalog_object_id'] . '_' . $count['location_id'];
			$cache[ $cache_key ] = [
				'quantity' => $count['quantity'],
				'state'    => $count['state'],
			];
		}
	}

	/**
	 * Get the location IDs.
	 *
	 * @since TBD
	 *
	 * @return array The location IDs.
	 */
	protected function get_location_ids(): array {
		$merchant = tribe( Merchant::class );

		return array_filter(
			[
				$merchant->get_location_id(),
			]
		);
	}
}
