<?php
/**
 * Handles the AJAX requests for the Seating feature.
 *
 * @since   5.16.0
 *
 * @package TEC\Tickets\Seating\Admin;
 */

namespace TEC\Tickets\Seating\Admin;

use TEC\Common\Contracts\Container;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Asset;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Seating\Admin;
use TEC\Tickets\Seating\Admin\Tabs\Layout_Edit;
use TEC\Tickets\Seating\Ajax_Methods;
use TEC\Tickets\Seating\Commerce\Controller;
use TEC\Tickets\Seating\Logging;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Service\Layouts;
use TEC\Tickets\Seating\Service\Maps;
use TEC\Tickets\Seating\Service\Reservations;
use TEC\Tickets\Seating\Service\Seat_Types;
use TEC\Tickets\Seating\Tables\Sessions;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Main as Tickets_Main;
use Tribe__Tickets__Global_Stock as Global_Stock;

/**
 * Class Ajax.
 *
 * @since   5.16.0
 *
 * @package TEC\Tickets\Seating\Admin;
 */
class Ajax extends Controller_Contract {
	use Ajax_Methods;
	use Logging;

	/**
	 * The nonce action.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const NONCE_ACTION = 'tec-tickets-seating-service-ajax';

	/**
	 * The action to get the seat types for a given layout ID.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID = 'tec_tickets_seating_get_seat_types_by_layout_id';

	/**
	 * The action to invalidate the maps and layouts cache.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE = 'tec_tickets_seating_service_invalidate_maps_layouts_cache';

	/**
	 * The action to invalidate the layouts cache.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const ACTION_INVALIDATE_LAYOUTS_CACHE = 'tec_tickets_seating_service_invalidate_layouts_cache';

	/**
	 * The action to delete a map.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const ACTION_DELETE_MAP = 'tec_tickets_seating_service_delete_map';

	/**
	 * The action to delete a layout.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const ACTION_DELETE_LAYOUT = 'tec_tickets_seating_service_delete_layout';

	/**
	 * The action to duplicate a layout.
	 *
	 * @since 5.17.0
	 *
	 * @var string
	 */
	public const ACTION_DUPLICATE_LAYOUT = 'tec_tickets_seating_service_duplicate_layout';

	/**
	 * The action to add a layout.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const ACTION_ADD_NEW_LAYOUT = 'tec_tickets_seating_service_add_layout';

	/**
	 * The action to push the reservations to the backend from the seat-selection frontend.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const ACTION_POST_RESERVATIONS = 'tec_tickets_seating_post_reservations';

	/**
	 * The action to remove the reservations from the backend from the seat-selection frontend.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const ACTION_CLEAR_RESERVATIONS = 'tec_tickets_seating_clear_reservations';

	/**
	 * The action to delete reservations.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const ACTION_DELETE_RESERVATIONS = 'tec_tickets_seating_delete_reservations';

	/**
	 * The action to fetch attendees.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const ACTION_FETCH_ATTENDEES = 'tec_tickets_seating_fetch_attendees';

	/**
	 * The action to update a set of seat types.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const ACTION_SEAT_TYPES_UPDATED = 'tec_tickets_seating_seat_types_updated';

	/**
	 * The action to handle seat type deletion.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const ACTION_SEAT_TYPE_DELETED = 'tec_tickets_seating_seat_type_deleted';

	/**
	 * The action to update a set of reservations following a seat type update.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES = 'tec_tickets_seating_reservations_updated_from_seat_types';

	/**
	 * The action to create a new reservation from the Seats Report page.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const ACTION_RESERVATION_CREATED = 'tec_tickets_seating_reservation_created';

	/**
	 * The action to update an existing reservation from the Seats Report page.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const ACTION_RESERVATION_UPDATED = 'tec_tickets_seating_reservation_updated';

	/**
	 * The action to update the layout for an event.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const ACTION_EVENT_LAYOUT_UPDATED = 'tec_tickets_seating_event_layout_updated';

	/**
	 * The action to remove the post layout.
	 *
	 * @since 5.18.0
	 *
	 * @var string
	 */
	public const ACTION_EVENT_LAYOUT_REMOVE = 'tec_tickets_seating_event_layout_removal';

	/**
	 * A reference to the Seat Types service object.
	 *
	 * @since 5.16.0
	 *
	 * @var Seat_Types
	 */
	private Seat_Types $seat_types;

	/**
	 * A reference to the Sessions table object.
	 *
	 * @since 5.16.0
	 *
	 * @var Sessions
	 */
	private Sessions $sessions;

	/**
	 * A reference to the Reservations service object.
	 *
	 * @since 5.16.0
	 *
	 * @var Reservations
	 */
	private Reservations $reservations;

	/**
	 * A reference to the Maps service object.
	 *
	 * @since 5.16.0
	 *
	 * @var Maps
	 */
	private Maps $maps;

	/**
	 * A reference to the Layouts service object.
	 *
	 * @since 5.16.0
	 *
	 * @var Layouts
	 */
	private Layouts $layouts;

	/**
	 * Ajax constructor.
	 *
	 * @since 5.16.0
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
	 * @since 5.16.0
	 */
	protected function do_register(): void {
		$this->register_assets();
		add_action( 'wp_ajax_' . self::ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID, [ $this, 'fetch_seat_types_by_layout_id' ] );
		add_action(
			'wp_ajax_' . self::ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE,
			[ $this, 'invalidate_maps_layouts_cache' ]
		);
		add_action( 'wp_ajax_' . self::ACTION_INVALIDATE_LAYOUTS_CACHE, [ $this, 'invalidate_layouts_cache' ] );
		add_action( 'wp_ajax_' . self::ACTION_DELETE_MAP, [ $this, 'delete_map_from_service' ] );
		add_action( 'wp_ajax_' . self::ACTION_DELETE_LAYOUT, [ $this, 'delete_layout_from_service' ] );
		add_action( 'wp_ajax_' . self::ACTION_ADD_NEW_LAYOUT, [ $this, 'add_new_layout_to_service' ] );
		add_action( 'wp_ajax_' . self::ACTION_DUPLICATE_LAYOUT, [ $this, 'duplicate_layout_in_service' ] );
		add_action( 'wp_ajax_' . self::ACTION_POST_RESERVATIONS, [ $this, 'update_reservations' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION_POST_RESERVATIONS, [ $this, 'update_reservations' ] );
		add_action( 'wp_ajax_' . self::ACTION_CLEAR_RESERVATIONS, [ $this, 'clear_reservations' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION_CLEAR_RESERVATIONS, [ $this, 'clear_reservations' ] );
		add_action( 'wp_ajax_' . self::ACTION_DELETE_RESERVATIONS, [ $this, 'delete_reservations' ] );
		add_action( 'wp_ajax_' . self::ACTION_SEAT_TYPES_UPDATED, [ $this, 'update_seat_types' ] );
		add_action(
			'wp_ajax_' . self::ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES,
			[ $this, 'update_reservations_from_seat_types' ]
		);
		add_action( 'wp_ajax_' . self::ACTION_SEAT_TYPE_DELETED, [ $this, 'handle_seat_type_deleted' ] );
		add_action( 'wp_ajax_' . self::ACTION_EVENT_LAYOUT_UPDATED, [ $this, 'update_event_layout' ] );
		add_action( 'wp_ajax_' . self::ACTION_EVENT_LAYOUT_REMOVE, [ $this, 'remove_event_layout' ] );

		add_action( 'tec_tickets_seating_session_interrupt', [ $this, 'clear_commerce_cart_cookie' ] );
	}

	/**
	 * Unsubscribes the controller from WordPress hooks.
	 *
	 * @since 5.16.0
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
		remove_action( 'wp_ajax_' . self::ACTION_ADD_NEW_LAYOUT, [ $this, 'add_new_layout_to_service' ] );
		remove_action( 'wp_ajax_' . self::ACTION_DUPLICATE_LAYOUT, [ $this, 'duplicate_layout_in_service' ] );
		remove_action( 'wp_ajax_' . self::ACTION_POST_RESERVATIONS, [ $this, 'update_reservations' ] );
		remove_action( 'wp_ajax_nopriv_' . self::ACTION_POST_RESERVATIONS, [ $this, 'update_reservations' ] );
		remove_action( 'wp_ajax_' . self::ACTION_CLEAR_RESERVATIONS, [ $this, 'clear_reservations' ] );
		remove_action( 'wp_ajax_nopriv_' . self::ACTION_CLEAR_RESERVATIONS, [ $this, 'clear_reservations' ] );
		remove_action( 'tec_tickets_seating_session_interrupt', [ $this, 'clear_commerce_cart_cookie' ] );
		remove_action( 'wp_ajax_' . self::ACTION_DELETE_RESERVATIONS, [ $this, 'delete_reservations' ] );
		remove_action( 'wp_ajax_' . self::ACTION_SEAT_TYPES_UPDATED, [ $this, 'update_seat_types' ] );
		remove_action(
			'wp_ajax_' . self::ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES,
			[ $this, 'update_reservations_from_seat_types' ]
		);

		remove_action( 'wp_ajax_' . self::ACTION_SEAT_TYPE_DELETED, [ $this, 'handle_seat_type_deleted' ] );
		remove_action( 'wp_ajax_' . self::ACTION_EVENT_LAYOUT_UPDATED, [ $this, 'update_event_layout' ] );
		remove_action( 'wp_ajax_' . self::ACTION_EVENT_LAYOUT_REMOVE, [ $this, 'remove_event_layout' ] );
	}

	/**
	 * Returns the Ajax data for the Seating feature.
	 *
	 * @since 5.16.0
	 *
	 * @return array<string,string> The Ajax data for the Seating feature.
	 */
	public function get_ajax_data(): array {
		return [
			'ajaxUrl'                                     => admin_url( 'admin-ajax.php' ),
			'ajaxNonce'                                   => wp_create_nonce( self::NONCE_ACTION ),
			'ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE'        => self::ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE,
			'ACTION_INVALIDATE_LAYOUTS_CACHE'             => self::ACTION_INVALIDATE_LAYOUTS_CACHE,
			'ACTION_DELETE_MAP'                           => self::ACTION_DELETE_MAP,
			'ACTION_DELETE_LAYOUT'                        => self::ACTION_DELETE_LAYOUT,
			'ACTION_ADD_NEW_LAYOUT'                       => self::ACTION_ADD_NEW_LAYOUT,
			'ACTION_DUPLICATE_LAYOUT'                     => self::ACTION_DUPLICATE_LAYOUT,
			'ACTION_POST_RESERVATIONS'                    => self::ACTION_POST_RESERVATIONS,
			'ACTION_CLEAR_RESERVATIONS'                   => self::ACTION_CLEAR_RESERVATIONS,
			'ACTION_DELETE_RESERVATIONS'                  => self::ACTION_DELETE_RESERVATIONS,
			'ACTION_FETCH_ATTENDEES'                      => self::ACTION_FETCH_ATTENDEES,
			'ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID'          => self::ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID,
			'ACTION_SEAT_TYPES_UPDATED'                   => self::ACTION_SEAT_TYPES_UPDATED,
			'ACTION_SEAT_TYPE_DELETED'                    => self::ACTION_SEAT_TYPE_DELETED,
			'ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES' => self::ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES,
			'ACTION_RESERVATION_CREATED'                  => self::ACTION_RESERVATION_CREATED,
			'ACTION_RESERVATION_UPDATED'                  => self::ACTION_RESERVATION_UPDATED,
			'ACTION_EVENT_LAYOUT_UPDATED'                 => self::ACTION_EVENT_LAYOUT_UPDATED,
			'ACTION_REMOVE_EVENT_LAYOUT'                  => self::ACTION_EVENT_LAYOUT_REMOVE,
		];
	}

	/**
	 * Registers the assets used by the AJAX component.
	 *
	 * @since 5.16.0
	 */
	private function register_assets(): void {
		Asset::add(
			'tec-tickets-seating-ajax',
			'ajax.js',
			Tickets_Main::VERSION
		)
			->add_to_group_path( 'tec-seating' )
			->add_localize_script( 'tec.tickets.seating.ajax', [ $this, 'get_ajax_data' ] )
			->add_to_group( 'tec-tickets-seating' )
			->register();
	}

	/**
	 * Returns the seat types in option format for the given layout IDs.
	 *
	 * @since 5.16.0
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
	 * @since 5.16.0
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
	 * @since 5.16.0
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
	 * @since 5.16.0
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
	 * @since 5.16.0
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
	 * Adds a new layout to the service.
	 *
	 * @since 5.16.0
	 *
	 * @return void The function does not return a value but will send the JSON response.
	 */
	public function add_new_layout_to_service(): void {
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

		$layout_id = $this->layouts->add( $map_id );

		if ( ! empty( $layout_id ) ) {
			$edit_url = add_query_arg(
				[
					'page'     => Admin::get_menu_slug(),
					'tab'      => Layout_Edit::get_id(),
					'layoutId' => $layout_id,
					'isNew'    => 1,
				],
				admin_url( 'admin.php' )
			);

			wp_send_json_success( $edit_url );
			return;
		}

		wp_send_json_error( [ 'error' => __( 'Failed to Add new layout.', 'event-tickets' ) ], 500 );
	}

	/**
	 * Duplicates a layout in the service.
	 *
	 * @since 5.17.0
	 *
	 * @return void The function does not return a value but will send the JSON response.
	 */
	public function duplicate_layout_in_service(): void {
		if ( ! $this->check_current_ajax_user_can( 'manage_options' ) ) {
			return;
		}

		$layout_id = (string) tribe_get_request_var( 'layoutId' );

		if ( empty( $layout_id ) ) {
			wp_send_json_error(
				[
					'error' => __( 'No layout ID provided for duplication', 'event-tickets' ),
				],
				400
			);

			return;
		}

		$duplicated_layout_id = $this->layouts->duplicate_layout( $layout_id );

		if ( empty( $duplicated_layout_id ) ) {
			wp_send_json_error( [ 'error' => __( 'Failed to duplicate layout.', 'event-tickets' ) ], 500 );
			return;
		}

		$edit_url = add_query_arg(
			[
				'page'     => Admin::get_menu_slug(),
				'tab'      => Layout_Edit::get_id(),
				'layoutId' => $duplicated_layout_id,
				'isNew'    => 1,
			],
			admin_url( 'admin.php' )
		);

		wp_send_json_success( $edit_url );
	}

	/**
	 * Handles the request to update reservations on the Service.
	 *
	 * @since 5.16.0
	 *
	 * @return void The JSON response is sent to the client.
	 */
	public function update_reservations() {
		if ( ! $this->check_current_ajax_user_can( 'exist' ) ) {
			return;
		}

		$post_id = (int) tribe_get_request_var( 'postId', 0 );

		if ( empty( $post_id ) ) {
			wp_send_json_error(
				[
					'error' => 'No post ID provided',
				],
				400
			);

			return;
		}

		$body    = $this->get_request_body();
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

			if ( ! is_array( $ticket_reservations ) ) {
				wp_send_json_error(
					[
						'error' => 'Reservation data is not in correct format',
					],
					400
				);

				return;
			}

			foreach ( $ticket_reservations as $reservation ) {
				if ( ! (
					is_array( $reservation )
					&& isset( $reservation['reservationId'], $reservation['seatTypeId'], $reservation['seatLabel'] )
				) ) {
					wp_send_json_error(
						[
							'error' => 'Reservation data is not in correct format',
						],
						400
					);

					return;
				}

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
	 * @since 5.16.0
	 *
	 * @return void The JSON response is sent to the client.
	 */
	public function clear_reservations(): void {
		if ( ! $this->check_current_ajax_user_can( 'exist' ) ) {
			return;
		}

		$post_id = (int) tribe_get_request_var( 'postId', 0 );
		$token   = tribe_get_request_var( 'token' );

		if ( ! ( $post_id && $token && is_string( $token ) ) ) {
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
			&& $this->sessions->delete_token_session( $token )
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
	 * @since 5.16.0
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

	/**
	 * Handles the request to delete reservations from attendees.
	 *
	 * @since 5.16.0
	 *
	 * @return void The function does not return a value but will send the JSON response.
	 */
	public function delete_reservations(): void {
		if ( ! $this->check_current_ajax_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[
					'error' => 'Nonce verification failed',
				],
				403
			);

			return;
		}

		$body = $this->get_request_body();
		$json = json_decode( $body, true );

		if ( ! ( is_array( $json ) ) ) {
			wp_send_json_error(
				[
					'error' => 'Invalid request body',
				],
				400
			);

			return;
		}

		$reservation_ids = array_map( 'strval', array_filter( $json ) );

		if ( empty( $reservation_ids ) ) {
			wp_send_json_error(
				[
					'error' => 'Invalid request body',
				],
				400
			);

			return;
		}

		try {
			$deleted = $this->reservations->delete_reservations_from_attendees( $reservation_ids );
		} catch ( \Exception $e ) {
			$this->log_error(
				'Failed to delete reservations from attendees.',
				[
					'source' => __METHOD__,
					'error'  => $e->getMessage(),
				]
			);

			wp_send_json_error(
				[
					'error' => 'Failed to delete reservations from attendees',
				],
				500
			);
		}

		wp_send_json_success( [ 'numberDeleted' => $deleted ] );
	}

	/**
	 * Handles the update of seat types from the service.
	 *
	 * @since 5.16.0
	 *
	 * @return void The function does not return a value but will echo the JSON response.
	 */
	public function update_seat_types(): void {
		if ( ! $this->check_current_ajax_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[
					'error' => 'Nonce verification failed',
				],
				403
			);

			return;
		}

		$body    = $this->get_request_body();
		$decoded = json_decode( $body, true );

		if ( ! ( $decoded && is_array( $decoded ) ) ) {
			wp_send_json_error(
				[
					'error' => 'Invalid request body',
				],
				400
			);

			return;
		}

		$valid = array_filter(
			$decoded,
			static function ( $updated_seat_type ) {
				return is_array( $updated_seat_type )
						&& isset(
							$updated_seat_type['id'],
							$updated_seat_type['name'],
							$updated_seat_type['mapId'],
							$updated_seat_type['layoutId'],
							$updated_seat_type['description'],
							$updated_seat_type['seatsCount'],
						);
			}
		);

		if ( empty( $valid ) ) {
			wp_send_json_error(
				[
					'error' => 'Invalid request body',
				],
				400
			);

			return;
		}

		$updated_seat_types = $this->seat_types->update_from_service( $valid );

		if ( false === $updated_seat_types ) {
			wp_send_json_error(
				[
					'error' => 'Failed to update the seat types from the service.',
				],
				500
			);

			return;
		}

		$seat_type_to_capacity_map = array_reduce(
			$valid,
			static function ( array $carry, array $seat_type ): array {
				return $carry + [ $seat_type['id'] => $seat_type['seatsCount'] ];
			},
			[]
		);

		$updated_tickets = $this->seat_types->update_tickets_capacity( $seat_type_to_capacity_map );

		$layout_to_seats_map = array_reduce(
			$valid,
			static function ( array $carry, array $seat_type ): array {
				$layout_id = $seat_type['layoutId'];
				if ( isset( $carry[ $layout_id ] ) ) {
					$carry[ $layout_id ] += $seat_type['seatsCount'];
				} else {
					$carry[ $layout_id ] = $seat_type['seatsCount'];
				}

				return $carry;
			},
			[]
		);

		$updated_posts = $this->layouts->update_posts_capacity( $layout_to_seats_map );

		wp_send_json_success(
			[
				'updatedSeatTypes' => $updated_seat_types,
				'updatedTickets'   => $updated_tickets,
				'updatedPosts'     => $updated_posts,
			]
		);
	}

	/**
	 * Updates the seat type of Attendees following based on their reservation.
	 *
	 * @since 5.16.0
	 *
	 * @return void The function does not return a value but will send the JSON response.
	 */
	public function update_reservations_from_seat_types(): void {
		if ( ! $this->check_current_ajax_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[
					'error' => 'Nonce verification failed',
				],
				403
			);

			return;
		}

		$body    = $this->get_request_body();
		$decoded = json_decode( $body, true );

		if ( ! ( $decoded && is_array( $decoded ) ) ) {
			wp_send_json_error(
				[
					'error' => 'Invalid request body',
				],
				400
			);

			return;
		}

		$valid = array_filter( $decoded, 'is_array' );

		if ( empty( $valid ) ) {
			wp_send_json_error(
				[
					'error' => 'Invalid request body',
				],
				400
			);

			return;
		}

		$updated = $this->reservations->update_attendees_seat_type( $valid );

		wp_send_json_success( [ 'updatedAttendees' => $updated ] );
	}

	/**
	 * Handles the deletion of a seat type by transferring existing reservations to new seat type.
	 *
	 * @since 5.16.0
	 *
	 * @return void The function does not return a value but will send the JSON response.
	 */
	public function handle_seat_type_deleted() {
		if ( ! $this->check_current_ajax_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[
					'error' => 'Nonce verification failed',
				],
				403
			);

			return;
		}

		$decoded = $this->get_request_json();

		$old_seat_type_id = $decoded['deletedId'] ?? null;
		$new_seat_type    = $decoded['transferTo'] ?? null;

		if ( empty( $old_seat_type_id )
			|| ! is_array( $new_seat_type )
			|| ! isset(
				$new_seat_type['id'],
				$new_seat_type['name'],
				$new_seat_type['mapId'],
				$new_seat_type['layoutId'],
				$new_seat_type['description'],
				$new_seat_type['seatsCount']
			) ) {
			wp_send_json_error(
				[
					'error' => 'Invalid request body',
				],
				400
			);

			return;
		}

		/*
		* Update all Tickets and Attendees with old seat type meta to new seat type.
		* Note this updated is NOT done based on currently active Tickets and Attendee types
		* to include, in the update, those that are not currently active.
		*/

		global $wpdb;

		$ticket_post_types = implode( ', ', array_map( static fn( $v ) => "'" . esc_sql( $v ) . "'", array_values( tribe_tickets()->ticket_types() ) ) );

		try {
			$original_seat_types_tickets = array_map(
				'intval',
				DB::get_col(
					DB::prepare(
						'SELECT DISTINCT(pm.post_id) FROM %i pm JOIN %i p ON p.ID=pm.post_id WHERE pm.meta_key = %s AND pm.meta_value = %s AND p.post_type IN (' . $ticket_post_types . ')',
						$wpdb->postmeta,
						$wpdb->posts,
						Meta::META_KEY_SEAT_TYPE,
						$new_seat_type['id']
					)
				)
			);

			$updated_seat_types_meta = DB::query(
				DB::prepare(
					'UPDATE %i SET meta_value = %s WHERE meta_key = %s AND meta_value = %s',
					$wpdb->postmeta,
					$new_seat_type['id'],
					Meta::META_KEY_SEAT_TYPE,
					$old_seat_type_id,
				),
			);
		} catch ( \Exception $exception ) {
			$this->log_error(
				'Failed to update seat type meta.',
				[
					'source' => __METHOD__,
					'error'  => $exception->getMessage(),
				]
			);
		}

		$updated_seat_types = $this->seat_types->update_from_service( [ $new_seat_type ] );
		$updated_tickets    = $this->seat_types->update_tickets_with_calculated_stock_and_capacity( $new_seat_type['id'], $new_seat_type['seatsCount'], $original_seat_types_tickets );

		wp_send_json_success(
			[
				'updatedSeatTypes' => $updated_seat_types,
				'updatedTickets'   => $updated_tickets,
				'updatedMeta'      => $updated_seat_types_meta,
			]
		);
	}

	/**
	 * Updates the layout of an event.
	 *
	 * @since 5.16.0
	 *
	 * @return void The function does not return a value but will echo the JSON response.
	 */
	public function update_event_layout() {
		$post_id   = tribe_get_request_var( 'postId' );
		$layout_id = tribe_get_request_var( 'newLayout' );

		if ( empty( $layout_id ) || empty( $post_id ) ) {
			wp_send_json_error(
				[
					'error' => 'No layout ID or post ID provided',
				],
				400
			);

			return;
		}

		if ( ! $this->check_current_ajax_user_can( 'edit_posts', $post_id ) ) {
			wp_send_json_error(
				[
					'error' => 'User has no permission.',
				],
				403
			);

			return;
		}

		$layout = DB::table( \TEC\Tickets\Seating\Tables\Layouts::table_name( false ) )->where( 'id', $layout_id )->get();

		if ( empty( $layout ) ) {
			wp_send_json_error(
				[
					'error' => 'Invalid layout ID',
				],
				400
			);

			return;
		}

		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler   = tribe( 'tickets.handler' );
		$capacity_meta_key = $tickets_handler->key_capacity;

		$primary_seat_type = $this->seat_types->get_primary_seat_type( $layout_id );

		if ( null === $primary_seat_type ) {
			wp_send_json_error(
				[
					'error' => __( 'No primary seat type found for the layout.', 'event-tickets' ),
				],
				400
			);

			return;
		}

		$new_seat_type_id  = $primary_seat_type->id;
		$new_seat_capacity = $primary_seat_type->seats;

		$updated_tickets   = 0;
		$updated_attendees = 0;

		// Get tickets by post id.
		$tickets = tribe_tickets()->where( 'event', $post_id )->get_ids( true );

		// We're handling the update of the ticket meta ourselves.
		remove_filter( 'update_post_metadata', [ tribe( Controller::class ), 'handle_ticket_meta_update' ], 10 );

		foreach ( $tickets as $ticket_id ) {
			$previous_capacity = get_post_meta( $ticket_id, $capacity_meta_key, true );
			$capacity_delta    = $new_seat_capacity - $previous_capacity;
			$previous_stock    = get_post_meta( $ticket_id, '_stock', true );
			$new_stock         = max( 0, $previous_stock + $capacity_delta );

			update_post_meta( $ticket_id, $capacity_meta_key, $new_seat_capacity );
			update_post_meta( $ticket_id, '_stock', $new_stock );
			update_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE, $new_seat_type_id );

			++$updated_tickets;
		}

		// Attendees by post id.
		$attendees = tribe_attendees()->where( 'event', $post_id )->get_ids( true );

		foreach ( $attendees as $attendee_id ) {
			update_post_meta( $attendee_id, Meta::META_KEY_SEAT_TYPE, $new_seat_type_id );
			update_post_meta( $attendee_id, Meta::META_KEY_ATTENDEE_SEAT_LABEL, '' );

			++$updated_attendees;
		}

		// Finally update post data.
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, $layout_id );
		update_post_meta( $post_id, $capacity_meta_key, $layout->seats );
		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_LEVEL, $layout->seats );

		add_filter( 'update_post_metadata', [ tribe( Controller::class ), 'handle_ticket_meta_update' ], 10, 4 );

		wp_send_json_success(
			[
				'updatedTickets'   => $updated_tickets,
				'updatedAttendees' => $updated_attendees,
			]
		);
	}

	/**
	 * Removes the layout from an event.
	 *
	 * @since 5.18.0
	 *
	 * @return void The function does not return a value but will echo the JSON response.
	 */
	public function remove_event_layout() {
		$post_id = tribe_get_request_var( 'postId' );

		if ( empty( $post_id ) ) {
			wp_send_json_error(
				[
					'error' => __( 'No post ID provided', 'event-tickets' ),
				],
				400
			);

			return;
		}

		if ( ! $this->check_current_ajax_user_can( 'edit_posts', $post_id ) ) {
			wp_send_json_error(
				[
					'error' => __( 'User has no permission.', 'event-tickets' ),
				],
				403
			);

			return;
		}

		$layout_id = get_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, true );

		if ( empty( $layout_id ) ) {
			wp_send_json_error(
				[
					'error' => __( 'Layout not found.', 'event-tickets' ),
				],
				403
			);

			return;
		}

		$updated_tickets   = 0;
		$updated_attendees = 0;

		// Get tickets by post id.
		$tickets = tribe_tickets()
					->where( 'event', $post_id )
					->where( 'meta_exists', Meta::META_KEY_SEAT_TYPE )
					->get_ids( true );

		foreach ( $tickets as $ticket_id ) {
			// Remove slr meta.
			delete_post_meta( $ticket_id, Meta::META_KEY_ENABLED );
			delete_post_meta( $ticket_id, Meta::META_KEY_LAYOUT_ID );
			delete_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE );

			// Switch ticket to own stock mode.
			update_post_meta( $ticket_id, Global_Stock::TICKET_STOCK_MODE, Global_Stock::OWN_STOCK_MODE );

			// Set ticket capacity to 1.
			tribe_tickets_delete_capacity( $ticket_id );
			tribe_tickets_update_capacity( $ticket_id, 1 );

			// Update ticket stock.
			update_post_meta( $ticket_id, '_stock', 1 );

			++$updated_tickets;
			clean_post_cache( $ticket_id );
		}

		// Attendees by post id.
		$attendees = tribe_attendees()
			->where( 'event', $post_id )
			->where( 'meta_equals', Meta::META_KEY_LAYOUT_ID, $layout_id )
			->get_ids( true );

		foreach ( $attendees as $attendee_id ) {
			delete_post_meta( $attendee_id, Meta::META_KEY_SEAT_TYPE );
			delete_post_meta( $attendee_id, Meta::META_KEY_ATTENDEE_SEAT_LABEL );
			delete_post_meta( $attendee_id, Meta::META_KEY_LAYOUT_ID );

			clean_post_cache( $attendee_id );
			++$updated_attendees;
		}

		// Finally update post data.
		delete_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID );
		delete_post_meta( $post_id, Meta::META_KEY_ENABLED );

		// Remove global stock.
		tribe_tickets_delete_capacity( $post_id );

		clean_post_cache( $post_id );

		wp_send_json_success(
			[
				'updatedTickets'   => $updated_tickets,
				'updatedAttendees' => $updated_attendees,
			]
		);
	}
}
