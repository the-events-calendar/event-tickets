<?php
/**
 * ${CARET}
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

	public function __construct( Container $container, Seat_Types $seat_types ) {
		parent::__construct( $container );
		$this->seat_types = $seat_types;
	}

	public function unregister(): void {
		remove_action( 'wp_ajax_seat_types_by_layout_id', [ $this, 'fetch_seat_types_by_layout_id' ] );
	}

	public function get_urls(): array {
		return [
			'seatTypesByLayoutId' => add_query_arg(
				[
					'action' => 'seat_types_by_layout_id',
					'nonce'  => wp_create_nonce( 'seatTypesByLayoutId' ),
				],
				admin_url( 'admin-ajax.php' )
			),
		];
	}

	public function fetch_seat_types_by_layout_id(): void {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'seatTypesByLayoutId' ) ) {
			wp_send_json_error( [
				                    'error' => 'Nonce verification failed',
			                    ] );
		}

		$layout_id = tribe_get_request_var( 'layoutId' );

		$seat_types = $this->seat_types->get_in_option_format( $layout_id );

		wp_send_json_success( $seat_types );
	}

	protected function do_register(): void {
		add_action( 'wp_ajax_seat_types_by_layout_id', [ $this, 'fetch_seat_types_by_layout_id' ] );
	}
}