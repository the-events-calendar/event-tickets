<?php

namespace TEC\Tickets\Seating\Admin;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Seating\Service\Seat_Types;
use TEC\Tickets\Seating\Tables\Layouts;
use TEC\Tickets\Seating\Tables\Maps;
use TEC\Tickets\Seating\Tables\Seat_Types as Seat_Types_Table;
use TEC\Tickets\Seating\Tests\Integration\Seat_Types_Factory;
use Tribe\Tests\Traits\With_Uopz;

class Ajax_Test extends Controller_Test_Case {
	use SnapshotAssertions;
	use With_Uopz;
	use Seat_Types_Factory;

	protected string $controller_class = Ajax::class;

	/**
	 * @before
	 * @after
	 */
	public function truncate_tables(): void {
		Maps::truncate();
		Seat_Types_Table::truncate();
		Layouts::truncate();
	}

	/**
	 * It should return URLs
	 *
	 * @test
	 */
	public function should_return_ur_ls(): void {
		$this->set_fn_return(
			'wp_create_nonce',
			function ( string $action ) {
				if ( $action === 'seat_types_by_layout_id' ) {
					return '8298ff6616';
				}

				return wp_create_nonce( $action );
			},
			true 
		);

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
		$this->set_fn_return(
			'wp_send_json_error',
			function ( $data ) use ( &$sent_data ) {
				$sent_data = $data;
			},
			true 
		);

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
		$this->set_fn_return(
			'wp_send_json_error',
			function ( $data ) use ( &$sent_data ) {
				$sent_data = $data;
			},
			true 
		);

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
		$this->set_fn_return(
			'wp_send_json_success',
			function ( $data ) use ( &$sent_data ) {
				$sent_data = $data;
			},
			true 
		);

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
		$this->set_fn_return(
			'wp_send_json_success',
			function ( $data ) use ( &$sent_data ) {
				$sent_data = $data;
			},
			true 
		);

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
		$this->set_fn_return(
			'wp_send_json_success',
			function ( $data ) use ( &$sent_data ) {
				$sent_data = $data;
			},
			true 
		);
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

		$this->set_fn_return(
			'wp_send_json_error',
			function ( $data, $code ) use ( &$sent_data, &$sent_code ) {
				$sent_data = $data;
				$sent_code = $code;
			},
			true 
		);

		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE );

		$this->assertEquals( [ 'error' => 'Nonce verification failed' ], $sent_data );
		$this->assertEquals( 403, $sent_code );
		$this->assertCount( 3, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts::fetch_all() ) );
	}

	private function given_maps_and_layouts_in_db(): void {
		$this->given_maps_in_db();

		\TEC\Tickets\Seating\Service\Layouts::insert_rows_from_service(
			[
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
			] 
		);

		\TEC\Tickets\Seating\Tables\Seat_Types::insert_many(
			[
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
			] 
		);
	}

	/**
	 * @before
	 */
	protected function become_administator(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	private function given_maps_in_db(): void {
		\TEC\Tickets\Seating\Service\Maps::insert_rows_from_service(
			[
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
			] 
		);
	}

	public function test_invalidate_maps_layouts_cache_with_invalid_nonce(): void {
		$this->given_maps_and_layouts_in_db();
		$this->become_administator();
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'something_else' );
		$_POST['_ajax_nonce']    = wp_create_nonce( 'something_else' );
		$sent_data               = null;
		$sent_code               = null;

		$this->make_controller()->register();

		$this->set_fn_return(
			'wp_send_json_error',
			function ( $data, $code ) use ( &$sent_data, &$sent_code ) {
				$sent_data = $data;
				$sent_code = $code;
			},
			true 
		);

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

		$this->set_fn_return(
			'wp_send_json_success',
			function () use ( &$success ) {
				$success = true;
			},
			true 
		);

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

		$this->set_fn_return(
			'wp_send_json_error',
			function ( $data, $code ) use ( &$sent_data, &$sent_code ) {
				$sent_data = $data;
				$sent_code = $code;
			},
			true 
		);

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

		$this->set_fn_return(
			'wp_send_json_error',
			function ( $data, $code ) use ( &$sent_data, &$sent_code ) {
				$sent_data = $data;
				$sent_code = $code;
			},
			true 
		);

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

		$this->set_fn_return(
			'wp_send_json_error',
			function ( $data, $code ) use ( &$sent_data, &$sent_code ) {
				$sent_data = $data;
				$sent_code = $code;
			},
			true 
		);

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

		$this->set_fn_return(
			'wp_send_json_error',
			function ( $data, $code ) use ( &$sent_data, &$sent_code ) {
				$sent_data = $data;
				$sent_code = $code;
			},
			true 
		);

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

		$this->set_fn_return(
			'wp_send_json_success',
			function () use ( &$success ) {
				$success = true;
			},
			true 
		);

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

		$this->set_fn_return(
			'wp_send_json_error',
			function ( $data, $code ) use ( &$sent_data, &$sent_code ) {
				$sent_data = $data;
				$sent_code = $code;
			},
			true 
		);

		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_LAYOUTS_CACHE );

		$this->assertEquals( [ 'error' => 'Nonce verification failed' ], $sent_data );
		$this->assertEquals( 403, $sent_code );
		$this->assertCount( 3, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Seat_Types_Table::fetch_all() ) );
	}
	
	public function test_delete_map_from_service_with_invalid_nonce(): void {
		$this->become_administator();
		$invalid_nonce           = wp_create_nonce( 'something_else' );
		$_REQUEST['_ajax_nonce'] = $invalid_nonce;
		$_POST['_ajax_nonce']    = $invalid_nonce;
		$sent_data               = null;
		$sent_code               = null;
		
		$this->make_controller()->register();
		
		$this->set_fn_return(
			'wp_send_json_error',
			function ( $data, $code ) use ( &$sent_data, &$sent_code ) {
				$sent_data = $data;
				$sent_code = $code;
			},
			true
		);
		
		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_MAP );
		
		$this->assertEquals( [ 'error' => 'Nonce verification failed' ], $sent_data );
		$this->assertEquals( 403, $sent_code );
	}
	
	public function test_delete_map_from_service_with_invalid_map_id(): void {
		$this->become_administator();
		$nonce                   = Ajax::NONCE_ACTION;
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( $nonce );
		$_POST['_ajax_nonce']    = wp_create_nonce( $nonce );
		$sent_data               = null;
		$sent_code               = null;
		
		$this->make_controller()->register();
		
		$this->set_fn_return(
			'wp_send_json_error',
			function ( $data, $code ) use ( &$sent_data, &$sent_code ) {
				$sent_data = $data;
				$sent_code = $code;
			},
			true
		);
		
		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_MAP );
		
		$this->assertEquals( [ 'error' => 'No map ID provided' ], $sent_data );
		$this->assertEquals( 400, $sent_code );
	}
	
	public function test_delete_map_from_service_with_success() {
		$this->given_maps_and_layouts_in_db();
		$this->become_administator();
		$nonce                   = Ajax::NONCE_ACTION;
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( $nonce );
		$_POST['_ajax_nonce']    = wp_create_nonce( $nonce );
		$_POST['mapId']          = 'some-map-1';
		$fetch_url               = null;
		$data                    = null;
		$success                 = null;
		
		$this->make_controller()->register();
		
		tribe_update_option( 'events_tickets_seating_access_token', 'some-token' );
		
		$this->set_fn_return(
			'wp_send_json_success',
			function () use ( &$success ) {
				$success = true;
			},
			true
		);
		
		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) use ( &$fetch_url, &$data ) {
				$fetch_url = $url;
				$data      = $args;
				return [ 'response' => [ 'code' => 200 ] ];
			},
			10,
			3
		);
		
		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_MAP );
		
		$this->assertTrue( $success );
		$this->assertMatchesJsonSnapshot(
			wp_json_encode(
				[
					'success'   => $success,
					'fetch_url' => $fetch_url,
					'method'    => $data['method'],
					'headers'   => $data['headers'],
				],
				JSON_SNAPSHOT_OPTIONS 
			)
		);
	}
}
