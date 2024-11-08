<?php

namespace TEC\Tickets\Seating;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Seating\Service\Service_Status;
use TEC\Tickets\Seating\Tables\Layouts;
use TEC\Tickets\Seating\Tables\Seat_Types;
use TEC\Tickets\Seating\Tests\Integration\Layouts_Factory;
use TEC\Tickets\Seating\Tests\Integration\Truncates_Custom_Tables;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Events__Main as TEC;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__REST__V1__Endpoints__Single_Ticket as Single_Ticket_Rest;
use WP_REST_Request;

class Editor_Test extends Controller_Test_Case {
	use Layouts_Factory;
	use SnapshotAssertions;
	use Ticket_Maker;
	use Order_Maker;
	use Truncates_Custom_Tables;

	protected string $controller_class = Editor::class;

	/**
	 * @before
	 */
	public function set_up_test_case(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		global $pagenow;
		$pagenow = '';
	}

	/**
	 * @after
	 */
	public function restore_pagenow(): void {
		global $pagenow;
		$pagenow = '';
	}

	public function asset_data_provider() {
		$assets = [
			'tec-tickets-seating-block-editor'       => '/build/Seating/blockEditor.js',
			'tec-tickets-seating-block-editor-style' => '/build/Seating/blockEditor.css',
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

	/**
	 * @test
	 */
	public function it_should_filter_seating_totals(): void {
		$post_id = static::factory()->post->create();

		$tickets_handler = tribe( 'tickets.handler' );

		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id, Meta::META_KEY_ENABLED, '1' );
		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_ENABLED, '1' );
		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_LEVEL, 40 );
		update_post_meta( $post_id, $tickets_handler->key_capacity, 40 );

		// Create the Seat Types.
		Seat_Types::insert_many(
			[
				[
					'id'     => 'some-seat-type-1',
					'name'   => 'B',
					'seats'  => 40,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
			]
		);
		set_transient( \TEC\Tickets\Seating\Service\Seat_Types::update_transient_name(), time() );

		$ticket_id_1 = $this->create_tc_ticket(
			$post_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 40,
				],
			]
		);

		$ticket_id_2 = $this->create_tc_ticket(
			$post_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 40,
				],
			]
		);

		$ticket_id_3 = $this->create_tc_ticket(
			$post_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 40,
				],
			]
		);

		update_post_meta( $ticket_id_1, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_id_1, Meta::META_KEY_SEAT_TYPE, 'some-seat-type-1' );

		update_post_meta( $ticket_id_2, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_id_2, Meta::META_KEY_SEAT_TYPE, 'some-seat-type-1' );

		update_post_meta( $ticket_id_3, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_id_3, Meta::META_KEY_SEAT_TYPE, 'some-seat-type-1' );

		$order = $this->create_order(
			[
				$ticket_id_1 => 3,
				$ticket_id_2 => 5,
			]
		);

		$totals_1 = [
			'stock'   => 40 - 3 - 5,
			'sold'    => 3,
			'pending' => 0,
		];

		$totals_2 = [
			'stock'   => 40 - 3 - 5,
			'sold'    => 5,
			'pending' => 0,
		];

		$totals_3 = [
			'stock'   => 40 - 3 - 5,
			'sold'    => 0,
			'pending' => 0,
		];

		$controller = $this->make_controller();

		$request_1 = new WP_REST_Request( 'GET', "tribe/tickets/v1/tickets/{$ticket_id_1}" );
		$request_1['id'] = $ticket_id_1;
		$request_2 = new WP_REST_Request( 'GET', "tribe/tickets/v1/tickets/{$ticket_id_2}" );
		$request_2['id'] = $ticket_id_2;
		$request_3 = new WP_REST_Request( 'GET', "tribe/tickets/v1/tickets/{$ticket_id_3}" );
		$request_3['id'] = $ticket_id_3;

		$data_1 = [ 'totals' => $totals_1 ];
		$data_2 = [ 'totals' => $totals_2 ];
		$data_3 = [ 'totals' => $totals_3 ];
		$data_1['totals']['stock'] = $data_2['totals']['stock'] = $data_3['totals']['stock'] = 40;

		$response_1 = $controller->filter_seating_totals( $data_1, $request_1 );
		$response_2 = $controller->filter_seating_totals( $data_2, $request_2 );
		$response_3 = $controller->filter_seating_totals( $data_3, $request_3 );

		$this->assertEquals( $totals_1, $response_1['totals'] );
		$this->assertEquals( $totals_2, $response_2['totals'] );
		$this->assertEquals( $totals_3, $response_3['totals'] );
	}

	public function get_store_data_provider(): \Generator {
		yield 'new post' => [
			function (): array {
				global $pagenow;
				$pagenow = 'post-new.php';
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();

				return [];
			}
		];

		yield 'existing post, using meta not set' => [
			function (): array {
				$id = self::factory()->post->create();
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();

				return [];
			}
		];

		yield 'existing post, using meta set, layout not set' => [
			function (): array {
				$id = self::factory()->post->create();
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
				update_post_meta( $id, Meta::META_KEY_ENABLED, 'yes' );
				delete_post_meta( $id, Meta::META_KEY_LAYOUT_ID );

				return [];
			}
		];

		yield 'existing post, using meta set, layout set' => [
			function (): array {
				$id = self::factory()->post->create();
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
				update_post_meta( $id, Meta::META_KEY_ENABLED, 'yes' );
				update_post_meta( $id, Meta::META_KEY_LAYOUT_ID, 'layout-1' );

				return [];
			}
		];

		yield 'existing post, using meta set, layout set, with tickets' => [
			function (): array {
				$id = self::factory()->post->create();
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
				update_post_meta( $id, Meta::META_KEY_ENABLED, 'yes' );
				update_post_meta( $id, Meta::META_KEY_LAYOUT_ID, 'layout-1' );
				$ticket_1 = $this->create_tc_ticket( $id, 10.10 );
				update_post_meta( $ticket_1, Meta::META_KEY_SEAT_TYPE, 'uuid-normal' );
				$ticket_2 = $this->create_tc_ticket( $id, 20.30 );
				update_post_meta( $ticket_2, Meta::META_KEY_SEAT_TYPE, 'uuid-forward-block' );
				$ticket_3 = $this->create_tc_ticket( $id, 30.40 );
				update_post_meta( $ticket_3, Meta::META_KEY_SEAT_TYPE, 'uuid-vip' );

				return [$ticket_1, $ticket_2, $ticket_3];
			}
		];

		yield 'existing post, not using meta set or layout set, with tickets' => [
			function (): array {
				$id = self::factory()->post->create();
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
				$ticket_1 = $this->create_tc_ticket( $id, 10.10 );
				$ticket_2 = $this->create_tc_ticket( $id, 20.30 );
				$ticket_3 = $this->create_tc_ticket( $id, 30.40 );

				return [$ticket_1, $ticket_2, $ticket_3];
			}
		];

		yield 'new event' => [
			function (): array {
				$post_type = TEC::POSTTYPE;
				global $pagenow;
				$pagenow               = 'post-new.php';
				$_REQUEST['post_type'] = $post_type;
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();

				return [];
			}
		];

		yield 'existing event, using meta not set' => [
			function (): array {
				$id = tribe_events()->set_args( [
					'title'      => 'Test Event',
					'start_date' => '+1 week',
					'duration'   => 3 * HOUR_IN_SECONDS,
				] )->create()->ID;
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();

				return [];
			}
		];

		yield 'existing event, using meta set, layout not set' => [
			function (): array {
				$id = tribe_events()->set_args( [
					'title'      => 'Test Event',
					'start_date' => '+1 week',
					'duration'   => 3 * HOUR_IN_SECONDS,
				] )->create()->ID;
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
				update_post_meta( $id, Meta::META_KEY_ENABLED, 'yes' );
				delete_post_meta( $id, Meta::META_KEY_LAYOUT_ID );

				return [];
			}
		];

		yield 'existing event, using meta set, layout set' => [
			function (): array {
				$id = tribe_events()->set_args( [
					'title'      => 'Test Event',
					'start_date' => '+1 week',
					'duration'   => 3 * HOUR_IN_SECONDS,
				] )->create()->ID;
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
				update_post_meta( $id, Meta::META_KEY_ENABLED, 'yes' );
				update_post_meta( $id, Meta::META_KEY_LAYOUT_ID, 'layout-1' );

				return [];
			}
		];

		yield 'existing event, using meta set, layout set, with tickets' => [
			function (): array {
				$id = tribe_events()->set_args( [
					'title'      => 'Test Event',
					'start_date' => '+1 week',
					'duration'   => 3 * HOUR_IN_SECONDS,
				] )->create()->ID;
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
				update_post_meta( $id, Meta::META_KEY_ENABLED, 'yes' );
				update_post_meta( $id, Meta::META_KEY_LAYOUT_ID, 'layout-1' );
				$ticket_1 = $this->create_tc_ticket( $id, 10.10 );
				update_post_meta( $ticket_1, Meta::META_KEY_SEAT_TYPE, 'uuid-normal' );
				$ticket_2 = $this->create_tc_ticket( $id, 20.30 );
				update_post_meta( $ticket_2, Meta::META_KEY_SEAT_TYPE, 'uuid-forward-block' );
				$ticket_3 = $this->create_tc_ticket( $id, 30.40 );
				update_post_meta( $ticket_3, Meta::META_KEY_SEAT_TYPE, 'uuid-vip' );

				return [ $ticket_1, $ticket_2, $ticket_3 ];
			}
		];

		yield 'service down' => [
			function (): array {
				add_filter( 'tec_tickets_seating_service_status', function ( $_status, $backend_base_url ) {
					return new Service_Status( $backend_base_url, Service_Status::SERVICE_UNREACHABLE );
				}, 1000, 2 );
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( static::factory()->post->create() );

				return [];
			}
		];

		yield 'service not connected' => [
			function (): array {
				add_filter( 'tec_tickets_seating_service_status', function ( $_status, $backend_base_url ) {
					return new Service_Status( $backend_base_url, Service_Status::NOT_CONNECTED );
				}, 1000, 2 );
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( static::factory()->post->create() );

				return [];
			}
		];

		yield 'no license' => [
			function (): array {
				add_filter( 'tec_tickets_seating_service_status', function ( $_status, $backend_base_url ) {
					return new Service_Status( $backend_base_url, Service_Status::NO_LICENSE );
				}, 1000, 2 );
				test_remove_seating_license_key_callback();
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( static::factory()->post->create() );

				return [];
			}
		];

		yield 'invalid license' => [
			function (): array {
				add_filter( 'tec_tickets_seating_service_status', function ( $_status, $backend_base_url ) {
					return new Service_Status( $backend_base_url, Service_Status::INVALID_LICENSE );
				}, 1000, 2 );
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( static::factory()->post->create() );

				return [];
			}
		];

		yield 'expired license' => [
			function (): array {
				add_filter( 'tec_tickets_seating_service_status', function ( $_status, $backend_base_url ) {
					return new Service_Status( $backend_base_url, Service_Status::EXPIRED_LICENSE );
				}, 1000, 2 );
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( static::factory()->post->create() );

				return [];
			}
		];

		yield 'service down - new' => [
			function (): array {
				add_filter( 'tec_tickets_seating_service_status', function ( $_status, $backend_base_url ) {
					return new Service_Status( $backend_base_url, Service_Status::SERVICE_UNREACHABLE );
				}, 1000, 2 );
				global $pagenow, $post;
				$pagenow = 'post-new.php';
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
				$post    = get_post( static::factory()->post->create() );

				return [];
			}
		];

		yield 'service not connected - new' => [
			function (): array {
				add_filter( 'tec_tickets_seating_service_status', function ( $_status, $backend_base_url ) {
					return new Service_Status( $backend_base_url, Service_Status::NOT_CONNECTED );
				}, 1000, 2 );
				global $pagenow, $post;
				$pagenow = 'post-new.php';
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
				$post    = get_post( static::factory()->post->create() );

				return [];
			}
		];

		yield 'no license - new' => [
			function (): array {
				add_filter( 'tec_tickets_seating_service_status', function ( $_status, $backend_base_url ) {
					return new Service_Status( $backend_base_url, Service_Status::NO_LICENSE );
				}, 1000, 2 );
				test_remove_seating_license_key_callback();
				global $pagenow, $post;
				$pagenow = 'post-new.php';
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
				$post    = get_post( static::factory()->post->create() );

				return [];
			}
		];

		yield 'invalid license - new' => [
			function (): array {
				add_filter( 'tec_tickets_seating_service_status', function ( $_status, $backend_base_url ) {
					return new Service_Status( $backend_base_url, Service_Status::INVALID_LICENSE );
				}, 1000, 2 );
				global $pagenow, $post;
				$pagenow = 'post-new.php';
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
				$post    = get_post( static::factory()->post->create() );

				return [];
			}
		];

		yield 'expired license - new' => [
			function (): array {
				add_filter( 'tec_tickets_seating_service_status', function ( $_status, $backend_base_url ) {
					return new Service_Status( $backend_base_url, Service_Status::EXPIRED_LICENSE );
				}, 1000, 2 );
				global $pagenow, $post;
				$pagenow = 'post-new.php';
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
				$post    = get_post( static::factory()->post->create() );

				return [];
			}
		];

		yield 'existing event, not using meta set or layout set, with tickets' => [
			function (): array {
				$id = tribe_events()->set_args( [
					'title'      => 'Test Event',
					'start_date' => '+1 week',
					'duration'   => 3 * HOUR_IN_SECONDS,
				] )->create()->ID;
				global $pagenow, $post;
				$pagenow = 'edit.php';
				$post    = get_post( $id );
				$this->given_many_layouts_in_db( 3 );
				$this->given_layouts_just_updated();
				$ticket_1 = $this->create_tc_ticket( $id, 10.10 );
				$ticket_2 = $this->create_tc_ticket( $id, 20.30 );
				$ticket_3 = $this->create_tc_ticket( $id, 30.40 );

				return [ $ticket_1, $ticket_2, $ticket_3 ];
			}
		];
	}

	/**
	 * @dataProvider get_store_data_provider
	 */
	public function test_get_store_data( \Closure $fixture ): void {
		$ticket_ids = $fixture();

		$store_data = $this->make_controller()->get_store_data();

		$json = str_replace(
			$ticket_ids,
			'{{ticket_id}}',
			wp_json_encode( $store_data, JSON_SNAPSHOT_OPTIONS )
		);
		$this->assertMatchesJsonSnapshot( $json );
	}

	public function test_it_should_update_slr_flags_on_ticket_save() {
		$id = tribe_events()->set_args( [
			'title'      => 'Test Event',
			'start_date' => '+1 week',
			'duration'   => 3 * HOUR_IN_SECONDS,
		] )->create()->ID;

		$ticket_1 = $this->create_tc_ticket( $id, 10.10 );

		$this->assertFalse( (bool) get_post_meta( $id, Meta::META_KEY_ENABLED, true ) );
		$this->assertEmpty( get_post_meta( $id, Meta::META_KEY_LAYOUT_ID, true ) );
		$this->assertFalse( (bool) get_post_meta( $ticket_1, Meta::META_KEY_ENABLED, true ) );
		$this->assertEmpty( get_post_meta( $ticket_1, Meta::META_KEY_SEAT_TYPE, true ) );

		$ticket_data = [
			'tribe-ticket' => [
				'seating' => [
					'enabled'  => 1,
					'seatType' => 'seat-type-uuid-1',
					'layoutId' => 'layout-uuid-1',
					]
			],
		];

		$this->make_controller()->register();

		do_action( 'tribe_tickets_ticket_added', $id, $ticket_1, $ticket_data );

		$this->assertTrue( (bool) get_post_meta( $id, Meta::META_KEY_ENABLED, true ) );
		$this->assertEquals( 'layout-uuid-1', get_post_meta( $id, Meta::META_KEY_LAYOUT_ID, true ) );
		$this->assertTrue( (bool) get_post_meta( $ticket_1, Meta::META_KEY_ENABLED, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $ticket_1, Meta::META_KEY_SEAT_TYPE, true ) );
	}

	public function test_it_should_skip_capacity_storage_when_revision() {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2020-01-01 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		// Enable the global stock on the Event.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );

		// Set the Event global stock level to 100.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 100 );

		update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 1 );

		$ticket_id = $this->create_tc_ticket(
			$event_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 30,
				],
			]
		);

		update_post_meta( $ticket_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-A' );

		$revision_id = wp_save_post_revision( $event_id );
		tribe( 'tickets.handler' )->filter_capacity_support( null, $revision_id, tribe( 'tickets.handler' )->key_capacity, true );

		$this->assertEquals( 100, get_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );

		$autosave_id = wp_create_post_autosave( array_merge( [ 'post_ID' => $event_id ], get_post( $event_id, ARRAY_A ) ) );

		tribe( 'tickets.handler' )->filter_capacity_support( null, $autosave_id, tribe( 'tickets.handler' )->key_capacity, true );

		$this->assertEquals( 100, get_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
	}
}
