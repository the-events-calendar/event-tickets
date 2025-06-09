<?php
/**
 * Event details provider.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use WP_Post;
use Tribe__Events__Timezones as Timezones;

/**
 * Event details provider.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */
class Tec_Event_Details_Provider extends Controller_Contract {
	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function do_register(): void {
		add_filter( 'tec_tickets_commerce_square_event_item_data', [ $this, 'filter_event_item_data' ], 10, 2 );

		$this->container->register_on_action( 'tec_events_pro_fully_loaded', ECP_Provider::class );
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_commerce_square_event_item_data', [ $this, 'filter_event_item_data' ], 10 );

		if ( $this->container->isBound( ECP_Provider::class ) ) {
			$this->container->get( ECP_Provider::class )->unregister();
		}
	}

	/**
	 * Filters the event item data before it is sent to Square.
	 *
	 * @since 5.24.0
	 *
	 * @param array   $data  The event data.
	 * @param WP_Post $event The event post object.
	 *
	 * @return array The filtered event data.
	 */
	public function filter_event_item_data( array $data, WP_Post $event ): array {
		if ( ! tribe_is_event( $event ) ) {
			return $data;
		}

		$timezone        = Timezones::get_event_timezone_string( $event->ID );
		$start_timestamp = Timezones::event_start_timestamp( $event->ID, $timezone );
		$end_timestamp   = Timezones::event_end_timestamp( $event->ID, $timezone );

		$event_data = [
			'start_at'                 => gmdate( Remote_Objects::SQUARE_DATE_TIME_FORMAT, $start_timestamp ),
			'end_at'                   => gmdate( Remote_Objects::SQUARE_DATE_TIME_FORMAT, $end_timestamp ),
			'event_location_time_zone' => $timezone,
			'event_location_name'      => '',
			'event_location_types'     => [ 'IN_PERSON' ], // Virtual event support is added by ECP integration.
			'all_day_event'            => tribe_event_is_all_day( $event->ID ),
		];

		if ( tribe_has_venue( $event->ID ) ) {
			$event_data['event_location_name'] = tribe_get_venue( $event->ID );

			if ( tribe_address_exists( $event->ID ) ) {
				// @todo the docs about this are missing in Square API reference as a result its incomplete.
				$event_data['address_id'] = tribe_get_venue_id( $event->ID );
			}
		}

		/**
		 * Filters the event data before it is sent to Square.
		 *
		 * @since 5.24.0
		 *
		 * @param array   $data  The event data.
		 * @param WP_Post $event The event post object.
		 *
		 * @return array The filtered event data.
		 */
		$event_data = (array) apply_filters( 'tec_tickets_commerce_square_event_data', $event_data, $event );

		if ( empty( $event_data ) ) {
			return $data;
		}

		if ( empty( $event_data['online_url'] ) && empty( $event_data['address_id'] ) ) {
			// Event NEEDS to be virtual or in-person. Instead of failing to sync, lets simply fail to sync event data only.
			return $data;
		}

		$data['item_data']['event'] = $event_data;

		// Until we receive confirmation from Square that we can add those fields, we don't.
		unset( $data['item_data']['event'] );

		return $data;
	}
}
