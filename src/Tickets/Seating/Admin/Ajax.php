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
use TEC\Tickets\Seating\Service\Reservations;
use TEC\Tickets\Seating\Service\Seat_Types;
use TEC\Tickets\Seating\Tables\Sessions;

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
	const ACTION_REMOVE_RESERVATIONS = 'tec_tickets_seating_remove_reservations';

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
	 * Ajax constructor.
	 *
	 * @since TBD
	 *
	 * @param Container    $container  A reference to the DI container object.
	 * @param Seat_Types   $seat_types A reference to the Seat Types service object.
	 * @param Sessions     $sessions    A reference to the Sessions table object.
	 * @param Reservations $reservations A reference to the Reservations service object.
	 */
	public function __construct(
		Container $container,
		Seat_Types $seat_types,
		Sessions $sessions,
		Reservations $reservations
	) {
		parent::__construct( $container );
		$this->seat_types   = $seat_types;
		$this->sessions     = $sessions;
		$this->reservations = $reservations;
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
		add_action( 'wp_ajax_' . self::ACTION_POST_RESERVATIONS, [ $this, 'update_reservations' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION_POST_RESERVATIONS, [ $this, 'update_reservations' ] );
		add_action( 'wp_ajax_' . self::ACTION_REMOVE_RESERVATIONS, [ $this, 'remove_reservations' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION_REMOVE_RESERVATIONS, [ $this, 'remove_reservations' ] );
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
		remove_action( 'wp_ajax_' . self::ACTION_POST_RESERVATIONS, [ $this, 'update_reservations' ] );
		remove_action( 'wp_ajax_nopriv_' . self::ACTION_POST_RESERVATIONS, [ $this, 'update_reservations' ] );
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
					'error' => __( 'Nonce verification failed', 'event-tickets' ),
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
		) ) {
			wp_send_json_error(
				[
					'error' => __('Invalid request body', 'event-tickets' ),
				],
				400
			);
		}

		$token        = $decoded['token'];
		$reservations = $decoded['reservations'];

		if ( ! ( $this->sessions->update_reservations( $token, $reservations ) ) ) {
			wp_send_json_error(
				[
					'error' => __( 'Failed to update the reservations', 'event-tickets' ),
				],
				500
			);
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
	public function remove_reservations() {
		if ( ! check_ajax_referer( self::NONCE_ACTION, '_ajax_nonce', false ) ) {
			wp_send_json_error(
				[
					'error' =>__( 'Nonce verification failed', 'event-tickets' ),
				],
				403
			);

			return;
		}

		$token   = tribe_get_request_var( 'token' );
		$post_id = tribe_get_request_var( 'postId' );

		$reservations_cancelled = $this->reservations->cancel(
			$post_id,
			$this->sessions->get_reservations_for_token( $token )
		);
		$token_session_deleted  = $this->sessions->delete_token_session( $token );

		if ( ! ( $reservations_cancelled && $token_session_deleted ) ) {
			wp_send_json_error(
				[
					'error' => __( 'Failed to remove the reservations', 'event-tickets' ),
				],
				500
			);
		}

		wp_send_json_success();
	}
}
