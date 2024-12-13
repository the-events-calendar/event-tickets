<?php

namespace TEC\Tickets\Seating\Admin;

use PHPUnit\Framework\Assert;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Service\Layouts;
use TEC\Tickets\Seating\Service\Layouts as Layouts_Service;
use TEC\Tickets\Seating\Service\Maps as Maps_Service;
use TEC\Tickets\Seating\Service\OAuth_Token;
use TEC\Tickets\Seating\Service\Reservations;
use TEC\Tickets\Seating\Service\Seat_Types;
use TEC\Tickets\Seating\Tables\Layouts as Layouts_Table;
use TEC\Tickets\Seating\Tables\Maps;
use TEC\Tickets\Seating\Tables\Seat_Types as Seat_Types_Table;
use TEC\Tickets\Seating\Tables\Sessions;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tests\Traits\WP_Remote_Mocks;
use Tribe\Tests\Traits\WP_Send_Json_Mocks;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Reservations_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Tickets__Global_Stock as Global_Stock;
use TEC\Common\StellarWP\Assets\Assets;

class Ajax_Test extends Controller_Test_Case {
	use SnapshotAssertions;
	use With_Uopz;
	use OAuth_Token;
	use WP_Remote_Mocks;
	use Reservations_Maker;
	use WP_Send_JSON_Mocks;
	use Ticket_Maker;
	use Attendee_Maker;
	use Order_Maker;
	use With_Tickets_Commerce;

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
	 * @after
	 */
	public function truncate_tables(): void {
		Maps::truncate();
		Seat_Types_Table::truncate();
		Layouts_Table::truncate();
		Sessions::truncate();
	}

	public function asset_data_provider() {
		$assets = [
			'tec-tickets-seating-ajax' => '/build/Seating/ajax.js',
		];

		foreach ( $assets as $slug => $path ) {
			yield $slug => [ $slug, $path ];
		}
	}

	/**
	 * @test
	 * @dataProvider asset_data_provider
	 */
	public function it_should_locate_assets_where_expected( $slug, $path ) {
		$this->make_controller()->register();

		$this->assertTrue( Assets::init()->exists( $slug ) );

		// We use false, because in CI mode the assets are not build so min aren't available. Its enough to check that the non-min is as expected.
		$asset_url = Assets::init()->get( $slug )->get_url( false );
		$this->assertEquals( plugins_url( $path, EVENT_TICKETS_MAIN_PLUGIN_FILE ), $asset_url );
	}

	public function test_get_localized_data(): void {
		$this->set_fn_return( 'wp_create_nonce', '88b1a4b166' );
		$controller = $this->make_controller();

		$this->assertMatchesJsonSnapshot( wp_json_encode( $controller->get_ajax_data(), JSON_SNAPSHOT_OPTIONS ) );
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

		codecept_debug( $wp_send_json_success->get_calls_as_string() );
		$this->assertTrue(
			$wp_send_json_success->was_called_times_with(
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

	private function set_up_ajax_request_context( int $user_id = null ): int {
		if ( null === $user_id ) {
			$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
			wp_set_current_user( $user_id );
		}
		$_REQUEST['action']      = Ajax::NONCE_ACTION;
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Ajax::NONCE_ACTION );

		return $user_id;
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
				[
					'id'            => 'some-map-4',
					'name'          => 'Some Map 4 without any layout',
					'seats'         => 40,
					'screenshotUrl' => 'https://example.com/some-map-4.png',
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

	public function test_invalidate_maps_layouts_cache(): void {
		$this->set_up_ajax_request_context();
		$this->given_maps_layouts_and_seat_types_in_db();

		$this->make_controller()->register();

		// Layouts invalidation fail.
		$this->set_class_fn_return( Layouts_Service::class, 'invalidate_cache', false );
		$mock_wp_send_json_error = $this->mock_wp_send_json_error();
		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE );
		$this->assertTrue(
			$mock_wp_send_json_error->was_called_times_with(
				1,
				[ 'error' => 'Failed to invalidate the layouts cache.' ],
				500
			),
			$mock_wp_send_json_error->get_calls_as_string()
		);
		$this->assertCount( 4, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts_Table::fetch_all() ) );
		$this->assertCount( 4, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->reset_wp_send_json_mocks();

		// Maps invalidation fail.
		$this->set_class_fn_return(
			Layouts_Service::class,
			'invalidate_cache',
			function (): bool {
				return Layouts_Service::invalidate_cache();
			},
			true
		);
		$this->set_class_fn_return( Maps_Service::class, 'invalidate_cache', false );
		$mock_wp_send_json_error = $this->mock_wp_send_json_error();
		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE );
		$this->assertTrue(
			$mock_wp_send_json_error->was_called_times_with(
				1,
				[ 'error' => 'Failed to invalidate the maps layouts cache.' ],
				500
			),
			$mock_wp_send_json_error->get_calls_as_string()
		);
		$this->assertCount( 4, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Layouts_Table::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->reset_wp_send_json_mocks();

		// All good.
		$this->set_class_fn_return(
			Layouts_Service::class,
			'invalidate_cache',
			function (): bool {
				return Layouts_Service::invalidate_cache();
			},
			true
		);
		$this->set_class_fn_return(
			Maps_Service::class,
			'invalidate_cache',
			function (): bool {
				return Maps_Service::invalidate_cache();
			},
			true
		);
		$mock_wp_send_json_success = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE );
		$this->assertTrue(
			$mock_wp_send_json_success->was_called_times_with( 1, [] ),
			$mock_wp_send_json_success->get_calls_as_string()
		);
		$this->assertCount( 0, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Layouts_Table::fetch_all() ) );
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
			$mock_wp_send_json_error->was_called_times_with(
				1,
				[ 'error' => 'Failed to invalidate the layouts cache.' ],
				500
			),
			$mock_wp_send_json_error->get_calls_as_string()
		);
		$this->assertCount( 4, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts_Table::fetch_all() ) );
		$this->assertCount( 4, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->reset_wp_send_json_mocks();

		// All good.
		$this->set_class_fn_return(
			Layouts_Service::class,
			'invalidate_cache',
			function (): bool {
				return Layouts_Service::invalidate_cache();
			},
			true
		);
		$mock_wp_send_json_success = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_INVALIDATE_LAYOUTS_CACHE );
		$this->assertTrue(
			$mock_wp_send_json_success->was_called_times_with( 1, [] ),
			$mock_wp_send_json_success->get_calls_as_string()
		);
		$this->assertCount( 4, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Layouts_Table::fetch_all() ) );
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
			$wp_send_json_error->was_called_times_with(
				1,
				[ 'error' => 'No map ID provided' ],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->assertCount( 4, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts_Table::fetch_all() ) );
		$this->assertCount( 4, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->reset_wp_send_json_mocks();

		// Map deletion attempt fails for map with layouts.
		$_REQUEST['mapId']  = 'some-map-1';
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_MAP );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[ 'error' => 'Failed to delete the map.' ],
				500
			),
			$wp_send_json_error->get_calls_as_string()
		);

		$this->assertCount( 4, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts_Table::fetch_all() ) );
		$this->assertCount( 4, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->reset_wp_send_json_mocks();

		// Map deletion from service fails.
		$_REQUEST['mapId']  = 'some-map-4';
		$wp_send_json_error = $this->mock_wp_send_json_error();
		$wp_remote          = $this->mock_wp_remote(
			'delete',
			$maps_service->get_delete_url( 'some-map-4' ),
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
			$wp_send_json_error->was_called_times_with(
				1,
				[ 'error' => 'Failed to delete the map.' ],
				500
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->assertCount( 4, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts_Table::fetch_all() ) );
		$this->assertCount( 4, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->assertTrue( $wp_remote->was_called() );
		$this->reset_wp_send_json_mocks();
		$wp_remote->tear_down();

		// Map deletion succeeds.
		$_REQUEST['mapId']    = 'some-map-4';
		$wp_send_json_success = $this->mock_wp_send_json_success();
		$wp_remote            = $this->mock_wp_remote(
			'delete',
			$maps_service->get_delete_url( 'some-map-4' ),
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
		$this->assertCount( 0, iterator_to_array( Layouts_Table::fetch_all() ) );
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
			$wp_send_json_error->was_called_times_with(
				1,
				[ 'error' => 'No layout ID or map ID provided' ],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->assertCount( 4, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts_Table::fetch_all() ) );
		$this->assertCount( 4, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->reset_wp_send_json_mocks();

		// Map ID is missing from request context.
		$_REQUEST['layoutId'] = 'some-layout-1';
		unset( $_REQUEST['mapId'] );
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_LAYOUT );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[ 'error' => 'No layout ID or map ID provided' ],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->assertCount( 4, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts_Table::fetch_all() ) );
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
			$wp_send_json_error->was_called_times_with(
				1,
				[ 'error' => 'Failed to delete the layout.' ],
				500
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->assertCount( 4, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts_Table::fetch_all() ) );
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
		$this->assertCount( 0, iterator_to_array( Layouts_Table::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->reset_wp_send_json_mocks();
	}

	public function test_update_reservations(): void {
		$this->set_up_ajax_request_context( 0 );
		$request_body = null;
		$this->set_fn_return(
			'file_get_contents',
			function ( $file, ...$args ) use ( &$request_body ) {
				if ( $file !== 'php://input' ) {
					return file_get_contents( $file, ...$args );
				}

				return $request_body;
			},
			true
		);
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
			$wp_send_json_error->was_called_times_with(
				1,
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
			$wp_send_json_error->was_called_times_with(
				1,
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
			$wp_send_json_error->was_called_times_with(
				1,
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
			$wp_send_json_error->was_called_times_with(
				1,
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
			$wp_send_json_error->was_called_times_with(
				1,
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
			$wp_send_json_error->was_called_times_with(
				1,
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
			$wp_send_json_error->was_called_times_with(
				1,
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
				'reservations' => array_merge(
					$reservations_data,
					[
						[
							89 => [
								'reservationId' => 'some-reservation-id',
								'seatTypeId'    => 'some-seat-type-id',
							],
						],
					]
				),
			]
		);
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_nopriv_' . Ajax::ACTION_POST_RESERVATIONS );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
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
				'DELETE FROM %i WHERE token = %s',
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
			$wp_send_json_error->was_called_times_with(
				1,
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
		$this->assertEquals(
			[
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
			],
			$sessions->get_reservations_for_token( 'some-token' )
		);
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
			$wp_send_json_error->was_called_times_with(
				1,
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
			$wp_send_json_error->was_called_times_with(
				1,
				[ 'error' => 'Invalid request parameters' ],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Reservations cancellation fails.
		$_REQUEST['postId'] = $post_id;
		$_REQUEST['token']  = 'some-token';
		$reservations       = tribe( Reservations::class );
		$wp_remote          = $this->mock_wp_remote(
			'post',
			$reservations->get_cancel_url(),
			[
				'headers' => [
					'Authorization' => 'Bearer some-token',
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'eventId' => 'some-post-uuid',
						'ids'     => $sessions->get_reservation_uuids_for_token( 'some-token' ),
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
			$wp_send_json_error->was_called_times_with(
				1,
				[ 'error' => 'Failed to clear the reservations' ],
				500
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Reservation cancellation succeeds.
		$_REQUEST['postId']   = $post_id;
		$_REQUEST['token']    = 'some-token';
		$reservations         = tribe( Reservations::class );
		$wp_remote            = $this->mock_wp_remote(
			'post',
			$reservations->get_cancel_url(),
			[
				'headers' => [
					'Authorization' => 'Bearer some-token',
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'eventId' => 'some-post-uuid',
						'ids'     => $sessions->get_reservation_uuids_for_token( 'some-token' ),
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
		$this->assertEmpty( $sessions->get_reservation_uuids_for_token( 'some-token' ) );

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
		$post_id                                  = static::factory()->post->create();
		$ticket_id                                = $this->create_tc_ticket( $post_id, 10 );
		[ $attendee_1, $attendee_2, $attendee_3 ] = $this->create_many_attendees_for_ticket( 3, $ticket_id, $post_id );
		update_post_meta( $attendee_1, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-1' );
		update_post_meta( $attendee_2, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-2' );
		update_post_meta( $attendee_3, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-3' );
		$this->set_oauth_token( 'auth-token' );
		$request_body = null;
		$this->set_fn_return(
			'file_get_contents',
			function ( $file, ...$args ) use ( &$request_body ) {
				if ( $file !== 'php://input' ) {
					return file_get_contents( $file, ...$args );
				}

				return $request_body;
			},
			true
		);

		$controller = $this->make_controller();
		$controller->register();

		// Request body is empty.
		$request_body       = '';
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_RESERVATIONS );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'Invalid request body',
				],
				400
			)
		);
		$this->reset_wp_send_json_mocks();

		// Request body is not valid JSON.
		$request_body       = 'not-json';
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_RESERVATIONS );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'Invalid request body',
				],
				400
			)
		);
		$this->reset_wp_send_json_mocks();

		// Request body is valid JSON but not an array of non-empty strings.
		$request_body       = '["", ""]';
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_RESERVATIONS );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'Invalid request body',
				],
				400
			)
		);
		$this->reset_wp_send_json_mocks();

		// Deletion succeeds.
		$request_body         = '["reservation-uuid-1", "reservation-uuid-4"]';
		$wp_send_json_success = $this->mock_wp_send_json_success();
		$delete_map           = [];
		add_action(
			'tec_tickets_seating_delete_reservations_from_attendees',
			function ( $map ) use ( &$delete_map ) {
				$delete_map = $map;
			}
		);

		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_RESERVATIONS );

		$this->assertTrue(
			$wp_send_json_success->was_called_times_with(
				1,
				[
					'numberDeleted' => 1,
				],
			)
		);
		$this->reset_wp_send_json_mocks();
		$this->assertEquals( $delete_map, [ 'reservation-uuid-1' => $attendee_1 ] );
		$this->assertEquals( '', get_post_meta( $attendee_1, Meta::META_KEY_RESERVATION_ID, true ) );

		// Send a second request to delete the rest.
		$request_body         = '["reservation-uuid-2", "reservation-uuid-3"]';
		$wp_send_json_success = $this->mock_wp_send_json_success();
		$delete_map           = [];
		add_action(
			'tec_tickets_seating_delete_reservations_from_attendees',
			function ( $map ) use ( &$delete_map ) {
				$delete_map = $map;
			}
		);

		do_action( 'wp_ajax_' . Ajax::ACTION_DELETE_RESERVATIONS );

		$this->assertTrue(
			$wp_send_json_success->was_called_times_with(
				1,
				[
					'numberDeleted' => 2,
				],
			)
		);
		$this->assertEquals(
			$delete_map,
			[
				'reservation-uuid-2' => $attendee_2,
				'reservation-uuid-3' => $attendee_3,
			]
		);
		$this->assertEquals( '', get_post_meta( $attendee_2, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( '', get_post_meta( $attendee_3, Meta::META_KEY_RESERVATION_ID, true ) );
	}

	public function test_update_seat_types(): void {
		// Create the layouts.
		Layouts::insert_rows_from_service(
			[
				[
					'id'            => 'layout-uuid-1',
					'name'          => 'Layout 1',
					'seats'         => 40,
					'mapId'         => 'map-uuid-1',
					'screenshotUrl' => 'https://example.com/layout-1.png',
				],
				[
					'id'            => 'layout-uuid-2',
					'name'          => 'Layout 2',
					'seats'         => 20,
					'mapId'         => 'map-uuid-1',
					'screenshotUrl' => 'https://example.com/layout-2.png',
				],
			]
		);
		// Create the seat types.
		Seat_Types::insert_rows_from_service(
			[
				[
					'id'       => 'seat-type-uuid-1',
					'name'     => 'Seat Type 1',
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-1',
					'seats'    => 10,
				],
				[
					'id'       => 'seat-type-uuid-2',
					'name'     => 'Seat Type 2',
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-1',
					'seats'    => 30,
				],
				[
					'id'       => 'seat-type-uuid-3',
					'name'     => 'Seat Type 3',
					'mapId'    => 'map-uuid-2',
					'layoutId' => 'layout-uuid-2',
					'seats'    => 20,
				],
			]
		);
		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler   = tribe( 'tickets.handler' );
		$capacity_meta_key = $tickets_handler->key_capacity;
		// Create 2 tickets, each one using one of the seat types.
		$post_1 = self::factory()->post->create();
		update_post_meta( $post_1, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_1, $capacity_meta_key, 40 );
		update_post_meta( $post_1, Global_Stock::GLOBAL_STOCK_LEVEL, 40 );
		$post_1_ticket_1 = $this->create_tc_ticket( $post_1, 10 );
		update_post_meta( $post_1_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		$post_1_ticket_2 = $this->create_tc_ticket( $post_1, 20 );
		update_post_meta( $post_1_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		$post_1_ticket_3 = $this->create_tc_ticket( $post_1, 30 );
		update_post_meta( $post_1_ticket_3, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		$post_1_ticket_4 = $this->create_tc_ticket( $post_1, 40 );
		update_post_meta( $post_1_ticket_4, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		$post_2 = self::factory()->post->create();
		update_post_meta( $post_2, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-2' );
		update_post_meta( $post_2, $capacity_meta_key, 20 );
		update_post_meta( $post_2, Global_Stock::GLOBAL_STOCK_LEVEL, 20 );
		$post_2_ticket_1 = $this->create_tc_ticket( $post_2, 30 );
		update_post_meta( $post_2_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-3' );
		$post_2_ticket_2 = $this->create_tc_ticket( $post_2, 40 );
		update_post_meta( $post_2_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-3' );
		// Create 2 Attendees for each ticket.
		[ $post_1_attendee_1, $post_1_attendee_2 ] = $this->create_many_attendees_for_ticket(
			2,
			$post_1_ticket_1,
			$post_1
		);
		update_post_meta( $post_1_attendee_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_1_attendee_1, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-1' );
		update_post_meta( $post_1_attendee_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_1_attendee_2, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-2' );
		[ $post_1_attendee_3, $post_1_attendee_4 ] = $this->create_many_attendees_for_ticket(
			2,
			$post_1_ticket_2,
			$post_1
		);
		update_post_meta( $post_1_attendee_3, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_1_attendee_3, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-3' );
		update_post_meta( $post_1_attendee_4, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_1_attendee_4, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-4' );
		[ $post_1_attendee_5, $post_1_attendee_6 ] = $this->create_many_attendees_for_ticket(
			2,
			$post_1_ticket_3,
			$post_1
		);
		update_post_meta( $post_1_attendee_5, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_1_attendee_5, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-5' );
		update_post_meta( $post_1_attendee_6, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_1_attendee_6, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-6' );
		[ $post_1_attendee_7, $post_1_attendee_8 ] = $this->create_many_attendees_for_ticket(
			2,
			$post_1_ticket_4,
			$post_1
		);
		update_post_meta( $post_1_attendee_7, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_1_attendee_7, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-7' );
		update_post_meta( $post_1_attendee_8, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_1_attendee_8, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-8' );
		[ $post_2_attendee_1, $post_2_attendee_2 ] = $this->create_many_attendees_for_ticket(
			2,
			$post_2_ticket_1,
			$post_2
		);
		update_post_meta( $post_2_attendee_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-3' );
		update_post_meta( $post_2_attendee_1, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-9' );
		update_post_meta( $post_2_attendee_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-3' );
		update_post_meta( $post_2_attendee_2, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-10' );
		[ $post_2_attendee_3, $post_2_attendee_4 ] = $this->create_many_attendees_for_ticket(
			2,
			$post_2_ticket_2,
			$post_2
		);
		update_post_meta( $post_2_attendee_3, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-3' );
		update_post_meta( $post_2_attendee_3, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-11' );
		update_post_meta( $post_2_attendee_4, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-3' );
		update_post_meta( $post_2_attendee_4, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-12' );
		$this->set_up_ajax_request_context();
		$this->set_oauth_token( 'auth-token' );
		$request_body = null;
		$this->set_fn_return(
			'file_get_contents',
			function ( $file, ...$args ) use ( &$request_body ) {
				if ( $file !== 'php://input' ) {
					return file_get_contents( $file, ...$args );
				}

				return $request_body;
			},
			true
		);

		$this->make_controller()->register();

		// Empty request body.
		$request_body       = '';
		$wp_send_json_error = $this->mock_wp_send_json_error();
		do_action( 'wp_ajax_' . Ajax::ACTION_SEAT_TYPES_UPDATED );
		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'Invalid request body',
				],
				400
			)
		);
		$this->reset_wp_send_json_mocks();

		// Request body is not valid JSON.
		$request_body       = 'not-json';
		$wp_send_json_error = $this->mock_wp_send_json_error();
		do_action( 'wp_ajax_' . Ajax::ACTION_SEAT_TYPES_UPDATED );
		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'Invalid request body',
				],
				400
			)
		);
		$this->reset_wp_send_json_mocks();

		// Request body is valid JSON, but it's an empty array.
		$request_body       = '{}';
		$wp_send_json_error = $this->mock_wp_send_json_error();
		do_action( 'wp_ajax_' . Ajax::ACTION_SEAT_TYPES_UPDATED );
		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'Invalid request body',
				],
				400
			)
		);
		$this->reset_wp_send_json_mocks();

		// Request body does not contain the required fields.
		$request_body       = '[{"id": "some-seat-type-1", "name": "Some Seat Type 1", "mapId": "some-map-id"}]';
		$wp_send_json_error = $this->mock_wp_send_json_error();
		do_action( 'wp_ajax_' . Ajax::ACTION_SEAT_TYPES_UPDATED );
		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'Invalid request body',
				],
				400
			)
		);
		$this->reset_wp_send_json_mocks();

		$valid_payload = [
			[
				'id'          => 'seat-type-uuid-1',
				'name'        => 'Updated Seat Type 1',
				'mapId'       => 'map-uuid-1',
				'layoutId'    => 'layout-uuid-1',
				'description' => 'Updated Seat Type 1 description',
				'seatsCount'  => 23,
			],
			[
				'id'          => 'seat-type-uuid-2',
				'name'        => 'Updated Seat Type 2',
				'mapId'       => 'map-uuid-1',
				'layoutId'    => 'layout-uuid-1',
				'description' => 'Updated Seat Type 2 description',
				'seatsCount'  => 89,
			],
		];

		// Seat types update from service fails.
		global $wpdb;
		$wpdb->suppress_errors  = true; // We know we're going to get an error, no need to pollute the output.
		$failing_query_callback = function ( string $query ) use ( &$failing_query_callback ) {
			if ( preg_match( '/UPDATE.*' . preg_quote( Seat_Types_Table::table_name(), '/' ) . '/i', $query ) ) {
				return 'BORKED';
			}

			return $query;
		};
		add_filter( 'query', $failing_query_callback );
		$request_body       = wp_json_encode( $valid_payload );
		$wp_send_json_error = $this->mock_wp_send_json_error();
		do_action( 'wp_ajax_' . Ajax::ACTION_SEAT_TYPES_UPDATED );
		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'Failed to update the seat types from the service.',
				],
				500
			)
		);
		$this->reset_wp_send_json_mocks();
		remove_filter( 'query', $failing_query_callback );

		// Seat types update succeeds.
		$request_body         = wp_json_encode( $valid_payload );
		$wp_send_json_success = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_SEAT_TYPES_UPDATED );
		$this->assertTrue(
			$wp_send_json_success->was_called_times_with(
				1,
				[
					'updatedSeatTypes' => 2,
					'updatedTickets'   => 4,
					'updatedPosts'     => 1,
				]
			),
			$wp_send_json_success->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();
	}

	public function test_update_reservations_from_seat_types(): void {
		$this->set_up_ajax_request_context();
		$this->set_oauth_token( 'auth-token' );
		// Create the seat types.
		Seat_Types::insert_rows_from_service(
			[
				[
					'id'       => 'seat-type-uuid-1',
					'name'     => 'Seat Type 1',
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-1',
					'seats'    => 10,
				],
				[
					'id'       => 'seat-type-uuid-2',
					'name'     => 'Seat Type 2',
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-1',
					'seats'    => 30,
				],
				[
					'id'       => 'seat-type-uuid-3',
					'name'     => 'Seat Type 3',
					'mapId'    => 'map-uuid-2',
					'layoutId' => 'layout-uuid-2',
					'seats'    => 20,
				],
			]
		);
		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler   = tribe( 'tickets.handler' );
		$capacity_meta_key = $tickets_handler->key_capacity;
		// Create 2 tickets, each one using one of the seat types.
		$post_1 = self::factory()->post->create();
		update_post_meta( $post_1, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_1, $capacity_meta_key, 40 );
		update_post_meta( $post_1, Global_Stock::GLOBAL_STOCK_LEVEL, 40 );
		$post_1_ticket_1 = $this->create_tc_ticket( $post_1, 10 );
		update_post_meta( $post_1_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		$post_1_ticket_2 = $this->create_tc_ticket( $post_1, 20 );
		update_post_meta( $post_1_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		$post_1_ticket_3 = $this->create_tc_ticket( $post_1, 30 );
		update_post_meta( $post_1_ticket_3, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		$post_1_ticket_4 = $this->create_tc_ticket( $post_1, 40 );
		update_post_meta( $post_1_ticket_4, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		// Create 2 Attendees for each ticket.
		[ $attendee_1, $attendee_2 ] = $this->create_many_attendees_for_ticket( 2, $post_1_ticket_1, $post_1 );
		update_post_meta( $attendee_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $attendee_1, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-1' );
		update_post_meta( $attendee_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $attendee_2, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-2' );
		[ $attendee_3, $attendee_4 ] = $this->create_many_attendees_for_ticket( 2, $post_1_ticket_2, $post_1 );
		update_post_meta( $attendee_3, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $attendee_3, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-3' );
		update_post_meta( $attendee_4, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $attendee_4, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-4' );
		[ $attendee_5, $attendee_6 ] = $this->create_many_attendees_for_ticket( 2, $post_1_ticket_3, $post_1 );
		update_post_meta( $attendee_5, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $attendee_5, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-5' );
		update_post_meta( $attendee_6, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $attendee_6, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-6' );
		[ $attendee_7, $attendee_8 ] = $this->create_many_attendees_for_ticket( 2, $post_1_ticket_4, $post_1 );
		update_post_meta( $attendee_7, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $attendee_7, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-7' );
		update_post_meta( $attendee_8, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $attendee_8, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-8' );
		$request_body = null;
		$this->set_fn_return(
			'file_get_contents',
			function ( $file, ...$args ) use ( &$request_body ) {
				if ( $file !== 'php://input' ) {
					return file_get_contents( $file, ...$args );
				}

				return $request_body;
			},
			true
		);

		$this->make_controller()->register();

		// Empty request body.
		$request_body       = '';
		$wp_send_json_error = $this->mock_wp_send_json_error();
		do_action( 'wp_ajax_' . Ajax::ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES );
		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'Invalid request body',
				],
				400
			)
		);
		$this->reset_wp_send_json_mocks();

		// Request body is not valid JSON.
		$request_body       = 'not-json';
		$wp_send_json_error = $this->mock_wp_send_json_error();
		do_action( 'wp_ajax_' . Ajax::ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES );
		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'Invalid request body',
				],
				400
			)
		);
		$this->reset_wp_send_json_mocks();

		// Request body is valid JSON, but it's an empty array.
		$request_body       = '{}';
		$wp_send_json_error = $this->mock_wp_send_json_error();
		do_action( 'wp_ajax_' . Ajax::ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES );
		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'Invalid request body',
				],
				400
			)
		);
		$this->reset_wp_send_json_mocks();

		// Attendees update succeeds: reservations are moved from seat type 1 to 2.
		$request_body         = wp_json_encode(
			[
				'seat-type-uuid-1' => [
					'reservation-uuid-5',
					'reservation-uuid-6',
					'reservation-uuid-7',
					'reservation-uuid-8',
				],
			]
		);
		$wp_send_json_success = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES );
		$this->assertTrue(
			$wp_send_json_success->was_called_times_with(
				1,
				[
					'updatedAttendees' => 4,
				],
			),
			$wp_send_json_success->get_calls_as_string()
		);
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $attendee_1, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'reservation-uuid-1', get_post_meta( $attendee_1, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $attendee_2, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'reservation-uuid-2', get_post_meta( $attendee_2, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $attendee_3, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'reservation-uuid-3', get_post_meta( $attendee_3, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $attendee_4, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'reservation-uuid-4', get_post_meta( $attendee_4, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $attendee_5, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'reservation-uuid-5', get_post_meta( $attendee_5, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $attendee_6, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'reservation-uuid-6', get_post_meta( $attendee_6, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $attendee_7, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'reservation-uuid-7', get_post_meta( $attendee_7, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $attendee_8, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'reservation-uuid-8', get_post_meta( $attendee_8, Meta::META_KEY_RESERVATION_ID, true ) );
	}

	/**
	 * @covers Ajax::add_new_layout_to_service
	 */
	public function test_add_new_layout_from_service(): void {
		$this->set_up_ajax_request_context();
		$this->given_maps_layouts_and_seat_types_in_db();
		$layouts_service = $this->test_services->get( Layouts_Service::class );
		$this->set_oauth_token( 'some-token' );

		$controller = $this->make_controller();
		$controller->register();

		// Map ID is missing from request context.
		unset( $_REQUEST['mapId'] );

		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_' . Ajax::ACTION_ADD_NEW_LAYOUT );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[ 'error' => 'No map ID provided' ],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->assertCount( 4, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts_Table::fetch_all() ) );
		$this->reset_wp_send_json_mocks();

		// Add new layout from service fails.
		$_REQUEST['mapId'] = 'some-map-1';

		$wp_send_json_error = $this->mock_wp_send_json_error();
		$wp_remote          = $this->mock_wp_remote(
			'request',
			$layouts_service->get_add_url( 'some-map-1' ),
			[
				'method'  => 'POST',
				'headers' => [
					'Authorization' => 'Bearer some-token',
					'Content-Type'  => 'application/json',
				],
			],
			function () {
				return [
					'response' => [
						'code' => 500,
					],
					'body'     => wp_json_encode(
						[
							'success' => false,
						]
					),
				];
			}
		);

		do_action( 'wp_ajax_' . Ajax::ACTION_ADD_NEW_LAYOUT );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[ 'error' => 'Failed to Add new layout.' ],
				500
			),
			$wp_send_json_error->get_calls_as_string()
		);

		$this->assertCount( 4, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts_Table::fetch_all() ) );
		$this->assertTrue( $wp_remote->was_called() );
		$this->reset_wp_send_json_mocks();
		$wp_remote->tear_down();

		// Add new layout succeeds.
		$_REQUEST['mapId']    = 'some-map-1';
		$wp_send_json_success = $this->mock_wp_send_json_success();
		$wp_remote            = $this->mock_wp_remote(
			'request',
			$layouts_service->get_add_url( 'some-map-1' ),
			[
				'method'  => 'POST',
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
							'data' => [
								'items' => [
									[ 'id' => 'new-layout-1' ],
								],
							],
						]
					),
				];
			}
		);

		do_action( 'wp_ajax_' . Ajax::ACTION_ADD_NEW_LAYOUT );

		$this->assertTrue(
			$wp_send_json_success->was_called_times_with(
				1,
				'http://wordpress.test/wp-admin/admin.php?page=tec-tickets-seating&tab=layout-edit&layoutId=new-layout-1&isNew=1',
			)
		);
		$this->assertCount( 0, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Layouts_Table::fetch_all() ) );
		$this->assertTrue( $wp_remote->was_called() );
		$this->reset_wp_send_json_mocks();
		$wp_remote->tear_down();
	}

	/**
	 * @covers Ajax::duplicate_layout_in_service
	 */
	public function test_duplicate_layout_in_service(): void {
		$this->set_up_ajax_request_context();
		$this->given_maps_layouts_and_seat_types_in_db();
		$layouts_service = $this->test_services->get( Layouts_Service::class );
		$this->set_oauth_token( 'some-token' );

		$controller = $this->make_controller();
		$controller->register();

		// Layout ID is missing from request context.
		unset( $_REQUEST['layoutId'] );

		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_' . Ajax::ACTION_DUPLICATE_LAYOUT );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[ 'error' => 'No layout ID provided for duplication' ],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->assertCount( 4, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts_Table::fetch_all() ) );
		$this->reset_wp_send_json_mocks();

		$_REQUEST['layoutId'] = 'some-layout-2';

		$wp_send_json_error = $this->mock_wp_send_json_error();
		$wp_remote          = $this->mock_wp_remote(
			'request',
			$layouts_service->get_duplicate_url( $_REQUEST['layoutId'] ),
			[
				'method'  => 'POST',
				'headers' => [
					'Authorization' => 'Bearer some-token',
					'Content-Type'  => 'application/json',
				],
			],
			function () {
				return [
					'response' => [
						'code' => 500,
					],
					'body'     => wp_json_encode(
						[
							'success' => false,
						]
					),
				];
			}
		);

		do_action( 'wp_ajax_' . Ajax::ACTION_DUPLICATE_LAYOUT );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[ 'error' => 'Failed to duplicate layout.' ],
				500
			),
			$wp_send_json_error->get_calls_as_string()
		);

		$this->assertCount( 4, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts_Table::fetch_all() ) );
		$this->assertTrue( $wp_remote->was_called() );
		$this->reset_wp_send_json_mocks();
		$wp_remote->tear_down();

		// Duplicating layout succeeds.
		$_REQUEST['layoutId'] = 'some-layout-2';
		$wp_send_json_success = $this->mock_wp_send_json_success();
		$wp_remote            = $this->mock_wp_remote(
			'request',
			$layouts_service->get_duplicate_url( $_REQUEST['layoutId'] ),
			[
				'method'  => 'POST',
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
							'data' => [
								'items' => [
									[ 'id' => 'duplicated-layout-1' ],
								],
							],
						]
					),
				];
			}
		);

		do_action( 'wp_ajax_' . Ajax::ACTION_DUPLICATE_LAYOUT );

		$this->assertTrue(
			$wp_send_json_success->was_called_times_with(
				1,
				'http://wordpress.test/wp-admin/admin.php?page=tec-tickets-seating&tab=layout-edit&layoutId=duplicated-layout-1&isNew=1',
			)
		);
		$this->assertCount( 0, iterator_to_array( Maps::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Layouts_Table::fetch_all() ) );
		$this->assertTrue( $wp_remote->was_called() );
		$this->reset_wp_send_json_mocks();
		$wp_remote->tear_down();
	}

	public function test_remove_seat_type_from_layout() {
		Maps_Service::insert_rows_from_service(
			[
				[
					'id'            => 'some-map-1',
					'name'          => 'Some Map 1',
					'seats'         => 50,
					'screenshotUrl' => 'https://example.com/some-map-1.png',
				],
			]
		);
		set_transient( Maps_Service::update_transient_name(), time() );

		Layouts_Service::insert_rows_from_service(
			[
				[
					'id'            => 'some-layout-1',
					'name'          => 'Some Layout 1',
					'seats'         => 50,
					'createdDate'   => time() * 1000,
					'mapId'         => 'some-map-1',
					'screenshotUrl' => 'https://example.com/some-layouts-1.png',
				],
			]
		);
		set_transient( Layouts_Service::update_transient_name(), time() );

		Seat_Types_Table::insert_many(
			[
				[
					'id'     => 'some-seat-type-1',
					'name'   => 'Some Seat Type 1',
					'seats'  => 30,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
				[
					'id'     => 'some-seat-type-2',
					'name'   => 'Some Seat Type 2',
					'seats'  => 20,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
			]
		);
		set_transient( Seat_Types::update_transient_name(), time() );
		$this->set_up_ajax_request_context();

		// setup request body.
		$this->set_oauth_token( 'auth-token' );
		$request_body = null;
		$this->set_fn_return(
			'file_get_contents',
			function ( $file, ...$args ) use ( &$request_body ) {
				if ( $file !== 'php://input' ) {
					return file_get_contents( $file, ...$args );
				}

				return $request_body;
			},
			true
		);

		// Create event with associated layout and ticket and attendees.
		$post_id                                  = static::factory()->post->create();
		$ticket_id                                = $this->create_tc_ticket( $post_id, 10 );
		[ $attendee_1, $attendee_2, $attendee_3 ] = $this->create_many_attendees_for_ticket( 3, $ticket_id, $post_id );

		update_post_meta( $post_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-1' );

		update_post_meta( $ticket_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-1' );
		update_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE, 'some-seat-type-1' );

		foreach ( [ $attendee_1, $attendee_2, $attendee_3 ] as $key => $attendee ) {
			update_post_meta( $attendee, Meta::META_KEY_LAYOUT_ID, 'some-layout-1' );
			update_post_meta( $attendee, Meta::META_KEY_SEAT_TYPE, 'some-seat-type-1' );
			update_post_meta( $attendee, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'seat-label-' . $key );
		}

		$this->make_controller()->register();

		$request_body = wp_json_encode(
			[
				'deletedId'  => 'some-seat-type-1',
				'transferTo' => [
					'id'          => 'some-seat-type-2',
					'name'        => 'Some Seat Type 2',
					'mapId'       => 'some-map-1',
					'layoutId'    => 'some-layout-1',
					'description' => 'This is the new description.',
					'seatsCount'  => 50,
				],
			]
		);

		$wp_send_json_success = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_SEAT_TYPE_DELETED );

		$wp_send_json_success->was_called_times_with(
			1,
			[
				'updatedSeatTypes' => 1, // Replaced existing seats count for updated seat type.
				'updatedTickets'   => 1, // Number of Tickets updated.
				'updatedMeta'      => 4, // 3 attendees + 1 Ticket
			]
		);

		$this->assertEquals( 'some-seat-type-2', get_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'some-seat-type-2', get_post_meta( $attendee_1, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'some-seat-type-2', get_post_meta( $attendee_2, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'some-seat-type-2', get_post_meta( $attendee_3, Meta::META_KEY_SEAT_TYPE, true ) );
	}

	public function test_remove_seat_type_from_layout_updates_capacity_properly() {
		$this->make_controller()->register();

		Maps_Service::insert_rows_from_service(
			[
				[
					'id'            => 'some-map-1',
					'name'          => 'Some Map 1',
					'seats'         => 100,
					'screenshotUrl' => 'https://example.com/some-map-1.png',
				],
			]
		);
		set_transient( Maps_Service::update_transient_name(), time() );

		Layouts_Service::insert_rows_from_service(
			[
				[
					'id'            => 'some-layout-1',
					'name'          => 'Some Layout 1',
					'seats'         => 100,
					'createdDate'   => time() * 1000,
					'mapId'         => 'some-map-1',
					'screenshotUrl' => 'https://example.com/some-layouts-1.png',
				],
			]
		);
		set_transient( Layouts_Service::update_transient_name(), time() );

		Seat_Types_Table::insert_many(
			[
				[
					'id'     => 'some-seat-type-1',
					'name'   => 'Some Seat Type 1',
					'seats'  => 70,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
				[
					'id'     => 'some-seat-type-2',
					'name'   => 'Some Seat Type 2',
					'seats'  => 30,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
			]
		);
		set_transient( Seat_Types::update_transient_name(), time() );
		$this->set_up_ajax_request_context();

		// Setup request body.
		$this->set_oauth_token( 'auth-token' );
		$request_body = null;
		$this->set_fn_return(
			'file_get_contents',
			function ( $file, ...$args ) use ( &$request_body ) {
				if ( $file !== 'php://input' ) {
					return file_get_contents( $file, ...$args );
				}

				return $request_body;
			},
			true
		);

		// Create event with associated layout and ticket and attendees.
		$post_id = static::factory()->post->create();

		// Enable the global stock on the Event.
		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );

		// Set the Event global stock level to 100.
		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_LEVEL, 100 );

		update_post_meta( $post_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-1' );

		$ticket_id_1 = $this->create_tc_ticket(
			$post_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 70,
				],
			]
		);

		$ticket_id_2 = $this->create_tc_ticket(
			$post_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 30,
				],
			]
		);

		update_post_meta( $ticket_id_1, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_id_1, Meta::META_KEY_LAYOUT_ID, 'some-layout-1' );
		update_post_meta( $ticket_id_1, Meta::META_KEY_SEAT_TYPE, 'some-seat-type-1' );

		update_post_meta( $ticket_id_2, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_id_2, Meta::META_KEY_LAYOUT_ID, 'some-layout-1' );
		update_post_meta( $ticket_id_2, Meta::META_KEY_SEAT_TYPE, 'some-seat-type-2' );

		$ticket_1 = tribe( Module::class )->get_ticket( $post_id, $ticket_id_1 );
		$ticket_2 = tribe( Module::class )->get_ticket( $post_id, $ticket_id_2 );

		$this->assertEquals( 70, $ticket_1->capacity() );
		$this->assertEquals( 70, $ticket_1->stock() );
		$this->assertEquals( 70, $ticket_1->available() );
		$this->assertEquals( 70, $ticket_1->inventory() );

		$this->assertEquals( 30, $ticket_2->capacity() );
		$this->assertEquals( 30, $ticket_2->stock() );
		$this->assertEquals( 30, $ticket_2->available() );
		$this->assertEquals( 30, $ticket_2->inventory() );

		$global_stock = new Global_Stock( $post_id );

		$this->assertTrue( $global_stock->is_enabled(), 'Global stock should be enabled.' );
		$this->assertEquals( 100, tribe_get_event_capacity( $post_id ), 'Total Event capacity should be 100' );
		$this->assertEquals( 100, $global_stock->get_stock_level(), 'Global stock should be 100' );

		$order = $this->create_order(
			[
				$ticket_id_1 => 5,
				$ticket_id_2 => 5,
			]
		);
		
		$order_attendees = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		
		// Mock the reservation ID to do proper stock calculation.
		foreach ( $order_attendees as $key => $attendee ) {
			update_post_meta( $attendee['ID'], Meta::META_KEY_RESERVATION_ID, 'test-reservation-id-' . $key );
		}

		$ticket_1 = tribe( Module::class )->get_ticket( $post_id, $ticket_id_1 );
		$ticket_2 = tribe( Module::class )->get_ticket( $post_id, $ticket_id_2 );

		$this->assertEquals( 70, $ticket_1->capacity() );
		$this->assertEquals( 70 - 5, $ticket_1->stock() );
		$this->assertEquals( 70 - 5, $ticket_1->available() );
		$this->assertEquals( 70 - 5, $ticket_1->inventory() );

		$this->assertEquals( 30, $ticket_2->capacity() );
		$this->assertEquals( 30 - 5, $ticket_2->stock() );
		$this->assertEquals( 30 - 5, $ticket_2->available() );
		$this->assertEquals( 30 - 5, $ticket_2->inventory() );

		$this->assertEquals( 90, $global_stock->get_stock_level(), 'Global stock should be 90' );

		$request_body = wp_json_encode(
			[
				'deletedId'  => 'some-seat-type-1',
				'transferTo' => [
					'id'          => 'some-seat-type-2',
					'name'        => 'Some Seat Type 2',
					'mapId'       => 'some-map-1',
					'layoutId'    => 'some-layout-1',
					'description' => 'This is the new description.',
					'seatsCount'  => 100,
				],
			]
		);

		$wp_send_json_success = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_SEAT_TYPE_DELETED );

		$wp_send_json_success->was_called_times_with(
			1,
			[
				'updatedSeatTypes' => 1, // Replaced existing seats count for updated seat type.
				'updatedTickets'   => 1, // Number of Tickets updated.
				'updatedMeta'      => 12, // 10 attendees + 2 Ticket
			]
		);

		$this->assertEquals( 90, $global_stock->get_stock_level(), 'Global stock should be 90' );

		$ticket_1 = tribe( Module::class )->get_ticket( $post_id, $ticket_id_1 );
		$ticket_2 = tribe( Module::class )->get_ticket( $post_id, $ticket_id_2 );

		$this->assertEquals( 100, $ticket_1->capacity() );
		$this->assertEquals( 90, $ticket_1->stock() );
		$this->assertEquals( 90, $ticket_1->available() );
		$this->assertEquals( 90, $ticket_1->inventory() );

		$this->assertEquals( 100, $ticket_2->capacity() );
		$this->assertEquals( 90, $ticket_2->stock() );
		$this->assertEquals( 90, $ticket_2->available() );
		$this->assertEquals( 90, $ticket_2->inventory() );

		$counts = \Tribe__Tickets__Tickets::get_ticket_counts( $post_id );

		$this->assertMatchesJsonSnapshot( wp_json_encode( $counts, JSON_SNAPSHOT_OPTIONS ) );
	}

	/**
	 * @covers Ajax::update_event_layout
	 */
	public function test_changing_layout_for_events_updates_event_and_tickets_properly() {
		$this->make_controller()->register();

		Maps_Service::insert_rows_from_service(
			[
				[
					'id'            => 'some-map-1',
					'name'          => 'Some Map 1',
					'seats'         => 100,
					'screenshotUrl' => 'https://example.com/some-map-1.png',
				],
				[
					'id'            => 'some-map-2',
					'name'          => 'Some Map 2',
					'seats'         => 200,
					'screenshotUrl' => 'https://example.com/some-map-2.png',
				],
				[
					'id'            => 'some-map-3',
					'name'          => 'Some Map 3',
					'seats'         => 10,
					'screenshotUrl' => 'https://example.com/some-map-3.png',
				],
			]
		);
		set_transient( Maps_Service::update_transient_name(), time() );

		Layouts::insert_rows_from_service(
			[
				[
					'id'            => 'some-layout-1',
					'name'          => 'Some Layout 1',
					'seats'         => 100,
					'createdDate'   => time() * 1000,
					'mapId'         => 'some-map-1',
					'screenshotUrl' => 'https://example.com/some-layouts-1.png',
				],
				[
					'id'            => 'some-layout-2',
					'name'          => 'Some Layout 2',
					'seats'         => 200,
					'createdDate'   => time() * 1000,
					'mapId'         => 'some-map-2',
					'screenshotUrl' => 'https://example.com/some-layouts-2.png',
				],
				[
					'id'            => 'some-layout-3',
					'name'          => 'Some Layout 3',
					'seats'         => 10,
					'createdDate'   => time() * 1000,
					'mapId'         => 'some-map-3',
					'screenshotUrl' => 'https://example.com/some-layouts-3.png',
				],
			]
		);
		set_transient( Layouts_Service::update_transient_name(), time() );

		Seat_Types_Table::insert_many(
			[
				[
					'id'     => 'some-seat-type-1',
					'name'   => 'Some Seat Type 1',
					'seats'  => 70,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
				[
					'id'     => 'some-seat-type-2',
					'name'   => 'Some Seat Type 2',
					'seats'  => 30,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
				[
					'id'     => 'some-seat-type-3',
					'name'   => 'Some Seat Type 3',
					'seats'  => 50,
					'map'    => 'some-map-2',
					'layout' => 'some-layout-2',
				],
				[
					'id'     => 'some-seat-type-4',
					'name'   => 'Some Seat Type 4',
					'seats'  => 150,
					'map'    => 'some-map-2',
					'layout' => 'some-layout-2',
				],
				[
					'id'     => 'some-seat-type-5',
					'name'   => 'Some Seat Type 5',
					'seats'  => 3,
					'map'    => 'some-map-3',
					'layout' => 'some-layout-3',
				],
				[
					'id'     => 'some-seat-type-6',
					'name'   => 'Some Seat Type 6',
					'seats'  => 7,
					'map'    => 'some-map-3',
					'layout' => 'some-layout-3',
				],
			]
		);

		set_transient( Seat_Types::update_transient_name(), time() );
		$this->set_up_ajax_request_context();
		$this->reset_wp_send_json_mocks();

		// Setup request body.
		$this->set_oauth_token( 'auth-token' );

		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler   = tribe( 'tickets.handler' );
		$capacity_meta_key = $tickets_handler->key_capacity;

		// Create event with associated layout and ticket and attendees.
		$post_id = static::factory()->post->create();

		// Enable the global stock on the Event.
		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );

		// set capacity as per layout 1.
		update_post_meta( $post_id, $capacity_meta_key, 100 );
		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_LEVEL, 100 );

		update_post_meta( $post_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-1' );

		$ticket_id_1 = $this->create_tc_ticket(
			$post_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 70,
				],
			]
		);

		$ticket_id_2 = $this->create_tc_ticket(
			$post_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 30,
				],
			]
		);

		update_post_meta( $ticket_id_1, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_id_1, Meta::META_KEY_SEAT_TYPE, 'some-seat-type-1' );

		update_post_meta( $ticket_id_2, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_id_2, Meta::META_KEY_SEAT_TYPE, 'some-seat-type-2' );

		$ticket_1 = tribe( Module::class )->get_ticket( $post_id, $ticket_id_1 );
		$ticket_2 = tribe( Module::class )->get_ticket( $post_id, $ticket_id_2 );

		$this->assertEquals( 70, $ticket_1->capacity() );
		$this->assertEquals( 70, $ticket_1->stock() );
		$this->assertEquals( 70, $ticket_1->available() );
		$this->assertEquals( 70, $ticket_1->inventory() );

		$this->assertEquals( 30, $ticket_2->capacity() );
		$this->assertEquals( 30, $ticket_2->stock() );
		$this->assertEquals( 30, $ticket_2->available() );
		$this->assertEquals( 30, $ticket_2->inventory() );

		$global_stock = new Global_Stock( $post_id );

		$this->assertTrue( $global_stock->is_enabled(), 'Global stock should be enabled.' );
		$this->assertEquals( 100, tribe_get_event_capacity( $post_id ), 'Total Event capacity should be 100' );
		$this->assertEquals( 100, $global_stock->get_stock_level(), 'Global stock should be 100' );

		$order = $this->create_order(
			[
				$ticket_id_1 => 5,
				$ticket_id_2 => 5,
			]
		);
		
		$order_attendees = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		
		// Mock the reservation ID to do proper stock calculation.
		foreach ( $order_attendees as $key => $attendee ) {
			update_post_meta( $attendee['ID'], Meta::META_KEY_RESERVATION_ID, 'test-reservation-id-' . $key );
		}

		$_REQUEST['postId']    = $post_id;
		$_REQUEST['newLayout'] = 'some-layout-2';

		$wp_send_json_success = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_EVENT_LAYOUT_UPDATED );

		$wp_send_json_success->was_called_times_with(
			1,
			[
				'updatedTickets'   => 2, // Number of Tickets updated.
				'updatedAttendees' => 10, // Number of Attendees updated.
			]
		);

		// Post capacity should be updated to 200 to match the new layout 2.
		$this->assertEquals( 200, tribe_get_event_capacity( $post_id ), 'Total Event capacity should be 200' );
		$this->assertEquals( 200, $global_stock->get_stock_level(), 'Global stock should be 200' );

		$ticket_1 = tribe( Module::class )->get_ticket( $post_id, $ticket_id_1 );
		// Ticket 1 should have its seat type updated to 3 to match the new layout.
		$this->assertEquals( 'some-seat-type-3', get_post_meta( $ticket_id_1, Meta::META_KEY_SEAT_TYPE, true ) );

		// Ticket 1 should have its capacity updated to 50 to match the new default seat type 3
		$this->assertEquals( 50, $ticket_1->capacity() );
		// Updating ticket type removes all reservation data from attendees therefore the stock should return to full capacity.
		$this->assertEquals( 50, $ticket_1->stock() );
		$this->assertEquals( 50, $ticket_1->available() );
		$this->assertEquals( 50, $ticket_1->inventory() );

		$ticket_2 = tribe( Module::class )->get_ticket( $post_id, $ticket_id_2 );

		// Ticket 2 should have its seat type updated to 3 to match the new layout.
		$this->assertEquals( 'some-seat-type-3', get_post_meta( $ticket_id_2, Meta::META_KEY_SEAT_TYPE, true ) );

		// Ticket 2 should have its capacity updated to 50 to match the new default seat type 3.
		// Updating ticket type removes all reservation data from attendees therefore the stock should return to full capacity.
		$this->assertEquals( 50, $ticket_2->capacity() );
		$this->assertEquals( 50, $ticket_2->stock() );
		$this->assertEquals( 50, $ticket_2->available() );
		$this->assertEquals( 50, $ticket_2->inventory() );

		$attendees = tribe_attendees()->where( 'event', $post_id )->get_ids( true );

		foreach ( $attendees as $attendee_id ) {
			// Attendee should have its seat type updated to 3 to match the new layout.
			$this->assertEquals( 'some-seat-type-3', get_post_meta( $attendee_id, Meta::META_KEY_SEAT_TYPE, true ) );
			$this->assertEquals( '', get_post_meta( $attendee_id, Meta::META_KEY_RESERVATION_ID, true ) );
			$this->assertEquals( '', get_post_meta( $attendee_id, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ) );
		}

		// Let's change the layout again to some-layout-3 to test negative case.
		$this->reset_wp_send_json_mocks();
		$_REQUEST['newLayout'] = 'some-layout-3';
		$_REQUEST['postId']    = $post_id;

		$wp_send_json_success = $this->mock_wp_send_json_success();

		do_action( 'wp_ajax_' . Ajax::ACTION_EVENT_LAYOUT_UPDATED );

		$wp_send_json_success->was_called_times_with(
			1,
			[
				'updatedTickets'   => 2, // Number of Tickets updated.
				'updatedAttendees' => 10, // Number of Attendees updated.
			]
		);

		// Post capacity should be updated to 10 to match the new layout 3.
		$this->assertEquals( 10, tribe_get_event_capacity( $post_id ), 'Total Event capacity should be 10' );
		$this->assertEquals( 10, $global_stock->get_stock_level(), 'Global stock should be 10' );

		$ticket_1 = tribe( Module::class )->get_ticket( $post_id, $ticket_id_1 );
		// Ticket 1 should have its seat type updated to 5 to match the new layout.

		$this->assertEquals( 'some-seat-type-5', get_post_meta( $ticket_id_1, Meta::META_KEY_SEAT_TYPE, true ) );

		// Ticket 1 should have its capacity updated to 3 to match the new default seat type 5.
		// As layout is updated, the stock and capacity will be reset to initial state.
		$this->assertEquals( 3, $ticket_1->capacity() );
		$this->assertEquals( 3, $ticket_1->stock() );
		$this->assertEquals( 3, $ticket_1->available() );
		$this->assertEquals( 3, $ticket_1->inventory() );

		$ticket_2 = tribe( Module::class )->get_ticket( $post_id, $ticket_id_2 );

		// Ticket 2 should have its seat type updated to 5 to match the new layout.
		$this->assertEquals( 'some-seat-type-5', get_post_meta( $ticket_id_2, Meta::META_KEY_SEAT_TYPE, true ) );

		// Ticket 2 should have its capacity updated to 7 to match the new default seat type 5.
		// As layout is updated, the stock and capacity will be reset to initial state.
		$this->assertEquals( 3, $ticket_2->capacity() );
		$this->assertEquals( 3, $ticket_2->stock() );
		$this->assertEquals( 3, $ticket_2->available() );
		$this->assertEquals( 3, $ticket_2->inventory() );
	}

	public function test_update_event_layout_failures(): void {
		set_transient( Seat_Types::update_transient_name(), time() );
		$admin_user_id = $this->set_up_ajax_request_context();
		$this->set_oauth_token( 'auth-token' );
		$post_id = static::factory()->post->create();

		$this->make_controller()->register();

		// Missing post ID.
		unset( $_REQUEST['postId'] );
		$_REQUEST['newLayout'] = 'some-layout-1';
		$wp_send_json_error    = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_' . Ajax::ACTION_EVENT_LAYOUT_UPDATED );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'No layout ID or post ID provided',
				],
				400
			)
		);
		$this->reset_wp_send_json_mocks();

		// Missing layout ID.
		unset( $_REQUEST['newLayout'] );
		$_REQUEST['postId'] = $post_id;
		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_' . Ajax::ACTION_EVENT_LAYOUT_UPDATED );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'No layout ID or post ID provided',
				],
				400
			)
		);
		$this->reset_wp_send_json_mocks();

		// User cannot edit post.
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$_REQUEST['postId']    = $post_id;
		$_REQUEST['newLayout'] = 'some-layout-1';
		$wp_send_json_error    = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_' . Ajax::ACTION_EVENT_LAYOUT_UPDATED );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'User has no permission.',
				],
				403
			)
		);
		$this->reset_wp_send_json_mocks();
		wp_set_current_user( $admin_user_id );

		// No such layout in the DB.
		$_REQUEST['postId']    = $post_id;
		$_REQUEST['newLayout'] = 'some-layout-1';
		$wp_send_json_error    = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_' . Ajax::ACTION_EVENT_LAYOUT_UPDATED );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'Invalid layout ID',
				],
				400
			)
		);

		// No primary seat type found for the layout.
		Maps_Service::insert_rows_from_service(
			[
				[
					'id'            => 'some-map-1',
					'name'          => 'Some Map 1',
					'seats'         => 100,
					'screenshotUrl' => 'https://example.com/some-map-1.png',
				],
			]
		);
		set_transient( Maps_Service::update_transient_name(), time() );

		Layouts::insert_rows_from_service(
			[
				[
					'id'            => 'some-layout-1',
					'name'          => 'Some Layout 1',
					'seats'         => 100,
					'createdDate'   => time() * 1000,
					'mapId'         => 'some-map-1',
					'screenshotUrl' => 'https://example.com/some-layouts-1.png',
				],
			]
		);
		set_transient( Layouts_Service::update_transient_name(), time() );

		$wp_send_json_error = $this->mock_wp_send_json_error();

		do_action( 'wp_ajax_' . Ajax::ACTION_EVENT_LAYOUT_UPDATED );

		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'No primary seat type found for the layout.',
				],
				400
			)
		);
	}
	
	public function test_remove_event_layout_success() {
		$this->make_controller()->register();
		
		Maps_Service::insert_rows_from_service(
			[
				[
					'id'            => 'some-map-1',
					'name'          => 'Some Map 1',
					'seats'         => 100,
					'screenshotUrl' => 'https://example.com/some-map-1.png',
				],
			]
		);
		set_transient( Maps_Service::update_transient_name(), time() );
		
		Layouts::insert_rows_from_service(
			[
				[
					'id'            => 'some-layout-1',
					'name'          => 'Some Layout 1',
					'seats'         => 100,
					'createdDate'   => time() * 1000,
					'mapId'         => 'some-map-1',
					'screenshotUrl' => 'https://example.com/some-layouts-1.png',
				],
			]
		);
		set_transient( Layouts::update_transient_name(), time() );
		
		Seat_Types_Table::insert_many(
			[
				[
					'id'     => 'some-seat-type-1',
					'name'   => 'Some Seat Type 1',
					'seats'  => 70,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
				[
					'id'     => 'some-seat-type-2',
					'name'   => 'Some Seat Type 2',
					'seats'  => 30,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
			]
		);
		
		set_transient( Seat_Types::update_transient_name(), time() );
		$this->set_up_ajax_request_context();
		$this->reset_wp_send_json_mocks();
		
		// Setup request body.
		$this->set_oauth_token( 'auth-token' );
		
		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler   = tribe( 'tickets.handler' );
		$capacity_meta_key = $tickets_handler->key_capacity;
		
		// Create event with associated layout and ticket and attendees.
		$post_id = static::factory()->post->create();
		
		// Enable the global stock on the Event.
		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );
		
		// set capacity as per layout 1.
		update_post_meta( $post_id, $capacity_meta_key, 100 );
		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_LEVEL, 100 );
		
		update_post_meta( $post_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-1' );
		
		$ticket_id_1 = $this->create_tc_ticket(
			$post_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 70,
				],
			]
		);
		
		$ticket_id_2 = $this->create_tc_ticket(
			$post_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 30,
				],
			]
		);
		
		update_post_meta( $ticket_id_1, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_id_1, Meta::META_KEY_SEAT_TYPE, 'some-seat-type-1' );
		update_post_meta( $ticket_id_1, Meta::META_KEY_LAYOUT_ID, 'some-layout-1' );
		
		
		update_post_meta( $ticket_id_2, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_id_2, Meta::META_KEY_SEAT_TYPE, 'some-seat-type-2' );
		update_post_meta( $ticket_id_2, Meta::META_KEY_LAYOUT_ID, 'some-layout-1' );
		
		$ticket_1 = tribe( Module::class )->get_ticket( $post_id, $ticket_id_1 );
		$ticket_2 = tribe( Module::class )->get_ticket( $post_id, $ticket_id_2 );
		
		$this->assertEquals( 70, $ticket_1->capacity() );
		$this->assertEquals( 70, $ticket_1->stock() );
		$this->assertEquals( 70, $ticket_1->available() );
		$this->assertEquals( 70, $ticket_1->inventory() );
		
		$this->assertEquals( 30, $ticket_2->capacity() );
		$this->assertEquals( 30, $ticket_2->stock() );
		$this->assertEquals( 30, $ticket_2->available() );
		$this->assertEquals( 30, $ticket_2->inventory() );
		
		$global_stock = new Global_Stock( $post_id );
		
		$this->assertTrue( $global_stock->is_enabled(), 'Global stock should be enabled.' );
		$this->assertEquals( 100, tribe_get_event_capacity( $post_id ), 'Total Event capacity should be 100' );
		$this->assertEquals( 100, $global_stock->get_stock_level(), 'Global stock should be 100' );
		
		$order = $this->create_order(
			[
				$ticket_id_1 => 5,
				$ticket_id_2 => 5,
			]
		);
		
		$order_attendees = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		
		// Mock the reservation ID to do proper stock calculation.
		foreach ( $order_attendees as $key => $attendee ) {
			update_post_meta( $attendee['ID'], Meta::META_KEY_RESERVATION_ID, 'test-reservation-id-' . $key );
		}
		
		// Total attendees by layout should be 10.
		$this->assertEquals(
			10,
			tribe_attendees()
				->where( 'event', $post_id )
				->where( 'meta_equals', Meta::META_KEY_LAYOUT_ID, 'some-layout-1' )
				->count()
		);
		
		$_REQUEST['postId'] = $post_id;
		
		$wp_send_json_success = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_EVENT_LAYOUT_REMOVE );
		
		$success = $wp_send_json_success->was_called_times_with(
			1,
			[
				'updatedTickets'   => 2, // Number of Tickets updated.
				'updatedAttendees' => 10, // Number of Attendees updated.
			]
		);
		
		$this->assertTrue( $success );
		
		// Confirm no layout is set for the post.
		$this->assertEquals( '', get_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, true ) );
		$this->assertEquals( '', get_post_meta( $post_id, Meta::META_KEY_ENABLED, true ) );
		
		// Confirm the tickets have no layout set.
		$this->assertEquals( '', get_post_meta( $ticket_id_1, Meta::META_KEY_LAYOUT_ID, true ) );
		$this->assertEquals( '', get_post_meta( $ticket_id_2, Meta::META_KEY_LAYOUT_ID, true ) );
	
		// Total attendees should be 10.
		$this->assertEquals(
			10,
			tribe_attendees()
			->where( 'event', $post_id )
			->count()
		);
		
		// Total attendees by layout should be 0.
		$this->assertEquals(
			0,
			tribe_attendees()
			->where( 'event', $post_id )
			->where( 'meta_equals', Meta::META_KEY_LAYOUT_ID, 'some-layout-1' )
			->count()
		);
		
		// Refresh tickets.
		$ticket_1 = tribe( Module::class )->get_ticket( $post_id, $ticket_id_1 );
		$ticket_2 = tribe( Module::class )->get_ticket( $post_id, $ticket_id_2 );
		
		// Confirm the tickets have no layout set.
		$this->assertEquals( 1, $ticket_1->capacity() );
		$this->assertEquals( 1, $ticket_1->stock() );
		$this->assertEquals( 1, $ticket_1->available() );
		$this->assertEquals( 1, $ticket_1->inventory() );
		
		$this->assertEquals( 1, $ticket_2->capacity() );
		$this->assertEquals( 1, $ticket_2->stock() );
		$this->assertEquals( 1, $ticket_2->available() );
		$this->assertEquals( 1, $ticket_2->inventory() );
		
		// Confirm the global stock is removed.
		$this->assertFalse( $global_stock->is_enabled() );
		$this->assertEquals( 0, $global_stock->get_stock_level() );
		
		// Check event capacity.
		$this->assertEquals( 2, tribe_get_event_capacity( $post_id ) );
		
		$stock_data = \Tribe__Tickets__Tickets::get_ticket_counts( $post_id );
		
		$this->assertMatchesJsonSnapshot( wp_json_encode( $stock_data, JSON_SNAPSHOT_OPTIONS ) );
	}
	
	public function test_remove_event_layout_fails() {
		$this->make_controller()->register();
		
		$admin_id = $this->set_up_ajax_request_context();
		$this->reset_wp_send_json_mocks();
		
		// Missing post ID.
		unset( $_REQUEST['postId'] );
		$wp_send_json_error = $this->mock_wp_send_json_error();
		
		do_action( 'wp_ajax_' . Ajax::ACTION_EVENT_LAYOUT_REMOVE );
		
		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'No post ID provided',
				],
				400
			)
		);
		$this->reset_wp_send_json_mocks();
		
		// User cannot edit post.
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$_REQUEST['postId'] = static::factory()->post->create();
		$wp_send_json_error = $this->mock_wp_send_json_error();
		
		do_action( 'wp_ajax_' . Ajax::ACTION_EVENT_LAYOUT_REMOVE );
		
		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'User has no permission.',
				],
				403
			)
		);
		$this->reset_wp_send_json_mocks();
		
		wp_set_current_user( $admin_id );
		
		// No layout set for the post.
		$_REQUEST['postId'] = static::factory()->post->create();
		$wp_send_json_error = $this->mock_wp_send_json_error();
		
		do_action( 'wp_ajax_' . Ajax::ACTION_EVENT_LAYOUT_REMOVE );
		
		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'Layout not found.',
				],
				403
			)
		);
		
		$this->reset_wp_send_json_mocks();
	}
}
