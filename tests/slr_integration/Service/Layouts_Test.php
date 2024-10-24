<?php

namespace TEC\Tickets\Seating\Service;

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Tables\Layouts as Layouts_Table;
use TEC\Tickets\Seating\Tables\Maps as Maps_Table;
use TEC\Tickets\Seating\Tables\Seat_Types as Seat_Types_Table;
use TEC\Tickets\Seating\Tests\Integration\Truncates_Custom_Tables;
use Tribe\Tests\Traits\WP_Remote_Mocks;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Global_Stock as Global_Stock;

class Layouts_Test extends \Codeception\TestCase\WPTestCase {
	use WP_Remote_Mocks;
	use Truncates_Custom_Tables;

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

	public function test_invalidate_cache(): void {
		$this->given_some_maps_layouts_in_db();
		set_transient( Seat_Types::update_transient_name(), time() );
		set_transient( Layouts::update_transient_name(), time() );
		set_transient( Maps::update_transient_name(), time() );

		$this->assertCount( 3, iterator_to_array( Maps_Table::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts_Table::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Seat_Types_Table::fetch_all() ) );

		// Invalidate the cache, do not truncate the table.
		$this->assertTrue( Layouts::invalidate_cache( false ) );

		$this->assertCount( 3, iterator_to_array( Maps_Table::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Layouts_Table::fetch_all() ) );
		$this->assertCount( 3, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->assertEmpty( get_transient( Layouts::update_transient_name() ) );
		$this->assertEmpty( get_transient( Seat_Types::update_transient_name() ) );
		$this->assertEqualsWithDelta( time(), get_transient( Maps::update_transient_name() ), 5 );

		// Invalidate the cache and truncate the table.
		$this->assertTrue( Layouts::invalidate_cache( true ) );

		$this->assertCount( 3, iterator_to_array( Maps_Table::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Layouts_Table::fetch_all() ) );
		$this->assertCount( 0, iterator_to_array( Seat_Types_Table::fetch_all() ) );
		$this->assertEmpty( get_transient( Layouts::update_transient_name() ) );
		$this->assertEmpty( get_transient( Seat_Types::update_transient_name() ) );
		$this->assertEqualsWithDelta( time(), get_transient( Maps::update_transient_name() ), 5 );
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

	public function test_get_associated_posts_by_id(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		register_post_type( 'test_venue' );
		tribe_update_option( 'ticket-enabled-post-types', [ 'post', 'page' ] );

		$this->assertEquals( 0, Layouts::get_associated_posts_by_id( 'some-layout-1' ) );
		$this->assertEquals( 0, Layouts::get_associated_posts_by_id( 'some-layout-2' ) );
		$this->assertEquals( 0, Layouts::get_associated_posts_by_id( 'some-layout-3' ) );

		$venue_1 = self::factory()->post->create( [ 'post_type' => 'test_venue' ] );
		update_post_meta( $venue_1, Meta::META_KEY_LAYOUT_ID, 'some-layout-1' );
		$venue_2 = self::factory()->post->create( [ 'post_type' => 'test_venue' ] );
		update_post_meta( $venue_2, Meta::META_KEY_LAYOUT_ID, 'some-layout-1' );
		$venue_3 = self::factory()->post->create( [ 'post_type' => 'test_venue' ] );
		update_post_meta( $venue_3, Meta::META_KEY_LAYOUT_ID, 'some-layout-2' );
		$venue_4 = self::factory()->post->create( [ 'post_type' => 'test_venue' ] );
		update_post_meta( $venue_4, Meta::META_KEY_LAYOUT_ID, 'some-layout-1' );

		$this->assertEquals( 0, Layouts::get_associated_posts_by_id( 'some-layout-1' ) );
		$this->assertEquals( 0, Layouts::get_associated_posts_by_id( 'some-layout-2' ) );
		$this->assertEquals( 0, Layouts::get_associated_posts_by_id( 'some-layout-3' ) );

		$post_1 = self::factory()->post->create( [ 'post_type' => 'post' ] );
		update_post_meta( $post_1, Meta::META_KEY_LAYOUT_ID, 'some-layout-1' );
		$post_2 = self::factory()->post->create( [ 'post_type' => 'post' ] );
		update_post_meta( $post_2, Meta::META_KEY_LAYOUT_ID, 'some-layout-1' );
		$post_3 = self::factory()->post->create( [ 'post_type' => 'post' ] );
		update_post_meta( $post_3, Meta::META_KEY_LAYOUT_ID, 'some-layout-2' );
		$post_4 = self::factory()->post->create( [ 'post_type' => 'post' ] );
		update_post_meta( $post_4, Meta::META_KEY_LAYOUT_ID, 'some-layout-1' );

		$this->assertEquals( 3, Layouts::get_associated_posts_by_id( 'some-layout-1' ) );
		$this->assertEquals( 1, Layouts::get_associated_posts_by_id( 'some-layout-2' ) );
		$this->assertEquals( 0, Layouts::get_associated_posts_by_id( 'some-layout-3' ) );

		tribe_update_option( 'ticket-enabled-post-types', [ 'post', 'page', 'test_venue' ] );

		$this->assertEquals( 6, Layouts::get_associated_posts_by_id( 'some-layout-1' ) );
		$this->assertEquals( 2, Layouts::get_associated_posts_by_id( 'some-layout-2' ) );
		$this->assertEquals( 0, Layouts::get_associated_posts_by_id( 'some-layout-3' ) );
	}

	public function test_update_posts_capacity(): void {
		// Create some layouts.
		Layouts::insert_rows_from_service( [
			[
				'id'            => 'layout-uuid-1',
				'name'          => 'Layout 1',
				'seats'         => 123,
				'mapId'         => 'map-uuid-1',
				'createdDate'   => microtime( true ),
				'screenshotUrl' => 'https://example.com/layout-1.png',
			],
			[
				'id'            => 'layout-uuid-2',
				'name'          => 'Layout 2',
				'seats'         => 289,
				'mapId'         => 'map-uuid-1',
				'createdDate'   => microtime( true ),
				'screenshotUrl' => 'https://example.com/layout-2.png',
			],
			[
				'id'            => 'layout-uuid-3',
				'name'          => 'Layout 3',
				'seats'         => 366,
				'mapId'         => 'map-uuid-2',
				'createdDate'   => microtime( true ),
				'screenshotUrl' => 'https://example.com/layout-3.png',
			],
		] );
		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler   = tribe( 'tickets.handler' );
		$capacity_meta_key = $tickets_handler->key_capacity;
		// Post 1 does not use ASC and layouts.
		$post_1 = self::factory()->post->create();
		update_post_meta( $post_1, $capacity_meta_key, 100 );
		update_post_meta( $post_1, Global_Stock::GLOBAL_STOCK_LEVEL, 100 );
		// Post 2 uses layout 1.
		$post_2 = self::factory()->post->create( [] );
		update_post_meta( $post_2, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_2, $capacity_meta_key, 123 );
		// The post had no purchased tickets.
		update_post_meta( $post_2, Global_Stock::GLOBAL_STOCK_LEVEL, 123 );
		// Post 3 uses layout 2.
		$post_3 = self::factory()->post->create( [] );
		update_post_meta( $post_3, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-2' );
		update_post_meta( $post_3, $capacity_meta_key, 289 );
		// The post had 89 purchased tickets.
		update_post_meta( $post_3, Global_Stock::GLOBAL_STOCK_LEVEL, 289 - 89 );
		// Post 4 uses layout 3.
		$post_4 = self::factory()->post->create( [] );
		update_post_meta( $post_4, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-3' );
		update_post_meta( $post_4, $capacity_meta_key, 366 );
		// The post had 23 purchased tickets.
		update_post_meta( $post_4, Global_Stock::GLOBAL_STOCK_LEVEL, 366 - 23 );

		$layouts = tribe( Layouts::class );

		// Empty updates.
		$this->assertEquals( 0, $layouts->update_posts_capacity( [] ) );
		$this->assertEquals( 123, get_post_meta( $post_2, $capacity_meta_key, true ) );
		$this->assertEquals( 123, get_post_meta( $post_2, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 289, get_post_meta( $post_3, $capacity_meta_key, true ) );
		$this->assertEquals( 289 - 89, get_post_meta( $post_3, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 366, get_post_meta( $post_4, $capacity_meta_key, true ) );
		$this->assertEquals( 366 - 23, get_post_meta( $post_4, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );

		// Increase the capacity of the first layout by 15
		$this->assertEquals( 1, $layouts->update_posts_capacity( [
			'layout-uuid-1' => 123 + 15,
		] ) );
		$this->assertEquals( 123 + 15, get_post_meta( $post_2, $capacity_meta_key, true ) );
		$this->assertEquals( 123 + 15, get_post_meta( $post_2, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 289, get_post_meta( $post_3, $capacity_meta_key, true ) );
		$this->assertEquals( 289 - 89, get_post_meta( $post_3, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 366, get_post_meta( $post_4, $capacity_meta_key, true ) );
		$this->assertEquals( 366 - 23, get_post_meta( $post_4, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 123 + 15,
			DB::get_var( DB::prepare( "SELECT seats FROM %i WHERE id = %s",
				Layouts_Table::table_name(),
				'layout-uuid-1' ) ) );
		$this->assertEquals( 289,
			DB::get_var( DB::prepare( "SELECT seats FROM %i WHERE id = %s",
				Layouts_Table::table_name(),
				'layout-uuid-2' ) ) );
		$this->assertEquals( 366,
			DB::get_var( DB::prepare( "SELECT seats FROM %i WHERE id = %s",
				Layouts_Table::table_name(),
				'layout-uuid-3' ) ) );

		// Decrease the capacity of the second layout by 150.
		$this->assertEquals( 1, $layouts->update_posts_capacity( [
			'layout-uuid-2' => 289 - 150,
		] ) );
		$this->assertEquals( 123 + 15, get_post_meta( $post_2, $capacity_meta_key, true ) );
		$this->assertEquals( 123 + 15, get_post_meta( $post_2, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 289 - 150, get_post_meta( $post_3, $capacity_meta_key, true ) );
		$this->assertEquals( 289 - 89 - 150, get_post_meta( $post_3, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 366, get_post_meta( $post_4, $capacity_meta_key, true ) );
		$this->assertEquals( 366 - 23, get_post_meta( $post_4, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 123 + 15,
			DB::get_var( DB::prepare( "SELECT seats FROM %i WHERE id = %s",
				Layouts_Table::table_name(),
				'layout-uuid-1' ) ) );
		$this->assertEquals( 289 - 150,
			DB::get_var( DB::prepare( "SELECT seats FROM %i WHERE id = %s",
				Layouts_Table::table_name(),
				'layout-uuid-2' ) ) );
		$this->assertEquals( 366,
			DB::get_var( DB::prepare( "SELECT seats FROM %i WHERE id = %s",
				Layouts_Table::table_name(),
				'layout-uuid-3' ) ) );

		// Increase the capacity of the third layout by 7.
		$this->assertEquals( 1, $layouts->update_posts_capacity( [
			'layout-uuid-3' => 366 + 7,
		] ) );
		$this->assertEquals( 123 + 15, get_post_meta( $post_2, $capacity_meta_key, true ) );
		$this->assertEquals( 123 + 15, get_post_meta( $post_2, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 289 - 150, get_post_meta( $post_3, $capacity_meta_key, true ) );
		$this->assertEquals( 289 - 89 - 150, get_post_meta( $post_3, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 366 + 7, get_post_meta( $post_4, $capacity_meta_key, true ) );
		$this->assertEquals( 366 - 23 + 7, get_post_meta( $post_4, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 123 + 15,
			DB::get_var( DB::prepare( "SELECT seats FROM %i WHERE id = %s",
				Layouts_Table::table_name(),
				'layout-uuid-1' ) ) );
		$this->assertEquals( 289 - 150,
			DB::get_var( DB::prepare( "SELECT seats FROM %i WHERE id = %s",
				Layouts_Table::table_name(),
				'layout-uuid-2' ) ) );
		$this->assertEquals( 366 + 7,
			DB::get_var( DB::prepare( "SELECT seats FROM %i WHERE id = %s",
				Layouts_Table::table_name(),
				'layout-uuid-3' ) ) );
	}
}
