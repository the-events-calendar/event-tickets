<?php

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Event_Item;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Inventory_Change;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Ticket_Item;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;
use TEC\Tickets\Commerce\Gateways\Square\Requests;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\NoChangeNeededException;

class Remote_Objects {

	public const SQUARE_DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.v\Z';

	public function transform( array $batch ): array {
		$transformed = [];

		foreach ( $batch as $post_id => $tickets ) {
			$transformed[] = new Event_Item( $post_id, $tickets );
		}

		return $transformed;
	}

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

	public function transform_inventory_batch( array $batch ): array {
		$transformed = [];

		$location_ids = $this->get_location_ids();

		if ( empty( $location_ids ) ) {
			return [];
		}

		$cache = tribe_cache();

		$cache_key = 'square_sync_synced_objects';

		$data = [];

		foreach ( $batch as $post_id =>$tickets ) {
			foreach ( $tickets as $ticket ) {
				foreach ( $location_ids as $location_id ) {
					try {
						$change = new Inventory_Change( 'ADJUSTMENT', new Ticket_Item( $ticket ), [ 'location_id' => $location_id ] );
					} catch ( NoChangeNeededException $e ) {
						if ( ! isset( $data[ $post_id ] ) ) {
							$data[ $post_id ] = [];
						}

						$data[ $post_id ][] = $ticket;
						continue;
					}

					$transformed[] = $change;
				}
			}
		}

		$cache[ $cache_key ] = $data;

		return $transformed;
	}

	public function cache_remote_object_state( array $batch ): void {
		$data = [
			'location_ids'       => $this->get_location_ids(),
			'catalog_object_ids' => [],
			// 1 second ago.
			// 'updated_after'      => date( self::SQUARE_DATE_TIME_FORMAT, time() - 1 ),
		];

		foreach ( $batch as $tickets ) {
			foreach ( $tickets as $ticket ) {
				$ticket_item = new Ticket_Item( $ticket );
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
			$cache_key = 'square_sync_object_state_' . $count['catalog_object_id'] . '_' . $count['location_id'];
			$cache[ $cache_key ] = [
				'quantity' => $count['quantity'],
				'state'    => $count['state'],
			];
		}
	}

	protected function get_location_ids(): array {
		$merchant = tribe( Merchant::class );

		return array_filter(
			[
				$merchant->get_location_id(),
				// $merchant->get_pos_location_id( $event_id ),
			]
		);
	}
}