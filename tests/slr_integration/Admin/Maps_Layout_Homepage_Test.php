<?php

namespace TEC\Tickets\Seating\Admin;

use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Seating\Admin\Tabs\Maps as Maps_Tab;
use TEC\Tickets\Seating\Tables\Maps;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Tickets\Seating\Service\Maps as Maps_Service;

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

	public function test_maps_tab_card_listing() {
		Maps_Service::insert_rows_from_service( [
			[
				'id'            => '1',
				'name'          => 'Map 1',
				'seats'         => 10,
				'screenshotUrl' => 'https://example.com/map-1-thumbnail'
			],
			[
				'id'            => '2',
				'name'          => 'Map 2',
				'seats'         => 20,
				'screenshotUrl' => 'https://example.com/map-2-thumbnail'
			],
			[
				'id'            => '3',
				'name'          => 'Map 3',
				'seats'         => 100,
				'screenshotUrl' => 'https://example.com/map-3-thumbnail'
			],
		] );
		// We've just updated the Maps, no need to run the update against the service.
		set_transient( Maps_Service::update_transient_name(), time() - 1 );

		ob_start();
		tribe( Maps_Tab::class )->render();
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
