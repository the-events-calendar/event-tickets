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
use TEC\Common\StellarWP\Assets\Asset;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Seating\Ajax_Checks;
use TEC\Tickets\Seating\Built_Assets;
use TEC\Tickets\Seating\Service\Layouts;
use TEC\Tickets\Seating\Service\Maps;
use TEC\Tickets\Seating\Service\Reservations;
use TEC\Tickets\Seating\Service\Seat_Types;
use TEC\Tickets\Seating\Tables\Sessions;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Main as Tickets_Main;

/**
 * Class Ajax.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Admin;
 */
class Ajax extends Controller_Contract {
	use Ajax_Checks;
	use Built_Assets;

	/**
	 * The nonce action.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const NONCE_ACTION = 'tec-tickets-seating-service-ajax';

	/**
	 * The action to get the seat types for a given layout ID.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID = 'tec_tickets_seating_get_seat_types_by_layout_id';

	/**
	 * The action to invalidate the maps and layouts cache.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE = 'tec_tickets_seating_service_invalidate_maps_layouts_cache';

	/**
	 * The action to invalidate the layouts cache.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const ACTION_INVALIDATE_LAYOUTS_CACHE = 'tec_tickets_seating_service_invalidate_layouts_cache';

	/**
	 * The action to delete a map.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const ACTION_DELETE_MAP = 'tec_tickets_seating_service_delete_map';

	/**
	 * The action to delete a layout.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const ACTION_DELETE_LAYOUT = 'tec_tickets_seating_service_delete_layout';

	/**
	 * The action to push the reservations to the backend from the seat-selection frontend.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const ACTION_POST_RESERVATIONS = 'tec_tickets_seating_post_reservations';

	/**
	 * The action to remove the reservations from the backend from the seat-selection frontend.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const ACTION_CLEAR_RESERVATIONS = 'tec_tickets_seating_clear_reservations';

	/**
	 * The action to delete reservations.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const ACTION_DELETE_RESERVATIONS = 'tec_tickets_seating_delete_reservations';

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
	 * @param Container    $container    A reference to the DI container object.
	 * @param Seat_Types   $seat_types   A reference to the Seat Types service object.
	 * @param Sessions     $sessions     A reference to the Sessions table object.
	 * @param Reservations $reservations A reference to the Reservations service object.
	 * @param Maps         $maps         A reference to the Maps service object.
	 * @param Layouts      $layouts      A reference to the Layouts service object.
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
		$this->register_assets();
		add_action( 'wp_ajax_' . self::ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID, [ $this, 'fetch_seat_types_by_layout_id' ] );
		add_action( 'wp_ajax_' . self::ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE,
			[ $this, 'invalidate_maps_layouts_cache' ] );
		add_action( 'wp_ajax_' . self::ACTION_INVALIDATE_LAYOUTS_CACHE, [ $this, 'invalidate_layouts_cache' ] );
		add_action( 'wp_ajax_' . self::ACTION_DELETE_MAP, [ $this, 'delete_map_from_service' ] );
		add_action( 'wp_ajax_' . self::ACTION_DELETE_LAYOUT, [ $this, 'delete_layout_from_service' ] );
		add_action( 'wp_ajax_' . self::ACTION_POST_RESERVATIONS, [ $this, 'update_reservations' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION_POST_RESERVATIONS, [ $this, 'update_reservations' ] );
		add_action( 'wp_ajax_' . self::ACTION_CLEAR_RESERVATIONS, [ $this, 'clear_reservations' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION_CLEAR_RESERVATIONS, [ $this, 'clear_reservations' ] );

		add_action( 'tec_tickets_seating_session_interrupt', [ $this, 'clear_commerce_cart_cookie' ] );
	}

	/**
	 * Unsubscribes the controller from WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'wp_ajax_' . self::ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID, [ $this, 'fetch_seat_types_by_layout_id' ] );
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
	}

	/**
	 * Returns the Ajax data for the Seating feature.
	 *
	 * @since TBD
	 *
	 * @return array<string,string> The Ajax data for the Seating feature.
	 */
	public function get_ajax_data(): array {
		return [
			'ajaxUrl'                              => admin_url( 'admin-ajax.php' ),
			'ajaxNonce'                            => wp_create_nonce( self::NONCE_ACTION ),
			'ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE' => self::ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE,
			'ACTION_INVALIDATE_LAYOUTS_CACHE'      => self::ACTION_INVALIDATE_LAYOUTS_CACHE,
			'ACTION_DELETE_MAP'                    => self::ACTION_DELETE_MAP,
			'ACTION_DELETE_LAYOUT'                 => self::ACTION_DELETE_LAYOUT,
			'ACTION_POST_RESERVATIONS'             => self::ACTION_POST_RESERVATIONS,
			'ACTION_CLEAR_RESERVATIONS'            => self::ACTION_CLEAR_RESERVATIONS,
			'ACTION_DELETE_RESERVATIONS'           => self::ACTION_DELETE_RESERVATIONS,
		];
	}

	/**
	 * Registers the assets used by the AJAX component.
	 *
	 * @since TBD
	 */
	private function register_assets(): void {
		Asset::add(
			'tec-tickets-seating-ajax',
			$this->built_asset_url( 'ajax.js' ),
			Tickets_Main::VERSION
		)
		     ->add_localize_script( 'tec.tickets.seating.ajax', [ $this, 'get_ajax_data' ] )
		     ->add_to_group( 'tec-tickets-seating' )
		     ->register();
	}

	/**
	 * Returns the seat types in option format for the given layout IDs.
	 *
	 * @since TBD
	 *
	 * @return void The seat types in option format for the given layout IDs are returned as JSON.
	 */
	public function fetch_seat_types_by_layout_id(): void {
		if ( ! $this->check_current_ajax_user_can( 'edit_posts' ) ) {
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
		if ( ! $this->check_current_ajax_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! Layouts::invalidate_cache() ) {
			wp_send_json_error( [ 'error' => 'Failed to invalidate the layouts cache.' ], 500 );

			return;
		}

		if ( ! ( Maps::invalidate_cache() ) ) {
			wp_send_json_error( [ 'error' => 'Failed to invalidate the maps layouts cache.' ], 500 );

			return;
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
		if ( ! $this->check_current_ajax_user_can( 'manage_options' ) ) {
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
		if ( ! $this->check_current_ajax_user_can( 'manage_options' ) ) {
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
		if ( ! $this->check_current_ajax_user_can( 'manage_options' ) ) {
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
		$post_id = (int) tribe_get_request_var( 'postId', 0 );

		if ( ! $this->check_current_ajax_user_can( 'read_post', $post_id ) ) {
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

		$token             = $decoded['token'];
		$json_reservations = $decoded['reservations'];

		$reservations = [];
		foreach ( $json_reservations as $ticket_id => $ticket_reservations ) {
			$reservations[ $ticket_id ] = [];
			foreach ( $ticket_reservations as $reservation ) {
				$reservations[ $ticket_id ][] = [
					'reservation_id' => $reservation['reservationId'],
					'seat_type_id'   => $reservation['seatTypeId'],
					'seat_label'     => $reservation['seatLabel'],
				];
			}
		}

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
	public function clear_reservations(): void {
		$post_id = (int) tribe_get_request_var( 'postId', 0 );

		if ( ! $this->check_current_ajax_user_can( 'read_post', $post_id ) ) {
			return;
		}

		$token = tribe_get_request_var( 'token' );

		if ( ! ( $token && $post_id ) ) {
			wp_send_json_error(
				[
					'error' => __( 'Invalid request parameters', 'event-tickets' ),
				],
				400
			);

			return;
		}

		if ( ! (
			$this->reservations->cancel( $post_id, $this->sessions->get_reservation_uuids_for_token( $token ) )
			&& $this->sessions->clear_token_reservations( $token )
		) ) {
			wp_send_json_error(
				[
					'error' => __( 'Failed to clear the reservations', 'event-tickets' ),
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
