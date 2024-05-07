<?php

namespace TEC\Tickets\Seating\Admin;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Seating\Service\Seat_Types;
use TEC\Tickets\Seating\Tests\Integration\Seat_Types_Factory;
use Tribe\Tests\Traits\With_Uopz;

class Ajax_Test extends Controller_Test_Case {
	use SnapshotAssertions;
	use With_Uopz;
	use Seat_Types_Factory;

	protected string $controller_class = Ajax::class;

	/**
	 * It should return URLs
	 *
	 * @test
	 */
	public function should_return_ur_ls(): void {
		$this->set_fn_return( 'wp_create_nonce', function ( string $action ) {
			if ( $action === 'seat_types_by_layout_id' ) {
				return '8298ff6616';
			}

			return wp_create_nonce( $action );
		},                    true );

		$controller = $this->make_controller();

		$this->assertMatchesCodeSnapshot( var_export( $controller->get_urls(), true ) );
	}

	/**
	 * It should send JSON error if nonce missing from request
	 *
	 * @test
	 */
	public function should_send_json_error_if_nonce_missing_from_request(): void {
		unset( $_REQUEST['_ajax_nonce'], $_REQUEST['layout'], $_REQUEST['_ajax_nonce'], $_POST['_ajax_nonce'] );
		$_REQUEST['action'] = 'seat_types_by_layout_id';
		$_REQUEST['layout'] = 'foo-baz-bar';
		$sent_data          = null;
		$this->set_fn_return( 'wp_send_json_error', function ( $data ) use ( &$sent_data ) {
			$sent_data = $data;
		},                    true );

		$this->make_controller()->register();

		do_action( 'wp_ajax_seat_types_by_layout_id' );

		$this->assertMatchesJsonSnapshot( wp_json_encode( $sent_data, JSON_SNAPSHOT_OPTIONS ) );
	}

	/**
	 * It should send JSON error if nonce verification failed
	 *
	 * @test
	 */
	public function should_send_json_error_if_nonce_verification_failed(): void {
		unset( $_REQUEST['_ajax_nonce'], $_REQUEST['layout'], $_REQUEST['_ajax_nonce'], $_POST['_ajax_nonce'] );
		$_REQUEST['action']      = 'seat_types_by_layout_id';
		$_REQUEST['layout']      = 'foo-baz-bar';
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'something_else' );
		$sent_data               = null;
		$this->set_fn_return( 'wp_send_json_error', function ( $data ) use ( &$sent_data ) {
			$sent_data = $data;
		},                    true );

		$this->make_controller()->register();

		do_action( 'wp_ajax_seat_types_by_layout_id' );

		$this->assertMatchesJsonSnapshot( wp_json_encode( $sent_data, JSON_SNAPSHOT_OPTIONS ) );
	}

	/**
	 * It should return empty array if there are no layout ID specified
	 *
	 * @test
	 */
	public function should_return_empty_array_if_there_are_no_layout_id_specified(): void {
		unset( $_REQUEST['_ajax_nonce'], $_REQUEST['layout'], $_REQUEST['_ajax_nonce'], $_POST['_ajax_nonce'] );
		$_REQUEST['action']      = 'seat_types_by_layout_id';
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'seat_types_by_layout_id' );
		$sent_data               = null;
		$this->set_fn_return( 'wp_send_json_success', function ( $data ) use ( &$sent_data ) {
			$sent_data = $data;
		},                    true );

		$this->make_controller()->register();

		do_action( 'wp_ajax_seat_types_by_layout_id' );

		$this->assertMatchesJsonSnapshot( wp_json_encode( $sent_data, JSON_SNAPSHOT_OPTIONS ) );
	}

	/**
	 * It should return empty array if there are no layout ID specified
	 *
	 * @test
	 */
	public function should_return_empty_array_if_there_are_no_seat_types_for_the_specified_layout(): void {
		// Mark the Seat Types as just updated.
		set_transient( Seat_Types::update_transient_name(), time() - 1 );
		unset( $_REQUEST['_ajax_nonce'], $_REQUEST['layout'], $_REQUEST['_ajax_nonce'], $_POST['_ajax_nonce'] );
		$_REQUEST['action']      = 'seat_types_by_layout_id';
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'seat_types_by_layout_id' );
		$_REQUEST['layout']      = 'some-layout';
		$sent_data               = null;
		$this->set_fn_return( 'wp_send_json_success', function ( $data ) use ( &$sent_data ) {
			$sent_data = $data;
		},                    true );

		$this->make_controller()->register();

		do_action( 'wp_ajax_seat_types_by_layout_id' );

		$this->assertMatchesJsonSnapshot( wp_json_encode( $sent_data, JSON_SNAPSHOT_OPTIONS ) );
	}

	/**
	 * It should return all seat types for the specified layout
	 *
	 * @test
	 */
	public function should_return_all_seat_types_for_the_specified_layout(): void {
		$this->given_seat_types_just_updated();
		unset( $_REQUEST['_ajax_nonce'], $_REQUEST['layout'], $_REQUEST['_ajax_nonce'], $_POST['_ajax_nonce'] );
		$_REQUEST['action']      = 'seat_types_by_layout_id';
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'seat_types_by_layout_id' );
		$_REQUEST['layout']      = 'some-layout';
		$sent_data               = null;
		$this->set_fn_return( 'wp_send_json_success', function ( $data ) use ( &$sent_data ) {
			$sent_data = $data;
		},                    true );
		$this->given_many_seat_types_in_db_for_layout( 'some-layout', 10 );

		$this->make_controller()->register();

		do_action( 'wp_ajax_seat_types_by_layout_id' );

		$this->assertMatchesJsonSnapshot( wp_json_encode( $sent_data, JSON_SNAPSHOT_OPTIONS ) );
	}

	/**
	 * @before
	 */
	protected function become_administator(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}
}