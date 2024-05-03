<?php

namespace TEC\Events_Assigned_Seating\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Events_Assigned_Seating\Admin\Tabs\Layouts;

class Maps_Layout_Homepage_Test extends WPTestCase {
	use SnapshotAssertions;

	public function test_empty_seating_configurations(): void {
		// Become administrator.
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Simulate a request to the Seat Configurations and Layouts home page.
		$_GET['page'] = 'tec-events-assigned-seating';

		ob_start();
		tribe( Maps_Layouts_Home_Page::class )->render();
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function test_empty_layouts(): void {
		// Become administrator.
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Simulate a request to the Seat Configurations and Layouts home page.
		$_GET['page'] = 'tec-events-assigned-seating';
		$_GET['tab']  = Layouts::get_id();

		ob_start();
		tribe( Maps_Layouts_Home_Page::class )->render();
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}
}