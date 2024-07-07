<?php
/**
 * Handles the AJAX requests for the Seating feature.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Admin;
 */

namespace TEC\Tickets\Seating\Admin;

use TEC\Common\Contracts\Container;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Seating\Service\Layouts;
use TEC\Tickets\Seating\Service\Maps;
use TEC\Tickets\Seating\Service\Seat_Types;
use TEC\Tickets\Seating\Service\Service;

/**
 * Class Ajax.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Admin;
 */
class Ajax extends Controller_Contract {
	/**
	 * The nonce action.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'tec-tickets-seating-service-ajax';

	/**
	 * The action to invalidate the maps and layouts cache.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE = 'tec_tickets_seating_service_invalidate_maps_layouts_cache';

	/**
	 * The action to invalidate the layouts cache.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ACTION_INVALIDATE_LAYOUTS_CACHE = 'tec_tickets_seating_service_invalidate_layouts_cache';
	
	/**
	 * The action to delete a map.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ACTION_DELETE_MAP = 'tec_tickets_seating_service_delete_map';
	
	/**
	 * The action to delete a layout.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ACTION_DELETE_LAYOUT = 'tec_tickets_seating_service_delete_layout';

	/**
	 * A reference to the Seat Types service object.
	 *
	 * @since TBD
	 *
	 * @var Seat_Types
	 */
	private Seat_Types $seat_types;

	/**
	 * Ajax constructor.
	 *
	 * @since TBD
	 *
	 * @param Container  $container  A reference to the DI container object.
	 * @param Seat_Types $seat_types A reference to the Seat Types service object.
	 */
	public function __construct( Container $container, Seat_Types $seat_types ) {
		parent::__construct( $container );
		$this->seat_types = $seat_types;
	}

	/**
	 * Unsubscribes the controller from WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'wp_ajax_seat_types_by_layout_id', [ $this, 'fetch_seat_types_by_layout_id' ] );
		remove_action(
			'wp_ajax_' . self::ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE,
			[ $this, 'invalidate_maps_layouts_cache' ]
		);
		remove_action( 'wp_ajax_' . self::ACTION_INVALIDATE_LAYOUTS_CACHE, [ $this, 'invalidate_layouts_cache' ] );
		remove_action( 'wp_ajax_' . self::ACTION_DELETE_MAP, [ $this, 'delete_map_from_service' ] );
		remove_action( 'wp_ajax_' . self::ACTION_DELETE_LAYOUT, [ $this, 'delete_layout_from_service' ] );
	}

	/**
	 * Returns the set of URLs to be used for the AJAX requests.
	 *
	 * @since TBD
	 *
	 * @return array<string, string> The set of URLs to be used for the AJAX requests.
	 */
	public function get_urls(): array {
		return [
			'seatTypesByLayoutId' => add_query_arg(
				[
					'action'      => 'seat_types_by_layout_id',
					'_ajax_nonce' => wp_create_nonce( 'seat_types_by_layout_id' ),
				],
				admin_url( 'admin-ajax.php' )
			),
		];
	}

	/**
	 * Hooked to the `wp_ajax_seat_types_by_layout_id` action, this method will return the seat types in option format
	 * for the given layout IDs.
	 *
	 * @since TBD
	 *
	 * @return void The seat types in option format for the given layout IDs are returned as JSON.
	 */
	public function fetch_seat_types_by_layout_id(): void {
		if ( ! check_ajax_referer( 'seat_types_by_layout_id', '_ajax_nonce', false ) ) {
			wp_send_json_error(
				[
					'error' => 'Nonce verification failed',
				]
			);

			return;
		}

		$layout_id = tribe_get_request_var( 'layout' );

		if ( empty( $layout_id ) ) {
			wp_send_json_success( [] );

			return;
		}

		$seat_types = $this->seat_types->get_in_option_format( [ $layout_id ] );

		wp_send_json_success( $seat_types );
	}

	/**
	 * Registers the controller bindings and subscribes to WordPress hooks.
	 *
	 * @since TBD
	 */
	protected function do_register(): void {
		add_action( 'wp_ajax_seat_types_by_layout_id', [ $this, 'fetch_seat_types_by_layout_id' ] );
		add_action( 'wp_ajax_' . self::ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE, [ $this, 'invalidate_maps_layouts_cache' ] );
		add_action( 'wp_ajax_' . self::ACTION_INVALIDATE_LAYOUTS_CACHE, [ $this, 'invalidate_layouts_cache' ] );
		add_action( 'wp_ajax_' . self::ACTION_DELETE_MAP, [ $this, 'delete_map_from_service' ] );
		add_action( 'wp_ajax_' . self::ACTION_DELETE_LAYOUT, [ $this, 'delete_layout_from_service' ] );
	}

	/**
	 * Invalidates the Maps and Layouts caches.
	 *
	 * @since TBD
	 *
	 * @return void The function does not return a value but will echo the JSON response.
	 */
	public function invalidate_maps_layouts_cache(): void {
		if ( ! check_ajax_referer( self::NONCE_ACTION, '_ajax_nonce', false ) ) {
			wp_send_json_error(
				[
					'error' => 'Nonce verification failed',
				],
				403
			);

			return;
		}

		if ( ! ( Layouts::invalidate_cache() ) ) {
			wp_send_json_error( [ 'error' => 'Failed to invalidate the layouts cache.' ], 500 );
		}

		if ( ! ( Maps::invalidate_cache() ) ) {
			wp_send_json_error( [ 'error' => 'Failed to invalidate the maps layouts cache.' ], 500 );
		}


		wp_send_json_success();
	}

	/**
	 * Invalidates the Layouts cache.
	 *
	 * @since TBD
	 *
	 * @return void The function does not return a value but will echo the JSON response.
	 */
	public function invalidate_layouts_cache(): void {
		if ( ! check_ajax_referer( self::NONCE_ACTION, '_ajax_nonce', false ) ) {
			wp_send_json_error(
				[
					'error' => 'Nonce verification failed',
				],
				403
			);

			return;
		}

		if ( ! ( Layouts::invalidate_cache() ) ) {
			wp_send_json_error( [ 'error' => 'Failed to invalidate the layouts cache.' ], 500 );
		}

		wp_send_json_success();
	}
	
	/**
	 * Deletes a map from the service.
	 *
	 * @since TBD
	 *
	 * @return void The function does not return a value but will send the JSON response.
	 */
	public function delete_map_from_service(): void {
		if ( ! check_ajax_referer( self::NONCE_ACTION, '_ajax_nonce', false ) ) {
			wp_send_json_error(
				[
					'error' => __( 'Nonce verification failed', 'event-tickets' ),
				],
				403
			);
			
			return;
		}
		
		$map_id = tribe_get_request_var( 'mapId' );
		
		if ( tribe( Service::class )->delete_map( $map_id ) ) {
			wp_send_json_success();
		}
		
		wp_send_json_error( [ 'error' => __( 'Failed to delete the map.', 'event-tickets' ) ], 500 );
	}
	
	/**
	 * Deletes a layout from the service.
	 *
	 * @since TBD
	 *
	 * @return void The function does not return a value but will send the JSON response.
	 */
	public function delete_layout_from_service(): void {
		if ( ! check_ajax_referer( self::NONCE_ACTION, '_ajax_nonce', false ) ) {
			wp_send_json_error(
				[
					'error' => __( 'Nonce verification failed', 'event-tickets' ),
				],
				403
			);
			
			return;
		}
		
		$layout_id = tribe_get_request_var( 'layoutId' );
		$map_id    = tribe_get_request_var( 'mapId' );
		
		if ( tribe( Service::class )->delete_layout( $layout_id, $map_id ) ) {
			wp_send_json_success();
		}
		
		wp_send_json_error( [ 'error' => __( 'Failed to delete the layout.', 'event-tickets' ) ], 500 );
	}
}
