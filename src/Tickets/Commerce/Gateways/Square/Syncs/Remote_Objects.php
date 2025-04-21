<?php

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Event_Item;

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
}