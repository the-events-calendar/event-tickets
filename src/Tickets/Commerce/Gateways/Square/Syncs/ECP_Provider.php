<?php
/**
 * ECP provider.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use Tribe\Events\Virtual\Models\Event as Virtual_Event;
use WP_Post;

/**
 * ECP provider.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */
class ECP_Provider extends Controller_Contract {
	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function do_register(): void {
		add_filter( 'tec_tickets_commerce_square_event_data', [ $this, 'filter_event_item_data' ], 10, 2 );
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_commerce_square_event_data', [ $this, 'filter_event_item_data' ], 10 );
	}

	public function filter_event_item_data( array $data, WP_Post $event ): array {
		$tribe_event = tribe_get_event( $event->ID );
		if ( ! $tribe_event->virtual ) {
			return $data;
		}

		$data['event_location_types'] = [ 'ONLINE' ];
		$data['online_url']           = $tribe_event->virtual_url;

		return $data;
	}
}
