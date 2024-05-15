<?php

namespace TEC\Tickets\Seating\Admin;

use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Seating\Admin\Tabs\Map_Card;
use TEC\Tickets\Seating\Admin\Tabs\Maps as Maps_Tab;
use TEC\Tickets\Seating\Service\Maps as Maps_Service;
use Tribe\Tests\Traits\With_Uopz;

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
		$this->set_class_fn_return(
			Maps_Service::class,
			'get_in_card_format',
			[
				new Map_Card( '1', 'Map 1', '10', 'https://example.com/map-1-thumbnail' ),
				new Map_Card( '2', 'Map 2', '20', 'https://example.com/map-2-thumbnail' ),
				new Map_Card( '3', 'Map 3', '100', 'https://example.com/map-3-thumbnail' ),
			] 
		);
		
		ob_start();
		tribe( Maps_Tab::class )->render();
		$html = ob_get_clean();
		
		$this->assertMatchesHtmlSnapshot( $html );
	}
}
