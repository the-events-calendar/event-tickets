<?php

namespace TEC\Tickets\Seating\Admin;

use Codeception\TestCase\WPTestCase;
use PHPUnit\Framework\Assert;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Seating\Admin\Tabs\Layout_Edit;
use TEC\Tickets\Seating\Admin\Tabs\Maps as Maps_Tab;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Service\Service;
use Tribe\Tests\Traits\Service_Locator_Mocks;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Tickets\Seating\Service\Maps as Maps_Service;
use TEC\Tickets\Seating\Service\Layouts as Layouts_Service;
use TEC\Tickets\Seating\Admin\Tabs\Layouts as Layouts_Tab;
use TEC\Tickets\Seating\Tables\Maps as Maps_Table;
use TEC\Tickets\Seating\Tables\Layouts as Layouts_Table;
use TEC\Tickets\Seating\Tables\Seat_Types as Seat_Types_Table;
use Tribe__Tickets__Data_API as Data_API;

class Maps_Layout_Homepage_Test extends WPTestCase {
	use SnapshotAssertions;
	use With_Uopz;
	use Service_Locator_Mocks;

	/**
	 * @before
	 */
	public function mock_user() {
		// Become administrator.
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * @before
	 * @after
	 */
	public function truncate_tables():void{
		tribe( Maps_Table::class )->truncate();
		tribe( Layouts_Table::class )->truncate();
		tribe( Seat_Types_Table::class )->truncate();
	}

	public function test_empty_seating_configurations(): void {
		ob_start();
		tribe( Maps_Layouts_Home_Page::class )->render();
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function test_empty_layouts(): void {
		ob_start();
		tribe( Maps_Layouts_Home_Page::class )->render();
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function test_maps_tab_card_listing_with_1_map() {
		Maps_Service::insert_rows_from_service(
			[
				[
					'id'            => '1',
					'name'          => 'Map 1',
					'seats'         => 10,
					'screenshotUrl' => 'https://example.com/map-1-thumbnail',
				],
			]
		);
		// We've just updated the Maps, no need to run the update against the service.
		set_transient( Maps_Service::update_transient_name(), time() - 1 );

		ob_start();
		tribe( Maps_Tab::class )->render();
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function test_maps_tab_card_listing() {
		Maps_Service::insert_rows_from_service(
			[
				[
					'id'            => 'map-uuid-1',
					'name'          => 'Map 1',
					'seats'         => 10,
					'screenshotUrl' => 'https://example.com/map-1-thumbnail',
				],
				[
					'id'            => 'map-uuid-2',
					'name'          => 'Map 2',
					'seats'         => 20,
					'screenshotUrl' => 'https://example.com/map-2-thumbnail',
				],
				[
					'id'            => 'map-uuid-3',
					'name'          => 'Map 3',
					'seats'         => 100,
					'screenshotUrl' => 'https://example.com/map-3-thumbnail',
				],
			]
		);
		// Create posts associated with the maps: 1 for layout-uuid-1, 2 for layout-uuid-2, 0 for layout-uuid-2.
		[$post_1, $post_2, $post_3] = self::factory()->post->create_many(5);
		update_post_meta( $post_1, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_2, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-2' );
		update_post_meta( $post_3, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-2' );
		// We've just updated the Maps, no need to run the update against the service.
		set_transient( Maps_Service::update_transient_name(), time() - 1 );

		ob_start();
		tribe( Maps_Tab::class )->render();
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function test_empty_layouts_tab() {
		ob_start();
		tribe( Layouts_Tab::class )->render();
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function test_layouts_tab_card_listing_with_1_layout() {
		Maps_Service::insert_rows_from_service(
			[
				[
					'id'            => 'map-uuid-1',
					'name'          => 'Map 1',
					'seats'         => 10,
					'screenshotUrl' => 'https://example.com/map-1-thumbnail',
				],
				[
					'id'            => 'map-uuid-2',
					'name'          => 'Map 2',
					'seats'         => 20,
					'screenshotUrl' => 'https://example.com/map-2-thumbnail',
				],
				[
					'id'            => 'map-uuid-3',
					'name'          => 'Map 3',
					'seats'         => 100,
					'screenshotUrl' => 'https://example.com/map-3-thumbnail',
				],
			]
		);

		// We've just updated the Maps, no need to run the update against the service.
		set_transient( Maps_Service::update_transient_name(), time() - 1 );

		Layouts_Service::insert_rows_from_service(
			[
				[
					'id'            => '1',
					'name'          => 'Layout 1',
					'mapId'         => 'a',
					'seats'         => 100,
					'screenshotUrl' => 'https://example.com/150',
					'createdDate'   => '1716901924',
				],
			]
		);
		// We've just updated the Layouts, no need to run the update against the service.
		set_transient( Layouts_Service::update_transient_name(), time() - 1 );

		ob_start();
		tribe( Layouts_Tab::class )->render();
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function test_layouts_tab_card_listing() {
		Maps_Service::insert_rows_from_service(
			[
				[
					'id'            => 'map-uuid-1',
					'name'          => 'Map 1',
					'seats'         => 10,
					'screenshotUrl' => 'https://example.com/map-1-thumbnail',
				],
				[
					'id'            => 'map-uuid-2',
					'name'          => 'Map 2',
					'seats'         => 20,
					'screenshotUrl' => 'https://example.com/map-2-thumbnail',
				],
				[
					'id'            => 'map-uuid-3',
					'name'          => 'Map 3',
					'seats'         => 100,
					'screenshotUrl' => 'https://example.com/map-3-thumbnail',
				],
			]
		);

		// We've just updated the Maps, no need to run the update against the service.
		set_transient( Maps_Service::update_transient_name(), time() - 1 );

		Layouts_Service::insert_rows_from_service(
			[
				[
					'id'            => 'layout-uuid-1',
					'name'          => 'Layout 1',
					'mapId'         => 'map-uuid-1',
					'seats'         => 100,
					'screenshotUrl' => 'https://example.com/150',
					'createdDate'   => '1716901924',
				],
				[
					'id'            => 'layout-uuid-2',
					'name'          => 'Layout 2',
					'mapId'         => 'map-uuid-2',
					'seats'         => 200,
					'screenshotUrl' => 'https://example.com/150',
					'createdDate'   => '1716901924',
				],
				[
					'id'            => 'layout-uuid-3',
					'name'          => 'Layout 3',
					'mapId'         => 'map-uuid-3',
					'seats'         => 300,
					'screenshotUrl' => 'https://example.com/150',
					'createdDate'   => '1716901924',
				],
			]
		);
		// We've just updated the Layouts, no need to run the update against the service.
		set_transient( Layouts_Service::update_transient_name(), time() - 1 );

		[ $post_a, $post_b, $post_c ] = static::factory()->post->create_many( 3 );

		// Layout 2 is associated with 1 event.
		update_post_meta( $post_a, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post_a, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-2' );

		// Layout 3 is associated with 2 events.
		update_post_meta( $post_b, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post_b, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-3' );
		update_post_meta( $post_c, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post_c, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-3' );

		ob_start();
		tribe( Layouts_Tab::class )->render();
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function test_get_maps_home_url(): void {
		$maps_layouts_home_page = tribe( Maps_Layouts_Home_Page::class );

		$this->assertEquals(
			'http://wordpress.test/wp-admin/admin.php?page=tec-tickets-seating&tab=maps',
			$maps_layouts_home_page->get_maps_home_url()
		);
	}

	public function test_get_layouts_home_url(): void {
		$maps_layouts_home_page = tribe( Maps_Layouts_Home_Page::class );

		$this->assertEquals(
			'http://wordpress.test/wp-admin/admin.php?page=tec-tickets-seating&tab=layouts',
			$maps_layouts_home_page->get_layouts_home_url()
		);
	}

	public function test_layout_edit(): void {
		$_GET['tab'] = Layout_Edit::get_id();
		$_GET['layoutId'] = 'some-layout-id';
		$_GET['mapId'] = 'some-map-id';
		$this->mock_singleton_service(
			Service::class,
			[
				'get_ephemeral_token' => function ( $expiration, $scope ) {
					Assert::assertEquals( 6 * HOUR_IN_SECONDS, $expiration );
					Assert::assertEquals( 'admin', $scope );

					return 'some-ephemeral-token';
				}
			]
		);
		$maps_layouts_home_page = tribe( Maps_Layouts_Home_Page::class );

		ob_start();
		$maps_layouts_home_page->render();
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function test_map_edit(): void {
		$_GET['tab']  = 'map-edit';
		$_GET['page'] = 'tec-tickets-seating';
		$this->mock_singleton_service(
			Service::class,
			[
				'get_ephemeral_token' => function ( $expiration, $scope ) {
					Assert::assertEquals( 6 * HOUR_IN_SECONDS, $expiration );
					Assert::assertEquals( 'admin', $scope );

					return 'some-ephemeral-token';
				}
			]
		);
		$maps_layouts_home_page = tribe( Maps_Layouts_Home_Page::class );

		ob_start();
		$maps_layouts_home_page->render();
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
