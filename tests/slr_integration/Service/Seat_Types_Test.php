<?php

namespace TEC\Tickets\Seating\Service;

use Codeception\TestCase\WPTestCase;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Tables\Seat_Types as Seat_Types_Table;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Global_Stock as Global_Stock;

class Seat_Types_Test extends WPTestCase {
	use Ticket_Maker;

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
		$this->assertEquals( 30, get_post_meta( $post_id_2, $capacity_meta_key, true ), );
		$this->assertEquals( 30, get_post_meta( $post_id_2, Global_Stock::GLOBAL_STOCK_LEVEL, true ), );
		$this->assertEquals( 70, get_post_meta( $post_id_3, $capacity_meta_key, true ), );
		$this->assertEquals( 70, get_post_meta( $post_id_3, Global_Stock::GLOBAL_STOCK_LEVEL, true ), );
		$this->assertEquals( 23, get_post_meta( $post_2_ticket_1, $capacity_meta_key, true ), );
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
		$this->assertEquals( 30, get_post_meta( $post_id_2, $capacity_meta_key, true ), );
		$this->assertEquals( 30, get_post_meta( $post_id_2, Global_Stock::GLOBAL_STOCK_LEVEL, true ), );
		$this->assertEquals( 70, get_post_meta( $post_id_3, $capacity_meta_key, true ), );
		$this->assertEquals( 70, get_post_meta( $post_id_3, Global_Stock::GLOBAL_STOCK_LEVEL, true ), );
		$this->assertEquals( 23, get_post_meta( $post_2_ticket_1, $capacity_meta_key, true ), );
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
}