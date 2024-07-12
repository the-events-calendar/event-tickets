<?php
/**
 * Handles the AJAX requests for the Seating feature.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Seating\Admin;
 */

namespace TEC\Tickets\Seating\Admin;

use TEC\Common\Contracts\Container;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Seating\Service\Layouts;
use TEC\Tickets\Seating\Service\Maps;
use TEC\Tickets\Seating\Service\Seat_Types;
use TEC\Tickets\Seating\Tables\Sessions;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Seating\Service\Reservations;
use Tribe__Tickets__Tickets as Tickets;
use TEC\Tickets\Seating\Meta;
/**
 * Class Ajax.
 *
 * @since TBD
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
	 * The action to push the reservations to the backend from the seat-selection frontend.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ACTION_POST_RESERVATIONS = 'tec_tickets_seating_post_reservations';

	/**
	 * The action to remove the reservations from the backend from the seat-selection frontend.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ACTION_CLEAR_RESERVATIONS = 'tec_tickets_seating_clear_reservations';
	
	/**
	 * The action to fetch attendees.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ACTION_FETCH_ATTENDEES = 'tec_tickets_seating_fetch_attendees';

	/**
	 * A reference to the Seat Types service object.
	 *
	 * @since TBD
	 *
	 * @var Seat_Types
	 */
	private Seat_Types $seat_types;

	/**
	 * A reference to the Sessions table object.
	 *
	 * @since TBD
	 *
	 * @var Sessions
	 */
	private Sessions $sessions;

	/**
	 * A reference to the Reservations service object.
	 *
	 * @since TBD
	 *
	 * @var Reservations
	 */
	private Reservations $reservations;
	
	/**
	 * A reference to the Maps service object.
	 *
	 * @since TBD
	 *
	 * @var Maps
	 */
	private Maps $maps;
	
	/**
	 * A reference to the Layouts service object.
	 *
	 * @since TBD
	 *
	 * @var Layouts
	 */
	private Layouts $layouts;

	/**
	 * Ajax constructor.
	 *
	 * @since TBD
	 *
	 * @param Container    $container  A reference to the DI container object.
	 * @param Seat_Types   $seat_types A reference to the Seat Types service object.
	 * @param Sessions     $sessions    A reference to the Sessions table object.
	 * @param Reservations $reservations A reference to the Reservations service object.
	 * @param Maps         $maps        A reference to the Maps service object.
	 * @param Layouts      $layouts     A reference to the Layouts service object.
	 */
	public function __construct(
		Container $container,
		Seat_Types $seat_types,
		Sessions $sessions,
		Reservations $reservations,
		Maps $maps,
		Layouts $layouts
	) {
		parent::__construct( $container );
		$this->seat_types   = $seat_types;
		$this->sessions     = $sessions;
		$this->reservations = $reservations;
		$this->maps         = $maps;
		$this->layouts      = $layouts;
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
		add_action( 'wp_ajax_' . self::ACTION_POST_RESERVATIONS, [ $this, 'update_reservations' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION_POST_RESERVATIONS, [ $this, 'update_reservations' ] );
		add_action( 'wp_ajax_' . self::ACTION_CLEAR_RESERVATIONS, [ $this, 'clear_reservations' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION_CLEAR_RESERVATIONS, [ $this, 'clear_reservations' ] );
		add_action( 'tec_tickets_seating_session_interrupt', [ $this, 'clear_commerce_cart_cookie' ] );
		add_action( 'wp_ajax_' . self::ACTION_FETCH_ATTENDEES, [ $this, 'fetch_attendees_by_event' ] );
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
		remove_action( 'wp_ajax_' . self::ACTION_POST_RESERVATIONS, [ $this, 'update_reservations' ] );
		remove_action( 'wp_ajax_nopriv_' . self::ACTION_POST_RESERVATIONS, [ $this, 'update_reservations' ] );
		remove_action( 'wp_ajax_' . self::ACTION_CLEAR_RESERVATIONS, [ $this, 'clear_reservations' ] );
		remove_action( 'wp_ajax_nopriv_' . self::ACTION_CLEAR_RESERVATIONS, [ $this, 'clear_reservations' ] );
		remove_action( 'tec_tickets_seating_session_interrupt', [ $this, 'clear_commerce_cart_cookie' ] );
		remove_action( 'wp_ajax_' . self::ACTION_FETCH_ATTENDEES, [ $this, 'fetch_attendees_by_event' ] );
	}
	
	/**
	 * Fetch attendees by event.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function fetch_attendees_by_event(): void {
		if ( ! check_ajax_referer( self::NONCE_ACTION, '_ajax_nonce', false ) ) {
			wp_send_json_error(
				[
					'error' => __( 'Nonce verification failed', 'event-tickets' ),
				],
				403
			);
			
			return;
		}
		
		$event_id = (int) tribe_get_request_var( 'event_id' );
		
		if ( empty( $event_id ) ) {
			wp_send_json_error(
				[
					'error' => __( 'No event ID provided', 'event-tickets' ),
				],
				400
			);
			
			return;
		}
		
		$current_page = (int) tribe_get_request_var( 'page', 0 );
		$per_page     = (int) tribe_get_request_var( 'perPage', 50 );
		
		$total_count = tribe_attendees()->by( 'event', $event_id )->count();
		
		$args = [
			'page'               => $current_page,
			'per_page'           => $per_page,
			'return_total_found' => false,
			'order'              => 'DESC',
		];
		
		$data      = \Tribe__Tickets__Tickets::get_attendees_by_args( $args, $event_id );
		$formatted = [];
		
		foreach ( $data['attendees'] as $attendee ) {
			$id = (int) $attendee['attendee_id'];
			
			$formatted[ $id ] = [
				'id'            => $id,
				'name'          => $attendee['holder_name'],
				'purchaser'     => [
					'id'   => $attendee['purchaser_id'],
					'name' => $attendee['purchaser_name'],
				],
				'ticketId'      => $attendee['product_id'],
				'seatTypeId'    => get_post_meta( $id, Meta::META_KEY_SEAT_TYPE, true ),
				'seatLabel'     => get_post_meta( $id, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ),
				'reservationId' => get_post_meta( $id, Meta::META_KEY_RESERVATION_ID, true ),
			];
		}
		
		wp_send_json_success(
			[
				'attendees' => $formatted,
				'total'     => $total_count,
			] 
		);
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
		
		$map_id = (string) tribe_get_request_var( 'mapId' );
		
		if ( empty( $map_id ) ) {
			wp_send_json_error(
				[
					'error' => __( 'No map ID provided', 'event-tickets' ),
				],
				400
			);
			
			return;
		}
		
		if ( $this->maps->delete( $map_id ) ) {
			wp_send_json_success();
			return;
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
		
		$layout_id = (string) tribe_get_request_var( 'layoutId' );
		$map_id    = (string) tribe_get_request_var( 'mapId' );
		
		if ( empty( $layout_id ) || empty( $map_id ) ) {
			wp_send_json_error(
				[
					'error' => __( 'No layout ID or map ID provided', 'event-tickets' ),
				],
				400
			);
			
			return;
		}
		
		if ( $this->layouts->delete( $layout_id, $map_id ) ) {
			wp_send_json_success();
			return;
		}
		
		wp_send_json_error( [ 'error' => __( 'Failed to delete the layout.', 'event-tickets' ) ], 500 );
	}
	
	/**
	 * Handles the request to update reservations on the Service.
	 *
	 * @since TBD
	 *
	 * @return void The JSON response is sent to the client.
	 */
	public function update_reservations() {
		if ( ! check_ajax_referer( self::NONCE_ACTION, '_ajax_nonce', false ) ) {
			wp_send_json_error(
				[
					'error' => 'Nonce verification failed',
				],
				403
			);

			return;
		}

		if ( function_exists( 'wpcom_vip_file_get_contents' ) ) {
			$body = wpcom_vip_file_get_contents( 'php://input' );
		} else {
			// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsRemoteFile
			$body = trim( file_get_contents( 'php://input' ) );
		}

		$decoded = json_decode( $body, true );

		if ( ! (
			$decoded
			&& is_array( $decoded )
			&& isset( $decoded['token'], $decoded['reservations'] )
			&& is_string( $decoded['token'] )
			&& is_array( $decoded['reservations'] )
		) ) {
			wp_send_json_error(
				[
					'error' => 'Invalid request body',
				],
				400
			);

			return;
		}

		$token        = $decoded['token'];
		$reservations = $decoded['reservations'];

		if ( ! ( $this->sessions->update_reservations( $token, $reservations ) ) ) {
			wp_send_json_error(
				[
					'error' => 'Failed to update the reservations',
				],
				500
			);

			return;
		}

		wp_send_json_success();
	}

	/**
	 * Handles the request to remove reservations on the Service.
	 *
	 * @since TBD
	 *
	 * @return void The JSON response is sent to the client.
	 */
	public function clear_reservations() {
		if ( ! check_ajax_referer( self::NONCE_ACTION, '_ajax_nonce', false ) ) {
			wp_send_json_error(
				[
					'error' => 'Nonce verification failed',
				],
				403
			);

			return;
		}

		$token   = tribe_get_request_var( 'token' );
		$post_id = tribe_get_request_var( 'postId' );

		if ( ! ( $token && $post_id ) ) {
			wp_send_json_error(
				[
					'error' => 'Invalid request parameters',
				],
				400
			);

			return;
		}

		if ( ! (
			$this->reservations->cancel( $post_id, $this->sessions->get_reservations_for_token( $token ) )
			&& $this->sessions->clear_token_reservations( $token )
		) ) {
			wp_send_json_error(
				[
					'error' => 'Failed to clear the reservations',
				],
				500
			);

			return;
		}

		wp_send_json_success();
	}


	/**
	 * Removes the Tribe Commerce cart cookie when a seat selection session is interrupted.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID the session is being interrupted for.
	 *
	 * @return void The cookie is cleared.
	 */
	public function clear_commerce_cart_cookie( int $post_id ): void {
		if ( Tickets::get_event_ticket_provider( $post_id ) !== Module::class ) {
			return;
		}
		// Remove the `tec-tickets-commerce-cart` cookie.
		$cookie_name = Cart::$cart_hash_cookie_name;
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
		setcookie(
			$cookie_name,
			'',
			time() - DAY_IN_SECONDS,
			COOKIEPATH,
			COOKIE_DOMAIN,
			true,
			true
		);
		
		// phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
		unset( $_COOKIE[ $cookie_name ] );
	}
}
