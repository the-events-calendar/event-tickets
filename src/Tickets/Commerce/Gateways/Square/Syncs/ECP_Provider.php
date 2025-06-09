<?php
/**
 * ECP provider.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use WP_Post;

/**
 * ECP provider.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */
class ECP_Provider extends Controller_Contract {
	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function do_register(): void {
		add_filter( 'tec_tickets_commerce_square_event_data', [ $this, 'filter_event_item_data' ], 10, 2 );
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_commerce_square_event_data', [ $this, 'filter_event_item_data' ], 10 );
	}

	/**
	 * Filters the event item data.
	 *
	 * @since 5.24.0
	 *
	 * @param array   $data  The event data.
	 * @param WP_Post $event The event post object.
	 *
	 * @return array The filtered event data.
	 */
	public function filter_event_item_data( array $data, WP_Post $event ): array {
		$tribe_event = tribe_get_event( $event->ID );
		if ( ! ( $tribe_event->virtual ?? false ) ) {
			return $data;
		}

		$data['event_location_types'] = [ 'ONLINE' ];
		$data['online_url']           = $tribe_event->virtual_url;

		return $data;
	}
}
