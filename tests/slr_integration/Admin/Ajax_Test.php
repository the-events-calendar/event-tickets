<?php

namespace TEC\Tickets\Seating\Admin;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Service\oAuth_Token;
use TEC\Tickets\Seating\Service\Reservations;
use TEC\Tickets\Seating\Service\Seat_Types;
use TEC\Tickets\Seating\Tables\Layouts;
use TEC\Tickets\Seating\Tables\Maps;
use TEC\Tickets\Seating\Tables\Seat_Types as Seat_Types_Table;
use TEC\Tickets\Seating\Tables\Sessions;
use TEC\Tickets\Seating\Tests\Integration\Seat_Types_Factory;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Traits\WP_Remote_Mocks;

class Ajax_Test extends Controller_Test_Case {
	use SnapshotAssertions;
	use With_Uopz;
	use Seat_Types_Factory;
	use oAuth_Token;
	use WP_Remote_Mocks;

	protected string $controller_class = Ajax::class;

	/**
	 * @before
	 * @after
	 */
	public function truncate_tables(): void {
		Maps::truncate();
		Seat_Types_Table::truncate();
		Layouts::truncate();
		Sessions::truncate();
	}

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
		}, true );

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
		}, true );

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
		}, true );

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
		}, true );

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
		}, true );

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
		}, true );
		$this->given_many_seat_types_in_db_for_layout( 'some-layout', 10 );

		$this->make_controller()->register();

		do_action( 'wp_ajax_seat_types_by_layout_id' );

		$this->assertMatchesJsonSnapshot( wp_json_encode( $sent_data, JSON_SNAPSHOT_OPTIONS ) );
	}

	public function test_invalidate_maps_layouts_cache_without_nonce(): void {
		$this->given_maps_and_layouts_in_db();
		$this->become_administator();
		unset( $_REQUEST['_ajax_nonce'], $_POST['_ajax_nonce'] );
		$sent_data = null;
		$sent_code = null;

		$this->make_controller()->register();

		$this->set_fn_return( 'wp_send_json_error', function ( $data, $code ) use ( &$sent_data, &$sent_code ) {
			$sent_data = $data;
			$sent_code = $code;
		}, true );

		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE );

		$this->assertEquals( [ 'error' => 'Nonce verification failed' ], $sent_data );
		$this->assertEquals( 403, $sent_code );
		$this->assertCount( 3, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts::fetch_all() ) );
	}

	private function given_maps_and_layouts_in_db(): void {
		$this->given_maps_in_db();

		\TEC\Tickets\Seating\Service\Layouts::insert_rows_from_service( [
			[
				'id'            => 'some-layout-1',
				'name'          => 'Some Layout 1',
				'seats'         => 10,
				'createdDate'   => time() * 1000,
				'mapId'         => 'some-map-1',
				'screenshotUrl' => 'https://example.com/some-layouts-1.png',
			],
			[
				'id'            => 'some-layout-2',
				'name'          => 'Some Layout 2',
				'seats'         => 20,
				'createdDate'   => time() * 1000,
				'mapId'         => 'some-map-2',
				'screenshotUrl' => 'https://example.com/some-layouts-2.png',
			],
			[
				'id'            => 'some-layout-3',
				'name'          => 'Some Layout 3',
				'seats'         => 30,
				'createdDate'   => time() * 1000,
				'mapId'         => 'some-map-3',
				'screenshotUrl' => 'https://example.com/some-layouts-3.png',
			],
		] );

		\TEC\Tickets\Seating\Tables\Seat_Types::insert_many( [
			[
				'id'     => 'some-seat-type-1',
				'name'   => 'Some Seat Type 1',
				'seats'  => 10,
				'map'    => 'some-map-1',
				'layout' => 'some-layout-1',
			],
			[
				'id'     => 'some-seat-type-2',
				'name'   => 'Some Seat Type 2',
				'seats'  => 20,
				'map'    => 'some-map-2',
				'layout' => 'https://example.com/some-seat-types-2.png',
			],
			[
				'id'     => 'some-seat-type-3',
				'name'   => 'Some Seat Type 3',
				'seats'  => 30,
				'map'    => 'some-map-3',
				'layout' => 'some-layout-3',
			],
		] );
	}

	/**
	 * @before
	 */
	protected function become_administator(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	private function given_maps_in_db(): void {
		\TEC\Tickets\Seating\Service\Maps::insert_rows_from_service( [
			[
				'id'            => 'some-map-1',
				'name'          => 'Some Map 1',
				'seats'         => 10,
				'screenshotUrl' => 'https://example.com/some-map-1.png',
			],
			[
				'id'            => 'some-map-2',
				'name'          => 'Some Map 2',
				'seats'         => 20,
				'screenshotUrl' => 'https://example.com/some-map-2.png',
			],
			[
				'id'            => 'some-map-3',
				'name'          => 'Some Map 3',
				'seats'         => 30,
				'screenshotUrl' => 'https://example.com/some-map-3.png',
			],
		] );
	}

	public function test_invalidate_maps_layouts_cache_with_invalid_nonce(): void {
		$this->given_maps_and_layouts_in_db();
		$this->become_administator();
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'something_else' );
		$_POST['_ajax_nonce']    = wp_create_nonce( 'something_else' );
		$sent_data               = null;
		$sent_code               = null;

		$this->make_controller()->register();

		$this->set_fn_return( 'wp_send_json_error', function ( $data, $code ) use ( &$sent_data, &$sent_code ) {
			$sent_data = $data;
			$sent_code = $code;
		}, true );

		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE );

		$this->assertEquals( [ 'error' => 'Nonce verification failed' ], $sent_data );
		$this->assertEquals( 403, $sent_code );
		$this->assertCount( 3, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Seat_Types_Table::fetch_all() ) );
	}

	public function test_invalidate_maps_layouts_cache_with_valid_nonce(): void {
		$this->given_maps_and_layouts_in_db();
		$this->become_administator();
		$nonce                   = Ajax::NONCE_ACTION;
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( $nonce );
		$_POST['_ajax_nonce']    = wp_create_nonce( $nonce );
		$success                 = null;

		$this->make_controller()->register();

		$this->set_fn_return( 'wp_send_json_success', function () use ( &$success ) {
			$success = true;
		}, true );

		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE );

		$this->assertTrue( $success );
		$this->assertCount( 0, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Seat_Types_Table::fetch_all() ) );
	}

	public function test_invalidate_maps_layouts_cache_with_maps_invalidation_failure(): void {
		$this->given_maps_and_layouts_in_db();
		$this->become_administator();
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'something_else' );
		$_POST['_ajax_nonce']    = wp_create_nonce( 'something_else' );
		$sent_data               = null;
		$sent_code               = null;

		$this->make_controller()->register();

		// Simulate a failure to invalidate the Maps cache.
		$this->set_class_fn_return( \TEC\Tickets\Seating\Service\Maps::class, 'invalidate_cache', false );

		$this->set_fn_return( 'wp_send_json_error', function ( $data, $code ) use ( &$sent_data, &$sent_code ) {
			$sent_data = $data;
			$sent_code = $code;
		}, true );

		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE );

		$this->assertEquals( [ 'error' => 'Nonce verification failed' ], $sent_data );
		$this->assertEquals( 403, $sent_code );
		$this->assertCount( 3, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Seat_Types_Table::fetch_all() ) );
	}

	public function test_invalidate_maps_layouts_cache_with_layouts_invalidation_failure(): void {
		$this->given_maps_and_layouts_in_db();
		$this->become_administator();
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'something_else' );
		$_POST['_ajax_nonce']    = wp_create_nonce( 'something_else' );
		$sent_data               = null;
		$sent_code               = null;

		$this->make_controller()->register();

		// Simulate a failure to invalidate the Layouts cache.
		$this->set_class_fn_return( \TEC\Tickets\Seating\Service\Layouts::class, 'invalidate_cache', false );

		$this->set_fn_return( 'wp_send_json_error', function ( $data, $code ) use ( &$sent_data, &$sent_code ) {
			$sent_data = $data;
			$sent_code = $code;
		}, true );

		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE );

		$this->assertEquals( [ 'error' => 'Nonce verification failed' ], $sent_data );
		$this->assertEquals( 403, $sent_code );
		$this->assertCount( 3, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Seat_Types_Table::fetch_all() ) );
	}

	public function test_invalidate_layouts_cache_without_nonce(): void {
		$this->given_maps_and_layouts_in_db();
		$this->become_administator();
		unset( $_REQUEST['_ajax_nonce'], $_POST['_ajax_nonce'] );
		$sent_data = null;
		$sent_code = null;

		$this->make_controller()->register();

		$this->set_fn_return( 'wp_send_json_error', function ( $data, $code ) use ( &$sent_data, &$sent_code ) {
			$sent_data = $data;
			$sent_code = $code;
		}, true );

		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_LAYOUTS_CACHE );

		$this->assertEquals( [ 'error' => 'Nonce verification failed' ], $sent_data );
		$this->assertEquals( 403, $sent_code );
		$this->assertCount( 3, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Seat_Types_Table::fetch_all() ) );
	}

	public function test_invalidate_layouts_cache_with_invalid_nonce(): void {
		$this->given_maps_and_layouts_in_db();
		$this->become_administator();
		$invalid_nonce           = wp_create_nonce( 'something_else' );
		$_REQUEST['_ajax_nonce'] = $invalid_nonce;
		$_POST['_ajax_nonce']    = $invalid_nonce;
		$sent_data               = null;
		$sent_code               = null;

		$this->make_controller()->register();

		$this->set_fn_return( 'wp_send_json_error', function ( $data, $code ) use ( &$sent_data, &$sent_code ) {
			$sent_data = $data;
			$sent_code = $code;
		}, true );

		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_LAYOUTS_CACHE );

		$this->assertEquals( [ 'error' => 'Nonce verification failed' ], $sent_data );
		$this->assertEquals( 403, $sent_code );
		$this->assertCount( 3, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Seat_Types_Table::fetch_all() ) );
	}

	public function test_invalidate_layouts_cache_with_valid_nonce(): void {
		$this->given_maps_and_layouts_in_db();
		$this->become_administator();
		$nonce                   = Ajax::NONCE_ACTION;
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( $nonce );
		$_POST['_ajax_nonce']    = wp_create_nonce( $nonce );
		$success                 = null;

		$this->make_controller()->register();

		$this->set_fn_return( 'wp_send_json_success', function () use ( &$success ) {
			$success = true;
		}, true );

		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_LAYOUTS_CACHE );

		$this->assertTrue( $success );
		$this->assertCount( 3, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Seat_Types_Table::fetch_all() ) );
	}

	public function test_invalidate_layouts_cache_with_layouts_invalidation_failure(): void {
		$this->given_maps_and_layouts_in_db();
		$this->become_administator();
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'something_else' );
		$_POST['_ajax_nonce']    = wp_create_nonce( 'something_else' );
		$sent_data               = null;
		$sent_code               = null;

		$this->make_controller()->register();

		// Simulate a failure to invalidate the Maps cache.
		$this->set_class_fn_return( \TEC\Tickets\Seating\Service\Layouts::class, 'invalidate_cache', false );

		$this->set_fn_return( 'wp_send_json_error', function ( $data, $code ) use ( &$sent_data, &$sent_code ) {
			$sent_data = $data;
			$sent_code = $code;
		}, true );

		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_LAYOUTS_CACHE );

		$this->assertEquals( [ 'error' => 'Nonce verification failed' ], $sent_data );
		$this->assertEquals( 403, $sent_code );
		$this->assertCount( 3, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Seat_Types_Table::fetch_all() ) );
	}

	public function test_update_reservations(): void {
		$this->given_maps_and_layouts_in_db();
		$this->set_fn_return( 'file_get_contents', function ( string $file ) {
			if ( $file !== 'php://input' ) {
				return file_get_contents( $file );
			}

			return json_encode( [
				'token'        => 'test-token',
				'reservations' => [
					'1234567890',
					'0987654321',
				],
			] );
		}, true );
		$wp_send_json_success = null;
		$this->set_fn_return( 'wp_send_json_success', function () use ( &$wp_send_json_success ) {
			$wp_send_json_success = true;
		}, true );
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Ajax::NONCE_ACTION );
		$sessions                = tribe( Sessions::class );
		$sessions->upsert( 'test-token', 23, time() + 10 );

		$this->make_controller()->register();

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertTrue( $wp_send_json_success );
		$this->assertEquals( [
			'1234567890',
			'0987654321',
		], $sessions->get_reservations_for_token( 'test-token' ) );
	}

	public function test_update_reservations_will_return_403_on_bad_nonce(): void {
		$this->given_maps_and_layouts_in_db();
		$this->set_fn_return( 'file_get_contents', function ( string $file ) {
			if ( $file !== 'php://input' ) {
				return file_get_contents( $file );
			}

			return json_encode( [
				'token'        => 'test-token',
				'reservations' => [
					'1234567890',
					'0987654321',
				],
			] );
		}, true );
		$wp_send_json_error_data = null;
		$wp_send_json_error_code = null;
		$this->set_fn_return( 'wp_send_json_error',
			function ( $data, $code ) use ( &$wp_send_json_error_data, &$wp_send_json_error_code ) {
				$wp_send_json_error_data = $data;
				$wp_send_json_error_code = $code;
			},
			true );
		$sessions = tribe( Sessions::class );
		$sessions->upsert( 'test-token', 23, time() + 10 );

		$this->make_controller()->register();

		// Start by not sending the nonce.
		unset( $_REQUEST['_ajax_nonce'] );

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertEquals( 403, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Nonce verification failed' ], $wp_send_json_error_data );

		// Send a wrong nonce.
		$wp_send_json_error_data = null;
		$wp_send_json_error_code = null;
		$_REQUEST['_ajax_nonce'] = 'not-valid-nonce';

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertEquals( 403, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Nonce verification failed' ], $wp_send_json_error_data );

		// Send a correct nonce for another action
		$wp_send_json_error_data = null;
		$wp_send_json_error_code = null;
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'another-action' );

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertEquals( 403, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Nonce verification failed' ], $wp_send_json_error_data );

		// Send a correct nonce for the action from another user.
		$wp_send_json_error_data = null;
		$wp_send_json_error_code = null;
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Ajax::NONCE_ACTION );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertEquals( 403, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Nonce verification failed' ], $wp_send_json_error_data );
	}

	public function test_update_reservations_will_return_400_on_bad_json(): void {
		$this->given_maps_and_layouts_in_db();
		$wp_send_json_error_data = null;
		$wp_send_json_error_code = null;
		$this->set_fn_return( 'wp_send_json_error',
			function ( $data, $code ) use ( &$wp_send_json_error_data, &$wp_send_json_error_code ) {
				$wp_send_json_error_data = $data;
				$wp_send_json_error_code = $code;
			},
			true );
		$sessions = tribe( Sessions::class );
		$sessions->upsert( 'test-token', 23, time() + 10 );
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Ajax::NONCE_ACTION );
		$json_body               = null;
		$this->set_fn_return( 'file_get_contents', function ( string $file ) use ( &$json_body ) {
			if ( $file !== 'php://input' ) {
				return file_get_contents( $file );
			}

			return $json_body;
		}, true );

		$this->make_controller()->register();

		// Start by not sending the JSON body.
		$json_body = '';

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertEquals( 400, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Invalid request body' ], $wp_send_json_error_data );

		// Send a JSON body that does not contain a token.
		$json_body = json_encode( [
			'reservations' => [
				'1234567890',
				'0987654321',
			],
		] );

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertEquals( 400, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Invalid request body' ], $wp_send_json_error_data );

		// Send a JSON body that does not contain a reservations array.
		$json_body = json_encode( [
			'token' => 'test-token',
		] );

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertEquals( 400, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Invalid request body' ], $wp_send_json_error_data );

		// Send a JSON body that does not contain a reservations array.
		$json_body = json_encode( [
			'token'        => 'test-token',
			'reservations' => 'not-an-array',
		] );

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertEquals( 400, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Invalid request body' ], $wp_send_json_error_data );
	}

	public function test_update_reservations_fails_if_session_does_not_exist(): void {
		$this->given_maps_and_layouts_in_db();
		$wp_send_json_error_data = null;
		$wp_send_json_error_code = null;
		$this->set_fn_return( 'wp_send_json_error',
			function ( $data, $code ) use ( &$wp_send_json_error_data, &$wp_send_json_error_code ) {
				$wp_send_json_error_data = $data;
				$wp_send_json_error_code = $code;
			},
			true );
		$this->set_fn_return( 'file_get_contents', function ( string $file ) {
			if ( $file !== 'php://input' ) {
				return file_get_contents( $file );
			}

			return json_encode( [
				'token'        => 'test-token',
				'reservations' => [
					'1234567890',
					'0987654321',
				],
			] );
		}, true );
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Ajax::NONCE_ACTION );

		// Do not create a session beforehand, this will cause the session update to fail.

		$this->make_controller()->register();

		codecept_debug( DB::get_results( 'SELECT * FROM ' . Sessions::table_name() ) );

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertEquals( 500, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Failed to update the reservations' ], $wp_send_json_error_data );
	}

	public function test_clear_reservations(): void {
		$this->given_maps_and_layouts_in_db();
		$wp_send_json_success = null;
		$this->set_fn_return( 'wp_send_json_success', function () use ( &$wp_send_json_success ) {
			$wp_send_json_success = true;
		}, true );
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Ajax::NONCE_ACTION );
		$_REQUEST['token']       = 'test-token';
		$_REQUEST['postId']      = 23;
		update_post_meta( 23, Meta::META_KEY_UUID, 'test-post-uuid' );
		DB::query(
			DB::prepare(
				"INSERT INTO %i (token, object_id, expiration, reservations) VALUES (%s, %d, %d, %s)",
				Sessions::table_name(),
				'test-token',
				23,
				time() + 10,
				wp_json_encode( [ '1234567890', '0987654321' ] )
			)
		);
		$sessions = tribe( Sessions::class );
		$this->assertEquals( [
			'1234567890',
			'0987654321',
		], $sessions->get_reservations_for_token( 'test-token' ) );
		$this->set_oauth_token( 'auth-token' );
		$this->mock_wp_remote(
			'post',
			tribe( Reservations::class )->get_cancel_url(),
			[
				'headers' => [
					'Authorization' => 'Bearer auth-token',
				],
				'body'    => wp_json_encode( [
					'eventId' => 'test-post-uuid',
					'ids'     => [ '1234567890', '0987654321' ],
				] ),
			],
			[
				'response' => [
					'code' => 200,
				],
				'body'     => wp_json_encode( [ 'success' => true ] ),
			]
		);

		$this->make_controller()->register();

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_CLEAR_RESERVATIONS );

		$this->assertTrue( $wp_send_json_success );
		$this->assertEquals( [], $sessions->get_reservations_for_token( 'test-token' ) );
	}

	public function test_clear_reservations_fails_if_cancel_request_fails(): void {
		$this->given_maps_and_layouts_in_db();
		$wp_send_json_error_code = null;
		$wp_send_json_error_data = null;
		$this->set_fn_return( 'wp_send_json_error',
			function ( $data, $code ) use ( &$wp_send_json_error_code, &$wp_send_json_error_data ) {
				$wp_send_json_error_data = $data;
				$wp_send_json_error_code = $code;
			},
			true );
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Ajax::NONCE_ACTION );
		$_REQUEST['token']       = 'test-token';
		$_REQUEST['postId']      = 23;
		update_post_meta( 23, Meta::META_KEY_UUID, 'test-post-uuid' );
		DB::query(
			DB::prepare(
				"INSERT INTO %i (token, object_id, expiration, reservations) VALUES (%s, %d, %d, %s)",
				Sessions::table_name(),
				'test-token',
				23,
				time() + 10,
				wp_json_encode( [ '1234567890', '0987654321' ] )
			)
		);
		$sessions = tribe( Sessions::class );
		$this->assertEquals( [
			'1234567890',
			'0987654321',
		], $sessions->get_reservations_for_token( 'test-token' ) );
		$this->set_oauth_token( 'auth-token' );
		$this->mock_wp_remote(
			'post',
			tribe( Reservations::class )->get_cancel_url(),
			[
				'headers' => [
					'Authorization' => 'Bearer auth-token',
				],
				'body'    => wp_json_encode( [
					'eventId' => 'test-post-uuid',
					'ids'     => [ '1234567890', '0987654321' ],
				] ),
			],
			[
				'response' => [
					'code' => 400,
				],
				'body'     => wp_json_encode( [ 'success' => false ] ),
			]
		);

		$this->make_controller()->register();

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_CLEAR_RESERVATIONS );

		$this->assertEquals( 500, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Failed to clear the reservations' ], $wp_send_json_error_data );
	}

	public function test_clear_reservations_fails_if_session_token_clearing_fails(): void {
		$this->given_maps_and_layouts_in_db();
		$wp_send_json_error_code = null;
		$wp_send_json_error_data = null;
		$this->set_fn_return( 'wp_send_json_error',
			function ( $data, $code ) use ( &$wp_send_json_error_code, &$wp_send_json_error_data ) {
				$wp_send_json_error_data = $data;
				$wp_send_json_error_code = $code;
			},
			true );
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Ajax::NONCE_ACTION );
		$_REQUEST['token']       = 'test-token';
		$_REQUEST['postId']      = 23;
		update_post_meta( 23, Meta::META_KEY_UUID, 'test-post-uuid' );
		// Do not add a token entry in the sessions table: this will trigger a failure in the `clear_token_reservations` method.
		$sessions = tribe( Sessions::class );
		$this->set_oauth_token( 'auth-token' );
		$this->mock_wp_remote(
			'post',
			tribe( Reservations::class )->get_cancel_url(),
			[
				'headers' => [
					'Authorization' => 'Bearer auth-token',
				],
				'body'    => wp_json_encode( [
					'eventId' => 'test-post-uuid',
					'ids'     => [ '1234567890', '0987654321' ],
				] ),
			],
			[
				'response' => [
					'code' => 200,
				],
				'body'     => wp_json_encode( [ 'success' => true ] ),
			]
		);

		$this->make_controller()->register();

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_CLEAR_RESERVATIONS );

		$this->assertEquals( 500, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Failed to clear the reservations' ], $wp_send_json_error_data );
	}

	public function test_clear_reservations_will_return_403_on_bad_nonce(): void {
		$wp_send_json_error_code = null;
		$wp_send_json_error_data = null;
		$this->set_fn_return( 'wp_send_json_error',
			function ( $data, $code ) use ( &$wp_send_json_error_code, &$wp_send_json_error_data ) {
				$wp_send_json_error_data = $data;
				$wp_send_json_error_code = $code;
			},
			true );

		$this->make_controller()->register();

		// Start by not sending the nonce.
		unset( $_REQUEST['_ajax_nonce'] );

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_CLEAR_RESERVATIONS );

		$this->assertEquals( 403, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Nonce verification failed' ], $wp_send_json_error_data );

		// Send a wrong nonce.
		$wp_send_json_error_data = null;
		$wp_send_json_error_code = null;
		$_REQUEST['_ajax_nonce'] = 'not-valid-nonce';

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_CLEAR_RESERVATIONS );

		$this->assertEquals( 403, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Nonce verification failed' ], $wp_send_json_error_data );

		// Send a correct nonce for another action.
		$wp_send_json_error_data = null;
		$wp_send_json_error_code = null;
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'another-action' );

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertEquals( 403, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Nonce verification failed' ], $wp_send_json_error_data );

		// Send a correct nonce for the action from another user.
		$wp_send_json_error_data = null;
		$wp_send_json_error_code = null;
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Ajax::NONCE_ACTION );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertEquals( 403, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Nonce verification failed' ], $wp_send_json_error_data );
	}

	public function test_clear_reservations_fails_on_bad_arguments(): void {
		$wp_send_json_error_code = null;
		$wp_send_json_error_data = null;
		$this->set_fn_return( 'wp_send_json_error',
			function ( $data, $code ) use ( &$wp_send_json_error_code, &$wp_send_json_error_data ) {
				$wp_send_json_error_data = $data;
				$wp_send_json_error_code = $code;
			},
			true );
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Ajax::NONCE_ACTION );
		update_post_meta( 23, Meta::META_KEY_UUID, 'test-post-uuid' );
		DB::query(
			DB::prepare(
				"INSERT INTO %i (token, object_id, expiration, reservations) VALUES (%s, %d, %d, %s)",
				Sessions::table_name(),
				'test-token',
				23,
				time() + 10,
				wp_json_encode( [ '1234567890', '0987654321' ] )
			)
		);
		$sessions = tribe( Sessions::class );
		$this->assertEquals( [
			'1234567890',
			'0987654321',
		], $sessions->get_reservations_for_token( 'test-token' ) );

		$this->make_controller()->register();

		// Start by not sending any parameters.
		unset( $_REQUEST['token'], $_REQUEST['postId'] );

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_CLEAR_RESERVATIONS );

		$this->assertEquals( 400, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Invalid request parameters' ], $wp_send_json_error_data );

		// Now send only a token.
		$_REQUEST['token'] = 'test-token';

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_CLEAR_RESERVATIONS );

		$this->assertEquals( 400, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Invalid request parameters' ], $wp_send_json_error_data );

		// Now send only a post ID.
		unset( $_REQUEST['token'] );
		$_REQUEST['postId'] = 23;

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_CLEAR_RESERVATIONS );

		$this->assertEquals( 400, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Invalid request parameters' ], $wp_send_json_error_data );
	}
}
