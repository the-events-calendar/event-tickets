<?php

namespace TEC\Tickets\Seating\Service;

use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Tables\Seat_Types as Seat_Types_Table;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Global_Stock as Global_Stock;

class Seat_Types_Test extends WPTestCase {
	use Ticket_Maker;
	use SnapshotAssertions;

	/**
	 * @before
	 */
	public function set_up_tickets_commerce(): void {
		// Ensure the Tickets Commerce module is active.
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules[ Module::class ] = tribe( Module::class )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object, so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );

		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	/**
	 * @before
	 * @after
	 */
	public function clean_up(): void {
		Seat_Types_Table::truncate();
	}

	public function test_update_from_service(): void {
		// Create 3 seat types.
		Seat_Types::insert_rows_from_service(
			[
				[
					'id'       => 'seat-type-1',
					'name'     => 'Seat Type 1',
					'seats'    => 10,
					'mapId'    => 'some-map-id',
					'layoutId' => 'some-layout-id',
				],
				[
					'id'       => 'seat-type-2',
					'name'     => 'Seat Type 2',
					'seats'    => 20,
					'mapId'    => 'some-map-id',
					'layoutId' => 'some-layout-id',
				],
				[
					'id'       => 'seat-type-3',
					'name'     => 'Seat Type 3',
					'seats'    => 30,
					'mapId'    => 'some-map-id',
					'layoutId' => 'some-layout-id',
				],
			]
		);
		$seat_types = tribe( Seat_Types::class );

		// Empty seat types.
		$this->assertEquals( 0, $seat_types->update_from_service( [] ) );
		$this->assertCount( 3, iterator_to_array( Seat_Types_Table::fetch_all(), false ) );

		// Update the first seat type.
		$this->assertEquals( 1, $seat_types->update_from_service( [
			[
				'id'         => 'seat-type-1',
				'name'       => 'New Seat Type 1',
				'seatsCount' => 23,
			],
		] ) );
		$this->assertCount( 3, iterator_to_array( Seat_Types_Table::fetch_all(), false ) );
		$this->assertEquals(
			[
				'id'     => 'seat-type-1',
				'name'   => 'New Seat Type 1',
				'seats'  => '23',
				'map'    => 'some-map-id',
				'layout' => 'some-layout-id',
			],
			DB::get_row(
				DB::prepare(
					"SELECT * FROM %i WHERE id = %s",
					Seat_Types_Table::table_name(),
					'seat-type-1'
				),
				ARRAY_A
			)
		);

		// Update the second and third seat type.
		$this->assertEquals( 2, $seat_types->update_from_service( [
			[
				'id'         => 'seat-type-2',
				'name'       => 'New Seat Type 2',
				'seatsCount' => 89,
			],
			[
				'id'         => 'seat-type-3',
				'name'       => 'New Seat Type 3',
				'seatsCount' => 66,
			],
		] ) );
		$this->assertCount( 3, iterator_to_array( Seat_Types_Table::fetch_all(), false ) );
		$this->assertEquals(
			[
				'id'     => 'seat-type-2',
				'name'   => 'New Seat Type 2',
				'seats'  => '89',
				'map'    => 'some-map-id',
				'layout' => 'some-layout-id',
			],
			DB::get_row(
				DB::prepare(
					"SELECT * FROM %i WHERE id = %s",
					Seat_Types_Table::table_name(),
					'seat-type-2'
				),
				ARRAY_A
			)
		);
		$this->assertEquals(
			[
				'id'     => 'seat-type-3',
				'name'   => 'New Seat Type 3',
				'seats'  => '66',
				'map'    => 'some-map-id',
				'layout' => 'some-layout-id',
			],
			DB::get_row(
				DB::prepare(
					"SELECT * FROM %i WHERE id = %s",
					Seat_Types_Table::table_name(),
					'seat-type-3'
				),
				ARRAY_A
			)
		);
	}

	public function test_update_tickets_capacity(): void {
		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler   = tribe( 'tickets.handler' );
		$capacity_meta_key = $tickets_handler->key_capacity;
		// Create the 4 seat types.
		Seat_Types::insert_rows_from_service(
			[
				[
					'id'       => 'seat-type-uuid-1',
					'name'     => 'Seat Type 1',
					'seats'    => 10,
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-1',
				],
				[
					'id'       => 'seat-type-uuid-2',
					'name'     => 'Seat Type 2',
					'seats'    => 20,
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-1',
				],
				[
					'id'       => 'seat-type-uuid-3',
					'name'     => 'Seat Type 3',
					'seats'    => 30,
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-2',
				],
				[
					'id'       => 'seat-type-uuid-4',
					'name'     => 'Seat Type 4',
					'seats'    => 40,
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-2',
				],
			]
		);
		$seat_types = tribe( Seat_Types::class );
		[ $post_id_1, $post_id_2, $post_id_3 ] = static::factory()->post->create_many( 3 );
		// Post 1 is not using assigned seating.
		$post_1_ticket_1 = $this->create_tc_ticket( $post_id_1, 10 );
		$post_1_ticket_2 = $this->create_tc_ticket( $post_id_1, 20 );
		// Post 2 is using assigned seating.
		// Layout 1 has 2 seat types with capacity 10 + 20 = 30.
		update_post_meta( $post_id_2, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_2, $capacity_meta_key, 30 );
		update_post_meta( $post_id_2, Global_Stock::GLOBAL_STOCK_LEVEL, 30 );
		$post_2_ticket_1 = $this->create_tc_ticket( $post_id_2, 10 );
		update_post_meta( $post_2_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_2_ticket_1, $capacity_meta_key, 10 );
		update_post_meta( $post_2_ticket_1, '_stock', 10 );
		$post_2_ticket_2 = $this->create_tc_ticket( $post_id_2, 20 );
		update_post_meta( $post_2_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_2_ticket_2, $capacity_meta_key, 20 );
		update_post_meta( $post_2_ticket_2, '_stock', 20 );
		// Post 3 is using assigned seating.
		update_post_meta( $post_id_3, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-2' );
		update_post_meta( $post_id_3, $capacity_meta_key, 70 );
		update_post_meta( $post_id_3, Global_Stock::GLOBAL_STOCK_LEVEL, 70 );
		$post_3_ticket_1 = $this->create_tc_ticket( $post_id_3, 10 );
		update_post_meta( $post_3_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-3' );
		update_post_meta( $post_3_ticket_1, $capacity_meta_key, 30 );
		update_post_meta( $post_3_ticket_1, '_stock', 30 );
		$post_3_ticket_2 = $this->create_tc_ticket( $post_id_3, 20 );
		update_post_meta( $post_3_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-4' );
		update_post_meta( $post_3_ticket_2, $capacity_meta_key, 40 );
		update_post_meta( $post_3_ticket_2, '_stock', 40 );
		// A second ticket on the same seat type.
		$post_3_ticket_3 = $this->create_tc_ticket( $post_id_3, 50 );
		update_post_meta( $post_3_ticket_3, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-4' );
		update_post_meta( $post_3_ticket_3, $capacity_meta_key, 40 );
		update_post_meta( $post_3_ticket_3, '_stock', 40 );

		// Empty update map.
		$this->assertEquals( 0, $seat_types->update_tickets_capacity( [] ) );

		// Update the capacity of the first seat type.
		$this->assertEquals( 1, $seat_types->update_tickets_capacity( [
			'seat-type-uuid-1' => 23
		] ) );
		// Posts should not be affected by the update, only the tickets.
		$this->assertEquals( 30, get_post_meta( $post_id_2, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_id_2, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 70, get_post_meta( $post_id_3, $capacity_meta_key, true ) );
		$this->assertEquals( 70, get_post_meta( $post_id_3, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 23, get_post_meta( $post_2_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 23, get_post_meta( $post_2_ticket_1, '_stock', true ) );
		$this->assertEquals( 20, get_post_meta( $post_2_ticket_2, $capacity_meta_key, true ) );
		$this->assertEquals( 20, get_post_meta( $post_2_ticket_2, '_stock', true ) );
		$this->assertEquals( 30, get_post_meta( $post_3_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_3_ticket_1, '_stock', true ) );
		$this->assertEquals( 40, get_post_meta( $post_3_ticket_2, $capacity_meta_key, true ) );
		$this->assertEquals( 40, get_post_meta( $post_3_ticket_2, '_stock', true ) );
		$this->assertEquals( 40, get_post_meta( $post_3_ticket_3, $capacity_meta_key, true ) );
		$this->assertEquals( 40, get_post_meta( $post_3_ticket_3, '_stock', true ) );

		// Update the capacity of the second, third and fourth seat types.
		$this->assertEquals( 4, $seat_types->update_tickets_capacity( [
			'seat-type-uuid-2' => 89,
			'seat-type-uuid-3' => 123,
			'seat-type-uuid-4' => 266,
		] ) );
		$this->assertEquals( 30, get_post_meta( $post_id_2, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_id_2, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 70, get_post_meta( $post_id_3, $capacity_meta_key, true ) );
		$this->assertEquals( 70, get_post_meta( $post_id_3, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 23, get_post_meta( $post_2_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 23, get_post_meta( $post_2_ticket_1, '_stock', true ) );
		$this->assertEquals( 89, get_post_meta( $post_2_ticket_2, $capacity_meta_key, true ) );
		$this->assertEquals( 89, get_post_meta( $post_2_ticket_2, '_stock', true ) );
		$this->assertEquals( 123, get_post_meta( $post_3_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 123, get_post_meta( $post_3_ticket_1, '_stock', true ) );
		$this->assertEquals( 266, get_post_meta( $post_3_ticket_2, $capacity_meta_key, true ) );
		$this->assertEquals( 266, get_post_meta( $post_3_ticket_2, '_stock', true ) );
		$this->assertEquals( 266, get_post_meta( $post_3_ticket_3, $capacity_meta_key, true ) );
		$this->assertEquals( 266, get_post_meta( $post_3_ticket_3, '_stock', true ) );
	}

	public function test_update_tickets_with_calculated_stock_and_capacity_simple(): void {
		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler   = tribe( 'tickets.handler' );
		$capacity_meta_key = $tickets_handler->key_capacity;
		// Create the 2 seat types.
		Seat_Types::insert_rows_from_service(
			[
				[
					'id'       => 'seat-type-uuid-1',
					'name'     => 'Seat Type 1',
					'seats'    => 10,
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-1',
				],
				[
					'id'       => 'seat-type-uuid-2',
					'name'     => 'Seat Type 2',
					'seats'    => 20,
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-1',
				]
			]
		);

		$seat_types = tribe( Seat_Types::class );

		[
			$post_id_1,
			$post_id_2,
			$post_id_3,
			$post_id_4,
		] = static::factory()->post->create_many( 4 );

		// Post 1 set up.
		$post_1_ticket_1 = $this->create_tc_ticket( $post_id_1, 10 );
		$post_1_ticket_2 = $this->create_tc_ticket( $post_id_1, 20 );

		update_post_meta( $post_id_1, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_1, $capacity_meta_key, 30 );
		update_post_meta( $post_id_1, Global_Stock::GLOBAL_STOCK_LEVEL, 30 );

		update_post_meta( $post_1_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_1_ticket_1, $capacity_meta_key, 10 );
		update_post_meta( $post_1_ticket_1, '_stock', 10 );

		update_post_meta( $post_1_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_1_ticket_2, $capacity_meta_key, 20 );
		update_post_meta( $post_1_ticket_2, '_stock', 20 );

		// Post 2 set up.
		$post_2_ticket_1 = $this->create_tc_ticket( $post_id_2, 20 );

		update_post_meta( $post_id_2, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_2, $capacity_meta_key, 20 );
		update_post_meta( $post_id_2, Global_Stock::GLOBAL_STOCK_LEVEL, 20 );

		update_post_meta( $post_2_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_2_ticket_1, $capacity_meta_key, 20 );
		update_post_meta( $post_2_ticket_1, '_stock', 20 );

		// Post 3 set up.
		$post_3_ticket_1 = $this->create_tc_ticket( $post_id_3, 10 );
		$post_3_ticket_2 = $this->create_tc_ticket( $post_id_3, 20 );

		update_post_meta( $post_id_3, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_3, $capacity_meta_key, 30 );
		update_post_meta( $post_id_3, Global_Stock::GLOBAL_STOCK_LEVEL, 24 );

		update_post_meta( $post_3_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_3_ticket_1, $capacity_meta_key, 10 );
		update_post_meta( $post_3_ticket_1, '_stock', 8 );

		update_post_meta( $post_3_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_3_ticket_2, $capacity_meta_key, 20 );
		update_post_meta( $post_3_ticket_2, '_stock', 16 );

		// Post 4 set up.
		$post_4_ticket_1 = $this->create_tc_ticket( $post_id_4, 10 );

		update_post_meta( $post_id_4, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_4, $capacity_meta_key, 20 );
		update_post_meta( $post_id_4, Global_Stock::GLOBAL_STOCK_LEVEL, 16 );

		update_post_meta( $post_4_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_4_ticket_1, $capacity_meta_key, 20 );
		update_post_meta( $post_4_ticket_1, '_stock', 16 );

		// Start testing!
		// We need to mock the update of seat type 2 to simulate the deletion of the seat type.
		update_post_meta( $post_1_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_2_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_3_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_4_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );

		// There should be 6 updated tickets because of seat-type-uuid-2 seat type deletion.
		$this->assertEquals( 6, $seat_types->update_tickets_with_calculated_stock_and_capacity( 'seat-type-uuid-1', 30, [ $post_1_ticket_1, $post_3_ticket_1] ) );

		// Post 1 checks
		$this->assertEquals( 30, get_post_meta( $post_id_1, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_id_1, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 30, get_post_meta( $post_1_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_1_ticket_1, '_stock', true ) );
		$this->assertEquals( 30, get_post_meta( $post_1_ticket_2, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_1_ticket_2, '_stock', true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_1_ticket_1, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_1_ticket_2, Meta::META_KEY_SEAT_TYPE, true ) );

		// Post 2 checks
		$this->assertEquals( 30, get_post_meta( $post_id_2, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_id_2, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 30, get_post_meta( $post_2_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_2_ticket_1, '_stock', true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_2_ticket_1, Meta::META_KEY_SEAT_TYPE, true ) );

		// Post 3 checks
		$this->assertEquals( 30, get_post_meta( $post_id_3, $capacity_meta_key, true ) );
		$this->assertEquals( 24, get_post_meta( $post_id_3, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 30, get_post_meta( $post_3_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 24, get_post_meta( $post_3_ticket_1, '_stock', true ) );
		$this->assertEquals( 30, get_post_meta( $post_3_ticket_2, $capacity_meta_key, true ) );
		$this->assertEquals( 24, get_post_meta( $post_3_ticket_2, '_stock', true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_3_ticket_1, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_3_ticket_2, Meta::META_KEY_SEAT_TYPE, true ) );

		// Post 4 checks
		$this->assertEquals( 30, get_post_meta( $post_id_4, $capacity_meta_key, true ) );
		$this->assertEquals( 26, get_post_meta( $post_id_4, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 30, get_post_meta( $post_4_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 26, get_post_meta( $post_4_ticket_1, '_stock', true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_2_ticket_1, Meta::META_KEY_SEAT_TYPE, true ) );
	}

	public function test_update_tickets_with_calculated_stock_and_capacity_complex(): void {
		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler   = tribe( 'tickets.handler' );
		$capacity_meta_key = $tickets_handler->key_capacity;
		// Create the 3 seat types.
		Seat_Types::insert_rows_from_service(
			[
				[
					'id'       => 'seat-type-uuid-1',
					'name'     => 'Seat Type 1',
					'seats'    => 10,
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-1',
				],
				[
					'id'       => 'seat-type-uuid-2',
					'name'     => 'Seat Type 2',
					'seats'    => 20,
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-1',
				],
				[
					'id'       => 'seat-type-uuid-3',
					'name'     => 'Seat Type 3',
					'seats'    => 30,
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-1',
				],
			]
		);

		$seat_types = tribe( Seat_Types::class );

		[
			$post_id_1,
			$post_id_2,
			$post_id_3,
			$post_id_4,
			$post_id_5,
			$post_id_6,
			$post_id_7,
			$post_id_8,
			$post_id_9,
			$post_id_10,
		] = static::factory()->post->create_many( 10 );

		// Post 1 set up.
		$post_1_ticket_1 = $this->create_tc_ticket( $post_id_1, 10 );
		$post_1_ticket_2 = $this->create_tc_ticket( $post_id_1, 20 );
		$post_1_ticket_3 = $this->create_tc_ticket( $post_id_1, 30 );

		update_post_meta( $post_id_1, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_1, $capacity_meta_key, 60 );
		update_post_meta( $post_id_1, Global_Stock::GLOBAL_STOCK_LEVEL, 60 );

		update_post_meta( $post_1_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_1_ticket_1, $capacity_meta_key, 10 );
		update_post_meta( $post_1_ticket_1, '_stock', 10 );

		update_post_meta( $post_1_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_1_ticket_2, $capacity_meta_key, 20 );
		update_post_meta( $post_1_ticket_2, '_stock', 20 );

		update_post_meta( $post_1_ticket_3, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-3' );
		update_post_meta( $post_1_ticket_3, $capacity_meta_key, 30 );
		update_post_meta( $post_1_ticket_3, '_stock', 30 );

		// Post 2 set up.
		$post_2_ticket_1 = $this->create_tc_ticket( $post_id_2, 10 );
		$post_2_ticket_2 = $this->create_tc_ticket( $post_id_2, 20 );
		$post_2_ticket_3 = $this->create_tc_ticket( $post_id_2, 30 );
		$post_2_ticket_4 = $this->create_tc_ticket( $post_id_2, 40 );
		$post_2_ticket_5 = $this->create_tc_ticket( $post_id_2, 50 );
		$post_2_ticket_6 = $this->create_tc_ticket( $post_id_2, 60 );

		update_post_meta( $post_id_2, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_2, $capacity_meta_key, 60 );
		update_post_meta( $post_id_2, Global_Stock::GLOBAL_STOCK_LEVEL, 29 );

		update_post_meta( $post_2_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_2_ticket_1, $capacity_meta_key, 10 );
		update_post_meta( $post_2_ticket_1, '_stock', 6 );

		update_post_meta( $post_2_ticket_4, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_2_ticket_4, $capacity_meta_key, 10 );
		update_post_meta( $post_2_ticket_4, '_stock', 6 );

		update_post_meta( $post_2_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_2_ticket_2, $capacity_meta_key, 20 );
		update_post_meta( $post_2_ticket_2, '_stock', 12 );

		update_post_meta( $post_2_ticket_5, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_2_ticket_5, $capacity_meta_key, 20 );
		update_post_meta( $post_2_ticket_5, '_stock', 12 );

		update_post_meta( $post_2_ticket_3, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-3' );
		update_post_meta( $post_2_ticket_3, $capacity_meta_key, 30 );
		update_post_meta( $post_2_ticket_3, '_stock', 11 );

		update_post_meta( $post_2_ticket_6, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-3' );
		update_post_meta( $post_2_ticket_6, $capacity_meta_key, 30 );
		update_post_meta( $post_2_ticket_6, '_stock', 11 );

		// Post 3 set up.
		$post_3_ticket_1 = $this->create_tc_ticket( $post_id_3, 10 );
		$post_3_ticket_2 = $this->create_tc_ticket( $post_id_3, 20 );

		update_post_meta( $post_id_3, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_3, $capacity_meta_key, 30 );
		update_post_meta( $post_id_3, Global_Stock::GLOBAL_STOCK_LEVEL, 30 );

		update_post_meta( $post_3_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_3_ticket_1, $capacity_meta_key, 10 );
		update_post_meta( $post_3_ticket_1, '_stock', 10 );

		update_post_meta( $post_3_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_3_ticket_2, $capacity_meta_key, 20 );
		update_post_meta( $post_3_ticket_2, '_stock', 20 );

		// Post 4 set up.
		$post_4_ticket_1 = $this->create_tc_ticket( $post_id_4, 10 );
		$post_4_ticket_2 = $this->create_tc_ticket( $post_id_4, 20 );

		update_post_meta( $post_id_4, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_4, $capacity_meta_key, 30 );
		update_post_meta( $post_id_4, Global_Stock::GLOBAL_STOCK_LEVEL, 22 );

		update_post_meta( $post_4_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_4_ticket_1, $capacity_meta_key, 10 );
		update_post_meta( $post_4_ticket_1, '_stock', 7 );

		update_post_meta( $post_4_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_4_ticket_2, $capacity_meta_key, 20 );
		update_post_meta( $post_4_ticket_2, '_stock', 15 );

		// Post 5 set up.
		$post_5_ticket_1 = $this->create_tc_ticket( $post_id_5, 10 );
		$post_5_ticket_2 = $this->create_tc_ticket( $post_id_5, 20 );

		update_post_meta( $post_id_5, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_5, $capacity_meta_key, 50 );
		update_post_meta( $post_id_5, Global_Stock::GLOBAL_STOCK_LEVEL, 50 );

		update_post_meta( $post_5_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-3' );
		update_post_meta( $post_5_ticket_1, $capacity_meta_key, 30 );
		update_post_meta( $post_5_ticket_1, '_stock', 30 );

		update_post_meta( $post_5_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_5_ticket_2, $capacity_meta_key, 20 );
		update_post_meta( $post_5_ticket_2, '_stock', 20 );

		// Post 6 set up.
		$post_6_ticket_1 = $this->create_tc_ticket( $post_id_6, 10 );
		$post_6_ticket_2 = $this->create_tc_ticket( $post_id_6, 20 );

		update_post_meta( $post_id_6, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_6, $capacity_meta_key, 50 );
		update_post_meta( $post_id_6, Global_Stock::GLOBAL_STOCK_LEVEL, 41 );

		update_post_meta( $post_6_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-3' );
		update_post_meta( $post_6_ticket_1, $capacity_meta_key, 30 );
		update_post_meta( $post_6_ticket_1, '_stock', 25 );

		update_post_meta( $post_6_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_6_ticket_2, $capacity_meta_key, 20 );
		update_post_meta( $post_6_ticket_2, '_stock', 16 );

		// Post 7 set up.
		$post_7_ticket_1 = $this->create_tc_ticket( $post_id_7, 10 );

		update_post_meta( $post_id_7, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_7, $capacity_meta_key, 20 );
		update_post_meta( $post_id_7, Global_Stock::GLOBAL_STOCK_LEVEL, 20 );

		update_post_meta( $post_7_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_7_ticket_1, $capacity_meta_key, 20 );
		update_post_meta( $post_7_ticket_1, '_stock', 20 );

		// Post 8 set up.
		$post_8_ticket_1 = $this->create_tc_ticket( $post_id_8, 10 );

		update_post_meta( $post_id_8, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_8, $capacity_meta_key, 20 );
		update_post_meta( $post_id_8, Global_Stock::GLOBAL_STOCK_LEVEL, 12 );

		update_post_meta( $post_8_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_8_ticket_1, $capacity_meta_key, 20 );
		update_post_meta( $post_8_ticket_1, '_stock', 12 );

		// Post 9 set up.
		$post_9_ticket_1 = $this->create_tc_ticket( $post_id_9, 10 );

		update_post_meta( $post_id_9, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_9, $capacity_meta_key, 10 );
		update_post_meta( $post_id_9, Global_Stock::GLOBAL_STOCK_LEVEL, 10 );

		update_post_meta( $post_9_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_9_ticket_1, $capacity_meta_key, 10 );
		update_post_meta( $post_9_ticket_1, '_stock', 10 );

		// Post 10 set up.
		$post_10_ticket_1 = $this->create_tc_ticket( $post_id_10, 10 );

		update_post_meta( $post_id_10, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_10, $capacity_meta_key, 10 );
		update_post_meta( $post_id_10, Global_Stock::GLOBAL_STOCK_LEVEL, 8 );

		update_post_meta( $post_10_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_10_ticket_1, $capacity_meta_key, 10 );
		update_post_meta( $post_10_ticket_1, '_stock', 8 );

		// Start testing!
		// We need to mock the update of seat type 2 to simulate the deletion of the seat type.
		update_post_meta( $post_1_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_2_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_2_ticket_5, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_3_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_4_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_5_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_6_ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_7_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_8_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );

		$originals = [
			$post_1_ticket_1,
			$post_2_ticket_1,
			$post_2_ticket_4,
			$post_3_ticket_1,
			$post_4_ticket_1,
			$post_9_ticket_1,
			$post_10_ticket_1,
		];

		// There should be 16 updated tickets because of seat-type-uuid-2 seat type deletion.
		$this->assertEquals( 16, $seat_types->update_tickets_with_calculated_stock_and_capacity( 'seat-type-uuid-1', 30, $originals ) );

		// Post 1 checks
		$this->assertEquals( 60, get_post_meta( $post_id_1, $capacity_meta_key, true ) );
		$this->assertEquals( 60, get_post_meta( $post_id_1, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 30, get_post_meta( $post_1_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_1_ticket_1, '_stock', true ) );
		$this->assertEquals( 30, get_post_meta( $post_1_ticket_2, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_1_ticket_2, '_stock', true ) );
		$this->assertEquals( 30, get_post_meta( $post_1_ticket_3, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_1_ticket_3, '_stock', true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_1_ticket_1, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_1_ticket_2, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'seat-type-uuid-3', get_post_meta( $post_1_ticket_3, Meta::META_KEY_SEAT_TYPE, true ) );

		// Post 2 checks
		$this->assertEquals( 60, get_post_meta( $post_id_2, $capacity_meta_key, true ) );
		$this->assertEquals( 29, get_post_meta( $post_id_2, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 30, get_post_meta( $post_2_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 18, get_post_meta( $post_2_ticket_1, '_stock', true ) );
		$this->assertEquals( 30, get_post_meta( $post_2_ticket_2, $capacity_meta_key, true ) );
		$this->assertEquals( 18, get_post_meta( $post_2_ticket_2, '_stock', true ) );
		$this->assertEquals( 30, get_post_meta( $post_2_ticket_3, $capacity_meta_key, true ) );
		$this->assertEquals( 11, get_post_meta( $post_2_ticket_3, '_stock', true ) );
		$this->assertEquals( 30, get_post_meta( $post_2_ticket_4, $capacity_meta_key, true ) );
		$this->assertEquals( 18, get_post_meta( $post_2_ticket_4, '_stock', true ) );
		$this->assertEquals( 30, get_post_meta( $post_2_ticket_5, $capacity_meta_key, true ) );
		$this->assertEquals( 18, get_post_meta( $post_2_ticket_5, '_stock', true ) );
		$this->assertEquals( 30, get_post_meta( $post_2_ticket_6, $capacity_meta_key, true ) );
		$this->assertEquals( 11, get_post_meta( $post_2_ticket_6, '_stock', true ) );

		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_2_ticket_1, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_2_ticket_2, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'seat-type-uuid-3', get_post_meta( $post_2_ticket_3, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_2_ticket_4, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_2_ticket_5, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'seat-type-uuid-3', get_post_meta( $post_2_ticket_6, Meta::META_KEY_SEAT_TYPE, true ) );

		// Post 3 checks
		$this->assertEquals( 30, get_post_meta( $post_id_3, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_id_3, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 30, get_post_meta( $post_3_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_3_ticket_1, '_stock', true ) );
		$this->assertEquals( 30, get_post_meta( $post_3_ticket_2, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_3_ticket_2, '_stock', true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_3_ticket_1, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_3_ticket_2, Meta::META_KEY_SEAT_TYPE, true ) );

		// Post 4 checks
		$this->assertEquals( 30, get_post_meta( $post_id_4, $capacity_meta_key, true ) );
		$this->assertEquals( 22, get_post_meta( $post_id_4, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 30, get_post_meta( $post_4_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 22, get_post_meta( $post_4_ticket_1, '_stock', true ) );
		$this->assertEquals( 30, get_post_meta( $post_4_ticket_2, $capacity_meta_key, true ) );
		$this->assertEquals( 22, get_post_meta( $post_4_ticket_2, '_stock', true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_4_ticket_1, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_4_ticket_2, Meta::META_KEY_SEAT_TYPE, true ) );

		// Post 5 checks
		$this->assertEquals( 60, get_post_meta( $post_id_5, $capacity_meta_key, true ) );
		$this->assertEquals( 60, get_post_meta( $post_id_5, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 30, get_post_meta( $post_5_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_5_ticket_1, '_stock', true ) );
		$this->assertEquals( 30, get_post_meta( $post_5_ticket_2, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_5_ticket_2, '_stock', true ) );
		$this->assertEquals( 'seat-type-uuid-3', get_post_meta( $post_5_ticket_1, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_5_ticket_2, Meta::META_KEY_SEAT_TYPE, true ) );

		// Post 6 checks
		$this->assertEquals( 60, get_post_meta( $post_id_6, $capacity_meta_key, true ) );
		$this->assertEquals( 51, get_post_meta( $post_id_6, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 30, get_post_meta( $post_6_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 25, get_post_meta( $post_6_ticket_1, '_stock', true ) );
		$this->assertEquals( 30, get_post_meta( $post_6_ticket_2, $capacity_meta_key, true ) );
		$this->assertEquals( 26, get_post_meta( $post_6_ticket_2, '_stock', true ) );
		$this->assertEquals( 'seat-type-uuid-3', get_post_meta( $post_6_ticket_1, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_6_ticket_2, Meta::META_KEY_SEAT_TYPE, true ) );

		// Post 7 checks
		$this->assertEquals( 30, get_post_meta( $post_id_7, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_id_7, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 30, get_post_meta( $post_7_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_7_ticket_1, '_stock', true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_7_ticket_1, Meta::META_KEY_SEAT_TYPE, true ) );

		// Post 8 checks
		$this->assertEquals( 30, get_post_meta( $post_id_8, $capacity_meta_key, true ) );
		$this->assertEquals( 22, get_post_meta( $post_id_8, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 30, get_post_meta( $post_8_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 22, get_post_meta( $post_8_ticket_1, '_stock', true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_8_ticket_1, Meta::META_KEY_SEAT_TYPE, true ) );

		// Post 9 checks
		$this->assertEquals( 30, get_post_meta( $post_id_9, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_id_9, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 30, get_post_meta( $post_9_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_9_ticket_1, '_stock', true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_9_ticket_1, Meta::META_KEY_SEAT_TYPE, true ) );

		// Post 10 checks
		$this->assertEquals( 30, get_post_meta( $post_id_10, $capacity_meta_key, true ) );
		$this->assertEquals( 28, get_post_meta( $post_id_10, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 30, get_post_meta( $post_10_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 28, get_post_meta( $post_10_ticket_1, '_stock', true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_10_ticket_1, Meta::META_KEY_SEAT_TYPE, true ) );
	}

	public function test_update_tickets_with_calculated_stock_and_capacity_without_originals(): void {
		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler   = tribe( 'tickets.handler' );
		$capacity_meta_key = $tickets_handler->key_capacity;
		// Create the 2 seat types.
		Seat_Types::insert_rows_from_service(
			[
				[
					'id'       => 'seat-type-uuid-1',
					'name'     => 'Seat Type 1',
					'seats'    => 10,
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-1',
				],
				[
					'id'       => 'seat-type-uuid-2',
					'name'     => 'Seat Type 2',
					'seats'    => 20,
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-1',
				]
			]
		);

		$seat_types = tribe( Seat_Types::class );

		[
			$post_id_1,
			$post_id_2,
		] = static::factory()->post->create_many( 4 );

		// Post 2 set up.
		$post_1_ticket_1 = $this->create_tc_ticket( $post_id_1, 20 );

		update_post_meta( $post_id_1, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_1, $capacity_meta_key, 20 );
		update_post_meta( $post_id_1, Global_Stock::GLOBAL_STOCK_LEVEL, 20 );

		update_post_meta( $post_1_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_1_ticket_1, $capacity_meta_key, 20 );
		update_post_meta( $post_1_ticket_1, '_stock', 20 );

		// Post 4 set up.
		$post_2_ticket_1 = $this->create_tc_ticket( $post_id_2, 10 );

		update_post_meta( $post_id_2, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_2, $capacity_meta_key, 20 );
		update_post_meta( $post_id_2, Global_Stock::GLOBAL_STOCK_LEVEL, 16 );

		update_post_meta( $post_2_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		update_post_meta( $post_2_ticket_1, $capacity_meta_key, 20 );
		update_post_meta( $post_2_ticket_1, '_stock', 16 );

		// Start testing!
		// We need to mock the update of seat type 2 to simulate the deletion of the seat type.
		update_post_meta( $post_1_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		update_post_meta( $post_2_ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );

		// There should be 2 updated tickets because of seat-type-uuid-2 seat type deletion.
		$this->assertEquals( 2, $seat_types->update_tickets_with_calculated_stock_and_capacity( 'seat-type-uuid-1', 30, [] ) );

		// Post 2 checks
		$this->assertEquals( 30, get_post_meta( $post_id_1, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_id_1, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 30, get_post_meta( $post_1_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $post_1_ticket_1, '_stock', true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_1_ticket_1, Meta::META_KEY_SEAT_TYPE, true ) );

		// Post 4 checks
		$this->assertEquals( 30, get_post_meta( $post_id_2, $capacity_meta_key, true ) );
		$this->assertEquals( 26, get_post_meta( $post_id_2, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 30, get_post_meta( $post_2_ticket_1, $capacity_meta_key, true ) );
		$this->assertEquals( 26, get_post_meta( $post_2_ticket_1, '_stock', true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $post_2_ticket_1, Meta::META_KEY_SEAT_TYPE, true ) );
	}
	
	public function test_get_in_option_format() {
		// Create seat types with different name value starting with different letters in random order.
		Seat_Types::insert_rows_from_service(
			[
				[
					'id'       => 'seat-type-uuid-1',
					'name'     => 'Mangoe',
					'seats'    => 10,
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-1',
				],
				[
					'id'       => 'seat-type-uuid-2',
					'name'     => 'Banana',
					'seats'    => 20,
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-1',
				],
				[
					'id'       => 'seat-type-uuid-3',
					'name'     => 'apple',
					'seats'    => 30,
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-1',
				],
				[
					'id'       => 'seat-type-uuid-4',
					'name'     => 'wolves',
					'seats'    => 40,
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-1',
				],
				[
					'id'       => 'seat-type-uuid-5',
					'name'     => '5guys',
					'seats'    => 50,
					'mapId'    => 'map-uuid-1',
					'layoutId' => 'layout-uuid-1',
				],
			]
		);
		
		set_transient( Seat_Types::update_transient_name(), time() );
		
		$seat_types = tribe( Seat_Types::class );
		
		// Get the seat types in option format and match snapshot.
		$this->assertMatchesJsonSnapshot( wp_json_encode( $seat_types->get_in_option_format( [ 'layout-uuid-1' ] ), JSON_SNAPSHOT_OPTIONS ) );
	}
}
