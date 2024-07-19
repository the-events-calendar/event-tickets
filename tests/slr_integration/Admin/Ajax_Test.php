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
use Tribe\Tickets\Test\Traits\Reservations_Maker;
use Tribe__Tickets__Data_API as Data_API;

class Ajax_Test extends Controller_Test_Case {
	use SnapshotAssertions;
	use With_Uopz;
	use OAuth_Token;
	use WP_Remote_Mocks;
	use Reservations_Maker;
	use WP_Send_JSON_Mocks;

	protected string $controller_class = Ajax::class;

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

	private function set_up_ajax_request_context(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
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
		// TODO
	}

	public function test_delete_layout_from_service(): void {
		// TODO
	}

	public function test_update_reservations(): void {
		// TODO
	}

	public function test_clear_reservations(): void {
		// TODO
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
}
