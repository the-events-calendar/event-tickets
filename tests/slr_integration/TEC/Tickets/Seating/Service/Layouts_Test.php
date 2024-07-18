<?php

namespace TEC\Tickets\Seating\Service;

use TEC\Tickets\Seating\Tables\Layouts as Layouts_Table;
use TEC\Tickets\Seating\Tables\Maps as Maps_Table;
use TEC\Tickets\Seating\Tables\Seat_Types as Seat_Types_Table;
use Tribe\Tests\Traits\WP_Remote_Mocks;

class Layouts_Test extends \Codeception\TestCase\WPTestCase {
	use WP_Remote_Mocks;

	/**
	 * @before
	 * @after
	 */
	public function truncate_custom_tables(): void {
		Seat_Types_Table::truncate();
		Layouts_Table::truncate();
		Maps_Table::truncate();
		delete_transient( Seat_Types::update_transient_name() );
		delete_transient( Layouts::update_transient_name() );
	}

	private function given_some_maps_layouts_in_db(): void {
		Maps_Table::insert_many( [
			[
				'id'             => 'some-map-1',
				'name'           => 'Some Map 1',
				'seats'          => 10,
				'screenshot_url' => 'https://example.com/some-map-1.png',
			],
			[
				'id'             => 'some-map-2',
				'name'           => 'Some Map 2',
				'seats'          => 20,
				'screenshot_url' => 'https://example.com/some-map-2.png',
			],
			[
				'id'             => 'some-map-3',
				'name'           => 'Some Map 3',
				'seats'          => 30,
				'screenshot_url' => 'https://example.com/some-map-3.png',
			],
		] );

		Layouts_Table::insert_many( [
			[
				'id'             => 'some-layout-1',
				'name'           => 'Some Layout 1',
				'seats'          => 10,
				'created_date'   => time() * 1000,
				'map'            => 'some-map-1',
				'screenshot_url' => 'https://example.com/some-layouts-1.png',
			],
			[
				'id'             => 'some-layout-2',
				'name'           => 'Some Layout 2',
				'seats'          => 20,
				'created_date'   => time() * 1000,
				'map'            => 'some-map-2',
				'screenshot_url' => 'https://example.com/some-layouts-2.png',
			],
			[
				'id'             => 'some-layout-3',
				'name'           => 'Some Layout 3',
				'seats'          => 30,
				'created_date'   => time() * 1000,
				'map'            => 'some-map-3',
				'screenshot_url' => 'https://example.com/some-layouts-3.png',
			],
		] );

		Seat_Types_Table::insert_many( [
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

	public function test_invalidate_cache(): void {
		$this->assertEmpty( get_transient( Layouts::update_transient_name() ) );
		$this->assertEmpty( get_transient( Seat_Types::update_transient_name() ) );

		$this->given_some_maps_layouts_in_db();
		set_transient( Seat_Types::update_transient_name(), time() );
		set_transient( Layouts::update_transient_name(), time() );
		set_transient( Maps::update_transient_name(), time() );

		$this->assertCount( 3, iterator_to_array( Maps_Table::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts_Table::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Seat_Types_Table::fetch_all() ) );

		$this->assertTrue( Layouts::invalidate_cache() );

		$this->assertCount( 3, iterator_to_array( Maps_Table::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Layouts_Table::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->assertEmpty( get_transient( Layouts::update_transient_name() ) );
		$this->assertEmpty( get_transient( Seat_Types::update_transient_name() ) );
		$this->assertEqualsWithDelta( time(), get_transient( Maps::update_transient_name() ), 5 );
	}
}