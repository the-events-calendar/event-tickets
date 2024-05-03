<?php

namespace TEC\Events_Assigned_Seating\Admin;

use lucatume\WPBrowser\Traits\UopzFunctions;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events_Assigned_Seating\Admin;
use TEC\Events_Assigned_Seating\Admin\Tabs\Layout_Edit;
use TEC\Events_Assigned_Seating\Admin\Tabs\Layouts;
use TEC\Events_Assigned_Seating\Admin\Tabs\Map_Edit;
use TEC\Events_Assigned_Seating\Admin\Tabs\Maps;
use TEC\Events_Assigned_Seating\Service\Service;

class Admin_Test extends Controller_Test_Case {
	use UopzFunctions;
	use SnapshotAssertions;

	protected string $controller_class = Admin::class;

	/**
	 * @before
	 */
	public function mock_admin_context(): void {
		$this->setFunctionReturn( 'is_admin', true );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function tabs_data_provider(): array {
		return [
			'no tab'          => [
				'tab' => null,
			],
			'maps tab'        => [
				'tab' => Maps::get_id(),
			],
			'layouts tab'     => [
				'tab' => Layouts::get_id(),
			],
			'map edit tab'    => [
				'tab' => Map_Edit::get_id(),
			],
			'layout edit tab' => [
				'tab' => Layout_Edit::get_id(),
			],
		];
	}

	/**
	 * It should add the sub-menu page
	 *
	 * @test
	 * @dataProvider tabs_data_provider
	 */
	public function should_add_the_sub_menu_page( string $tab = null ): void {
		$this->test_services->singleton( Service::class, function () {
			return $this->make( Service::class, [
				'frontend_base_url'    => 'https://service.test.local',
				'get_ephemeral_token' => 'test-ephemeral-token'
			], $this );
		} );

		if ( $tab === null ) {
			// Simulate a request to the Seat Configurations and Layouts home page without specifying a tab.
			unset( $_GET['tab'] );
		} else {
			// Simulate a request to the Seat Configurations and Layouts home page for a specific tab.
			$_GET['tab'] = $tab;
		}

		$controller = $this->make_controller();
		$controller->register();

		// Register the Admin controller sub-menu page.
		do_action( 'admin_menu' );
		// Render the page.
		ob_start();
		do_action( 'tickets_page_tec-events-assigned-seating' );
		$this->assertMatchesHtmlSnapshot( ob_get_clean() );
	}
}