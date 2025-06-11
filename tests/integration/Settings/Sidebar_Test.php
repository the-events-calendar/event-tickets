<?php

namespace TEC\Tickets_Plus\Test\Integration\Settings;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Codeception\TestCase\WPTestCase;
use TEC\Common\Admin\Settings_Sidebar;
use Tribe__Settings_Tab;

/**
 * Class Sidebar_Test
 *
 * @package TEC\Tickets_Plus\Tests\Integration\Settings
 */
class Sidebar_Test extends WPTestCase {
	use SnapshotAssertions;

	/**
	 * Test that the sidebar renders correctly.
	 *
	 * @test
	 */
	public function should_render_sidebar(): void {
		// Set up the admin page context.
		set_current_screen( 'admin_page_tec-events-settings' );

		// Create a settings tab.
		$tab = new Tribe__Settings_Tab( 'test-tab', 'Test Tab' );

		// Create and set the default sidebar.
		$sidebar = new Settings_Sidebar();
		Tribe__Settings_Tab::set_default_sidebar( $sidebar );

		// Trigger the settings tabs action.
		do_action( 'tribe_settings_do_tabs' );

		// Start output buffering to capture the rendered sidebar.
		ob_start();
		$tab->render_sidebar();
		$output = ob_get_clean();

		// Assert the snapshot.
		$this->assertMatchesHtmlSnapshot( $output );
	}
}
