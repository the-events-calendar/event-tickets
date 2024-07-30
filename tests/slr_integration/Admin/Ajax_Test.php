<?php

namespace TEC\Tickets\Seating\Admin;

use PHPUnit\Framework\Assert;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Service\Layouts as Layouts_Service;
use TEC\Tickets\Seating\Service\Maps as Maps_Service;
use TEC\Tickets\Seating\Service\OAuth_Token;
use TEC\Tickets\Seating\Service\Reservations;
use TEC\Tickets\Seating\Tables\Layouts;
use TEC\Tickets\Seating\Tables\Maps;
use TEC\Tickets\Seating\Tables\Seat_Types as Seat_Types_Table;
use TEC\Tickets\Seating\Tables\Sessions;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tests\Traits\WP_Remote_Mocks;
use Tribe\Tests\Traits\WP_Send_Json_Mocks;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Reservations_Maker;
use Tribe__Tickets__Data_API as Data_API;


class Ajax_Test extends Controller_Test_Case {
	use SnapshotAssertions;
	use With_Uopz;
	use OAuth_Token;
	use WP_Remote_Mocks;
	use Reservations_Maker;
	use WP_Send_JSON_Mocks;
	use Ticket_Maker;
	use Attendee_Maker;

	protected string $controller_class = Ajax::class;

	/**
	 * @before
	 * @after
	 */
	public function reset_tribe_options_cache(): void {
		tribe_unset_var( \Tribe__Settings_Manager::OPTION_CACHE_VAR_NAME );
	}

	/**
	 * @before
	 */
	public function ensure_tickets_commerce_active(): void {
		// Ensure the Tickets Commerce module is active.
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		add_filter(
			'tribe_tickets_get_modules',
			function ( $modules ) {
				$modules[ Module::class ] = tribe( Module::class )->plugin_name;

				return $modules;
			}
		);

		// Reset Data_API object, so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API() );
	}

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

	private function given_maps_layouts_and_seat_types_in_db(): void {
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
		set_transient( \TEC\Tickets\Seating\Service\Maps::update_transient_name(), time() );

		Layouts_Service::insert_rows_from_service(
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
		set_transient( Layouts_Service::update_transient_name(), time() );

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
					'layout' => 'some-layout-2',
				],
				[
					'id'     => 'some-seat-type-3',
					'name'   => 'Some Seat Type 3',
					'seats'  => 30,
					'map'    => 'some-map-3',
					'layout' => 'some-layout-3',
				],
				[
					'id'     => 'some-seat-type-4',
					'name'   => 'Some Seat Type 1',
					'seats'  => 10,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
			]
		);
		set_transient( \TEC\Tickets\Seating\Service\Seat_Types::update_transient_name(), time() );
	}

	public function test_get_localized_data(): void {
		$this->set_fn_return( 'wp_create_nonce', '88b1a4b166' );
		$controller = $this->make_controller();

		$this->assertMatchesJsonSnapshot( wp_json_encode( $controller->get_ajax_data(), JSON_SNAPSHOT_OPTIONS ) );
	}

	private function set_up_ajax_request_context( int $user_id = null ): void {
		if ( null === $user_id ) {
			wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		}
		$_REQUEST['action']      = Ajax::NONCE_ACTION;
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Ajax::NONCE_ACTION );
	}

	public function test_fetch_seat_types_by_layout_id(): void {
		$this->set_up_ajax_request_context();
		$this->given_maps_layouts_and_seat_types_in_db();

		$this->make_controller()->register();

		// Call without specifying a layout ID.
		$wp_send_json_success = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID );
		$this->assertTrue( $wp_send_json_success->was_called_times_with( 1, [] ) );
		$this->reset_wp_send_json_mocks();

		// Call with an empty layout ID.
		$_REQUEST['layout']   = '';
		$wp_send_json_success = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID );
		$this->assertTrue( $wp_send_json_success->was_called_times_with( 1, [] ) );
		$this->reset_wp_send_json_mocks();

		// Call with a layout ID that has no seat types.
		$_REQUEST['layout']   = 'some-layout-4';
		$wp_send_json_success = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID );
		$this->assertTrue( $wp_send_json_success->was_called_times_with( 1, [] ) );
		$this->reset_wp_send_json_mocks();

		// Call with a layout ID that has no seat types.
		$_REQUEST['layout']   = 'some-layout-4';
		$wp_send_json_success = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID );
		$this->assertTrue( $wp_send_json_success->was_called_times_with( 1, [] ) );
		$this->reset_wp_send_json_mocks();

		// Call with a layout ID that has seat types.
		$_REQUEST['layout']   = 'some-layout-1';
		$wp_send_json_success = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID );
		$this->assertTrue( $wp_send_json_success->was_called_times_with(
			1,
			[
				[
					'id'    => 'some-seat-type-1',
					'name'  => 'Some Seat Type 1',
					'seats' => '10',
				],
				[
					'id'    => 'some-seat-type-4',
					'name'  => 'Some Seat Type 1',
					'seats' => '10',
				],
			],
		),
			$wp_send_json_success->get_calls_as_string()
		);
	}

	public function test_invalidate_maps_layouts_cache(): void {
		$this->set_up_ajax_request_context();
		$this->given_maps_layouts_and_seat_types_in_db();

		$this->make_controller()->register();

		// Layouts invalidation fail.
		$this->set_class_fn_return( Layouts_Service::class, 'invalidate_cache', false );
		$mock_wp_send_json_error = $this->mock_wp_send_json_error();
		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE );
		$this->assertTrue(
			$mock_wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'Failed to invalidate the layouts cache.' ],
				500
			),
			$mock_wp_send_json_error->get_calls_as_string()
		);
		$this->assertCount( 3, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 4, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->reset_wp_send_json_mocks();

		// Maps invalidation fail.
		$this->set_class_fn_return( Layouts_Service::class, 'invalidate_cache', function (): bool {
			return Layouts_Service::invalidate_cache();
		}, true );
		$this->set_class_fn_return( Maps_Service::class, 'invalidate_cache', false );
		$mock_wp_send_json_error = $this->mock_wp_send_json_error();
		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE );
		$this->assertTrue(
			$mock_wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'Failed to invalidate the maps layouts cache.' ],
				500
			),
			$mock_wp_send_json_error->get_calls_as_string()
		);
		$this->assertCount( 3, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->reset_wp_send_json_mocks();

		// All good.
		$this->set_class_fn_return( Layouts_Service::class, 'invalidate_cache', function (): bool {
			return Layouts_Service::invalidate_cache();
		}, true );
		$this->set_class_fn_return( Maps_Service::class, 'invalidate_cache', function (): bool {
			return Maps_Service::invalidate_cache();
		}, true );
		$mock_wp_send_json_success = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE );
		$this->assertTrue(
			$mock_wp_send_json_success->was_called_times_with( 1, [] ),
			$mock_wp_send_json_success->get_calls_as_string()
		);
		$this->assertCount( 0, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Seat_Types_Table::fetch_all() ) );
	}

	public function test_invalidate_layouts_cache(): void {
		$this->set_up_ajax_request_context();
		$this->given_maps_layouts_and_seat_types_in_db();

		$this->make_controller()->register();

		// Layouts invalidation fail.
		$this->set_class_fn_return( Layouts_Service::class, 'invalidate_cache', false );
		$mock_wp_send_json_error = $this->mock_wp_send_json_error();
		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_LAYOUTS_CACHE );
		$this->assertTrue(
			$mock_wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'Failed to invalidate the layouts cache.' ],
				500
			),
			$mock_wp_send_json_error->get_calls_as_string()
		);
		$this->assertCount( 3, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 4, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->reset_wp_send_json_mocks();

		// All good.
		$this->set_class_fn_return( Layouts_Service::class, 'invalidate_cache', function (): bool {
			return Layouts_Service::invalidate_cache();
		}, true );
		$mock_wp_send_json_success = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_LAYOUTS_CACHE );
		$this->assertTrue(
			$mock_wp_send_json_success->was_called_times_with( 1, [] ),
			$mock_wp_send_json_success->get_calls_as_string()
		);
		$this->assertCount( 3, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Seat_Types_Table::fetch_all() ) );
	}

	public function test_delete_map_from_service(): void {
		$this->set_up_ajax_request_context();
		$this->given_maps_layouts_and_seat_types_in_db();
		$maps_service = $this->test_services->get( Maps_Service::class );
		$this->set_oauth_token( 'some-token' );

		$controller = $this->make_controller();
		$controller->register();

		// Map ID is missing from request context.
		unset( $_REQUEST['mapId'] );
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_MAP );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'No map ID provided' ],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->assertCount( 3, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 4, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->reset_wp_send_json_mocks();

		// Map deletion from  service fails.
		$_REQUEST['mapId']  = 'some-map-1';
		$wp_send_json_error = $this->mock_wp_send_json_error();
		$wp_remote          = $this->mock_wp_remote(
			'delete',
			$maps_service->get_delete_url( 'some-map-1' ),
			[
				'method'  => 'DELETE',
				'headers' => [
					'Authorization' => 'Bearer some-token',
					'Content-Type'  => 'application/json',
				],
			],
			function () {
				return [
					'response' => [
						'code' => 400,
					],
					'body'     => wp_json_encode(
						[
							'success' => false,
						]
					),
				];
			}
		);

		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_MAP );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'Failed to delete the map.' ],
				500
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->assertCount( 3, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 4, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->assertTrue( $wp_remote->was_called() );
		$this->reset_wp_send_json_mocks();
		$wp_remote->tear_down();

		// Map deletion succeeds.
		$_REQUEST['mapId']    = 'some-map-1';
		$wp_send_json_success = $this->mock_wp_send_json_success();
		$wp_remote            = $this->mock_wp_remote(
			'delete',
			$maps_service->get_delete_url( 'some-map-1' ),
			[
				'method'  => 'DELETE',
				'headers' => [
					'Authorization' => 'Bearer some-token',
					'Content-Type'  => 'application/json',
				],
			],
			function () {
				return [
					'response' => [
						'code' => 200,
					],
					'body'     => wp_json_encode(
						[
							'success' => true,
						]
					),
				];
			}
		);

		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_MAP );

		$this->assertTrue(
			$wp_send_json_success->was_called_times_with( 1 ),
			$wp_send_json_success->get_calls_as_string()
		);
		// After a deletion the local seat type, maps and layouts caches should be invalidated.
		$this->assertCount( 0, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->assertTrue( $wp_remote->was_called() );
	}

	public function test_delete_layout_from_service(): void {
		$this->set_up_ajax_request_context();
		$this->given_maps_layouts_and_seat_types_in_db();
		$layouts_service = $this->test_services->get( Layouts_Service::class );
		$this->set_oauth_token( 'some-token' );

		$controller = $this->make_controller();
		$controller->register();

		// Layout ID is missing from request context.
		unset( $_REQUEST['layoutId'] );
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_LAYOUT );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'No layout ID or map ID provided' ],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->assertCount( 3, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 4, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->reset_wp_send_json_mocks();

		// Map ID is missing from request context.
		$_REQUEST['layoutId'] = 'some-layout-1';
		unset( $_REQUEST['mapId'] );
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_LAYOUT );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'No layout ID or map ID provided' ],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->assertCount( 3, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 4, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->reset_wp_send_json_mocks();

		// Layout deletion from service fails.
		$_REQUEST['layoutId'] = 'some-layout-1';
		$_REQUEST['mapId']    = 'some-map-1';
		$wp_send_json_error   = $this->mock_wp_send_json_error();
		$wp_remote            = $this->mock_wp_remote(
			'delete',
			$layouts_service->get_delete_url( 'some-layout-1', 'some-map-1' ),
			[
				'method'  => 'DELETE',
				'headers' => [
					'Authorization' => 'Bearer some-token',
					'Content-Type'  => 'application/json',
				],
			],
			function () {
				return [
					'response' => [
						'code' => 400,
					],
					'body'     => wp_json_encode(
						[
							'success' => false,
						]
					),
				];
			}
		);

		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_LAYOUT );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'Failed to delete the layout.' ],
				500
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->assertCount( 3, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 4, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->assertTrue( $wp_remote->was_called() );
		$this->reset_wp_send_json_mocks();
		$wp_remote->tear_down();

		// Layout deletion succeeds.
		$_REQUEST['layoutId'] = 'some-layout-1';
		$_REQUEST['mapId']    = 'some-map-1';
		$wp_send_json_success = $this->mock_wp_send_json_success();
		$wp_remote            = $this->mock_wp_remote(
			'delete',
			$layouts_service->get_delete_url( 'some-layout-1', 'some-map-1' ),
			[
				'method'  => 'DELETE',
				'headers' => [
					'Authorization' => 'Bearer some-token',
					'Content-Type'  => 'application/json',
				],
			],
			function () {
				return [
					'response' => [
						'code' => 200,
					],
					'body'     => wp_json_encode(
						[
							'success' => true,
						]
					),
				];
			}
		);

		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_LAYOUT );

		$this->assertTrue(
			$wp_send_json_success->was_called_times_with( 1 ),
			$wp_send_json_success->get_calls_as_string()
		);
		// After a deletion the local seat type, maps and layouts caches should be invalidated.
		$this->assertCount( 0, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Layouts::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->reset_wp_send_json_mocks();
	}

	public function test_update_reservations(): void {
		$this->set_up_ajax_request_context( 0 );
		$request_body = null;
		$this->set_fn_return( 'file_get_contents', function ( $file, ...$args ) use ( &$request_body ) {
			if ( $file !== 'php://input' ) {
				return file_get_contents( $file, ...$args );
			}

			return $request_body;
		}, true );
		$post_id   = self::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 23 );
		$sessions  = tribe( Sessions::class );
		$sessions->upsert( 'some-token', $post_id, time() + 100 );

		$controller = $this->make_controller();
		$controller->register();

		// Missing post ID from request context.
		unset( $_REQUEST['postId'] );
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'No post ID provided' ],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Mangled request body.
		$_REQUEST['postId'] = $post_id;
		$request_body       = 'not-JSON';
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'Invalid request body' ],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		$reservations_data = $this->create_mock_ajax_reservations_data( [ $ticket_id ], 2 );

		// Token missing from request context.
		$_REQUEST['postId'] = $post_id;
		$request_body       = wp_json_encode(
			[
				'reservations' => $reservations_data,
			]
		);
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'Invalid request body' ],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Reservations data missing from request context.
		$_REQUEST['postId'] = $post_id;
		$request_body       = wp_json_encode(
			[
				'token' => 'some-token',
			]
		);
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'Invalid request body' ],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Reservation data is not an array.
		$_REQUEST['postId'] = $post_id;
		$request_body       = wp_json_encode(
			[
				'token'        => 'some-token',
				'reservations' => 'some-reservations',
			]
		);
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'Invalid request body' ],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Token is not a string.
		$_REQUEST['postId'] = $post_id;
		$request_body       = wp_json_encode(
			[
				'token'        => 123,
				'reservations' => [],
			]
		);
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'Invalid request body' ],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Not all reservation entries are not arrays.
		$_REQUEST['postId'] = $post_id;
		$request_body       = wp_json_encode(
			[
				'token'        => 'some-token',
				'reservations' => array_merge( $reservations_data, [ 'some-reservation' => 'not-an-array' ] ),
			]
		);
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'Reservation data is not in correct format' ],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Not all reservation entries have all the correct information.
		$_REQUEST['postId'] = $post_id;
		$request_body       = wp_json_encode(
			[
				'token'        => 'some-token',
				'reservations' => array_merge( $reservations_data, [
					[
						89 => [
							'reservationId' => 'some-reservation-id',
							'seatTypeId'    => 'some-seat-type-id',
						]
					]
				] ),
			]
		);
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'Reservation data is not in correct format' ],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Update of reservations fails.
		// Delete the token entry in the sessions table, failing the update.
		DB::query(
			DB::prepare(
				"DELETE FROM %i WHERE token = %s",
				Sessions::table_name(),
				'some-token'
			)
		);
		$wp_send_json_error = $this->mock_wp_send_json_error();
		$request_body       = wp_json_encode(
			[
				'token'        => 'some-token',
				'reservations' => $reservations_data,
			]
		);

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'Failed to update the reservations' ],
				500
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Update of reservations succeeds.
		// Re-insert the token entry in the sessions table, making the update possible.
		$sessions->upsert( 'some-token', $post_id, time() + 100 );
		$wp_send_json_success = $this->mock_wp_send_json_success();
		$request_body         = wp_json_encode(
			[
				'token'        => 'some-token',
				'reservations' => $reservations_data,
			]
		);

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertTrue(
			$wp_send_json_success->was_called_times_with( 1 ),
			$wp_send_json_success->get_calls_as_string()
		);
		codecept_debug( $sessions->get_reservations_for_token( 'some-token' ) );
		$this->assertEquals( [
			$ticket_id => [
				[
					'reservation_id' => 'reservation-id-1',
					'seat_type_id'   => 'seat-type-id-0',
					'seat_label'     => 'seat-label-0-1',
				],
				[
					'reservation_id' => 'reservation-id-2',
					'seat_type_id'   => 'seat-type-id-0',
					'seat_label'     => 'seat-label-0-2',
				],
			],
		], $sessions->get_reservations_for_token( 'some-token' ) );
	}

	public function test_clear_reservations(): void {
		$this->set_up_ajax_request_context( 0 );
		$sessions = tribe( Sessions::class );
		$sessions->upsert( 'some-token', self::factory()->post->create(), time() + 100 );
		$reservations_data = $this->create_mock_reservations_data( [ 23 ], 2 );
		$sessions->update_reservations( 'some-token', $reservations_data );
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, Meta::META_KEY_UUID, 'some-post-uuid' );
		$this->set_oauth_token( 'some-token' );

		$controller = $this->make_controller();
		$controller->register();

		// Missing post ID from request context.
		unset( $_REQUEST['postId'] );
		$_REQUEST['token']  = 'some-token';
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_CLEAR_RESERVATIONS );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'Invalid request parameters' ],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Missing token from request context.
		$_REQUEST['postId'] = $post_id;
		unset( $_REQUEST['token'] );
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_CLEAR_RESERVATIONS );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'Invalid request parameters' ],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Reservations cancellation fails.
		$_REQUEST['postId'] = $post_id;
		$_REQUEST['token']  = 'some-token';
		$reservations = tribe( Reservations::class );
		$wp_remote            = $this->mock_wp_remote(
			'post',
			$reservations->get_cancel_url(),
			[
				'headers' => [
					'Authorization' => 'Bearer some-token',
					'Content-Type'  => 'application/json',
				],
				'body' => wp_json_encode(
					[
						'eventId' => 'some-post-uuid',
						'ids'     =>  $sessions->get_reservation_uuids_for_token( 'some-token' ),
					]
				),
			],
			function () {
				return [
					'response' => [
						'code' => 400,
					],
					'body'     => wp_json_encode(
						[
							'success' => false,
						]
					),
				];
			}
		);
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_CLEAR_RESERVATIONS );

		$this->assertTrue( $wp_remote->was_called() );
		$this->assertTrue(
			$wp_send_json_error->was_called_times_with( 1,
				[ 'error' => 'Failed to clear the reservations' ],
				500
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Reservation cancellation succeeds.
		$_REQUEST['postId'] = $post_id;
		$_REQUEST['token']  = 'some-token';
		$reservations = tribe( Reservations::class );
		$wp_remote            = $this->mock_wp_remote(
			'post',
			$reservations->get_cancel_url(),
			[
				'headers' => [
					'Authorization' => 'Bearer some-token',
					'Content-Type'  => 'application/json',
				],
				'body' => wp_json_encode(
					[
						'eventId' => 'some-post-uuid',
						'ids'     =>  $sessions->get_reservation_uuids_for_token( 'some-token' ),
					]
				),
			],
			function () {
				return [
					'response' => [
						'code' => 200,
					],
					'body'     => wp_json_encode(
						[
							'success' => true,
						]
					),
				];
			}
		);
		$wp_send_json_success = $this->mock_wp_send_json_success();

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_CLEAR_RESERVATIONS );

		$this->assertTrue( $wp_remote->was_called() );
		$this->assertTrue(
			$wp_send_json_success->was_called_times_with( 1 ),
			$wp_send_json_success->get_calls_as_string()
		);
		$this->assertEmpty( $sessions->get_reservations_for_token( 'some-token' ) );
		$this->reset_wp_send_json_mocks();
	}

	public function test_clear_commerce_cart_cookie(): void {
		$cookie_name             = Cart::$cart_hash_cookie_name;
		$_COOKIE[ $cookie_name ] = 'some-commerce-cart-hash';
		$setcookie_call          = null;
		$this->set_fn_return(
			'setcookie',
			function (
				$name,
				$value,
				$expire,
				$path,
				$domain,
				$secure,
				$httponly
			) use (
				$cookie_name,
				&$setcookie_call
			) {
				$setcookie_call = true;

				Assert::assertEquals( $cookie_name, $name );
				Assert::assertEquals( '', $value );
				Assert::assertEquals( time() - DAY_IN_SECONDS, $expire, '', 5 );
				Assert::assertEquals( COOKIEPATH, $path );
				Assert::assertEquals( COOKIE_DOMAIN, $domain );
				Assert::assertTrue( $secure );
				Assert::assertTrue( $httponly );

				return true;
			},
			true
		);
		$post_id = self::factory()->post->create();
		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );
		update_post_meta( $post_id, $tickets_handler->key_provider_field, Module::class );

		$this->make_controller()->register();

		do_action( 'tec_tickets_seating_session_interrupt', $post_id );

		$this->assertFalse( isset( $_COOKIE[ $cookie_name ] ) );
		$this->assertTrue( $setcookie_call );
	}

	public function test_delete_reservations(): void {
		$this->set_up_ajax_request_context();
		// Create 3 Attendees and assign a reservation ID to each one of them.
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );
		[ $attendee_1, $attendee_2, $attendee_3 ] = $this->create_many_attendees_for_ticket( 3, $ticket_id, $post_id );
		update_post_meta( $attendee_1, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-1' );
		update_post_meta( $attendee_2, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-2' );
		update_post_meta( $attendee_3, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-3' );
		$this->set_oauth_token( 'auth-token' );
		$request_body = null;
		$this->set_fn_return( 'file_get_contents', function ( $file, ...$args ) use ( &$request_body ) {
			if ( $file !== 'php://input' ) {
				return file_get_contents( $file, ...$args );
			}

			return $request_body;
		}, true );

		$controller = $this->make_controller();
		$controller->register();

		// Request body is empty.
		$request_body = '';
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_RESERVATIONS );

		$this->assertTrue( $wp_send_json_error->was_called_times_with( 1,
			[
				'error' => 'Invalid request body',
			],
			400
		) );
		$this->reset_wp_send_json_mocks();

		// Request body is not valid JSON.
		$request_body = 'not-json';
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_RESERVATIONS );

		$this->assertTrue( $wp_send_json_error->was_called_times_with( 1,
			[
				'error' => 'Invalid request body',
			],
			400
		) );
		$this->reset_wp_send_json_mocks();

		// Request body is valid JSON but not an array of non-empty strings.
		$request_body = '["", ""]';
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_RESERVATIONS );

		$this->assertTrue( $wp_send_json_error->was_called_times_with( 1,
			[
				'error' => 'Invalid request body',
			],
			400
		) );
		$this->reset_wp_send_json_mocks();

		// Deletion succeeds.
		$request_body         = '["reservation-uuid-1", "reservation-uuid-4"]';
		$wp_send_json_success = $this->mock_wp_send_json_success();
		$delete_map = [];
		add_action( 'tec_tickets_seating_delete_reservations_from_attendees', function ( $map ) use ( &$delete_map ) {
			$delete_map = $map;
		} );

		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_RESERVATIONS );

		$this->assertTrue( $wp_send_json_success->was_called_times_with( 1,
			[
				'numberDeleted' => 1,
			],
		) );
		$this->reset_wp_send_json_mocks();
		$this->assertEquals( $delete_map, [ 'reservation-uuid-1' => $attendee_1 ] );
		$this->assertEquals('', get_post_meta( $attendee_1, Meta::META_KEY_RESERVATION_ID, true ) );

		// Send a second request to delete the rest.
		$request_body         = '["reservation-uuid-2", "reservation-uuid-3"]';
		$wp_send_json_success = $this->mock_wp_send_json_success();
		$delete_map = [];
		add_action( 'tec_tickets_seating_delete_reservations_from_attendees', function ( $map ) use ( &$delete_map ) {
			$delete_map = $map;
		} );

		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_RESERVATIONS );

		$this->assertTrue( $wp_send_json_success->was_called_times_with( 1,
			[
				'numberDeleted' => 2,
			],
		) );
		$this->assertEquals( $delete_map, [ 'reservation-uuid-2' => $attendee_2, 'reservation-uuid-3' => $attendee_3 ] );
		$this->assertEquals('', get_post_meta( $attendee_2, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals('', get_post_meta( $attendee_3, Meta::META_KEY_RESERVATION_ID, true ) );
	}
}
