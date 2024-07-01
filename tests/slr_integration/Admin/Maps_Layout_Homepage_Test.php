<?php

namespace TEC\Tickets\Seating\Admin;

use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Seating\Admin\Tabs\Maps as Maps_Tab;
use TEC\Tickets\Seating\Meta;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Tickets\Seating\Service\Maps as Maps_Service;
use TEC\Tickets\Seating\Service\Layouts as Layouts_Service;
use TEC\Tickets\Seating\Admin\Tabs\Layouts as Layouts_Tab;

class Maps_Layout_Homepage_Test extends WPTestCase {
	use SnapshotAssertions;
	use With_Uopz;

	/**
	 * @before
	 */
	public function mock_user() {
		// Become administrator.
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
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
					'id'            => '1',
					'name'          => 'Map 1',
					'seats'         => 10,
					'screenshotUrl' => 'https://example.com/map-1-thumbnail',
				],
				[
					'id'            => '2',
					'name'          => 'Map 2',
					'seats'         => 20,
					'screenshotUrl' => 'https://example.com/map-2-thumbnail',
				],
				[
					'id'            => '3',
					'name'          => 'Map 3',
					'seats'         => 100,
					'screenshotUrl' => 'https://example.com/map-3-thumbnail',
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

	public function test_empty_layouts_tab() {
		ob_start();
		tribe( Layouts_Tab::class )->render();
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function test_layouts_tab_card_listing_with_1_layout() {
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
				[
					'id'            => '2',
					'name'          => 'Layout 2',
					'mapId'         => 'b',
					'seats'         => 200,
					'screenshotUrl' => 'https://example.com/150',
					'createdDate'   => '1716901924',
				],
				[
					'id'            => '3',
					'name'          => 'Layout 3',
					'mapId'         => 'c',
					'seats'         => 300,
					'screenshotUrl' => 'https://example.com/150',
					'createdDate'   => '1716901924',
				],
			]
		);
		// We've just updated the Layouts, no need to run the update against the service.
		set_transient( Layouts_Service::update_transient_name(), time() - 1 );

		[ $post_a, $post_b, $post_c ] = $this->factory()->post->create_many( 3 );

		// Layout 2 is associated with 1 event.
		update_post_meta( $post_a, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post_a, Meta::META_KEY_LAYOUT_ID, '2' );

		// Layout 3 is associated with 2 events.
		update_post_meta( $post_b, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post_b, Meta::META_KEY_LAYOUT_ID, '3' );
		update_post_meta( $post_c, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post_c, Meta::META_KEY_LAYOUT_ID, '3' );

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
}
