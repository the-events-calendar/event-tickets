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
use TEC\Tickets\Seating\Service\Seat_Types;

/**
 * Class Ajax.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Admin;
 */
class Ajax extends Controller_Contract {
	/**
	 * Ajax constructor.
	 *
	 * since TBD
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
					'action' => 'seat_types_by_layout_id',
					'_ajax_nonce'  => wp_create_nonce( 'seat_types_by_layout_id' ),
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
			wp_send_json_error( [
				                    'error' => 'Nonce verification failed',
			                    ] );

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
	}
}