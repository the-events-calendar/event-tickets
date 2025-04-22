<?php

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Event_Item;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Inventory_Change;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;

class Remote_Objects {
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

		$merchant = tribe( Merchant::class );

		$web_location_id = $merchant->get_location_id();

		foreach ( $batch as $post_id => $tickets ) {
			$event_pos_location_id = $merchant->get_pos_location_id( $post_id );

			$location_ids = array_filter(
				[
					$web_location_id,
					$event_pos_location_id,
				]
			);

			if ( empty( $location_ids ) ) {
				continue;
			}

			foreach ( $tickets as $ticket ) {
				foreach ( $location_ids as $location_id ) {
					$transformed[] = new Inventory_Change( 'PHYSICAL_COUNT', $ticket, [ 'location_id' => $location_id ] );
				}
			}
		}

		return $transformed;
	}
}