<?php

namespace TEC\Tickets\Seating\Admin;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Seating\Admin;
use TEC\Tickets\Seating\Admin\Tabs\Layout_Edit;
use TEC\Tickets\Seating\Admin\Tabs\Layouts;
use TEC\Tickets\Seating\Admin\Tabs\Map_Edit;
use TEC\Tickets\Seating\Admin\Tabs\Maps;
use Tribe\Tests\Traits\With_Uopz;

class Admin_Test extends Controller_Test_Case {
	use With_Uopz;
	use SnapshotAssertions;

	protected string $controller_class = Admin::class;

	/**
	 * @before
	 */
	public function mock_admin_context(): void {
		$this->set_fn_return( 'is_admin', true );
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
			'map new tab'    => [
				'tab' => Map_Edit::get_id(),
			],
			'map edit tab'    => [
				'tab' => Map_Edit::get_id(),
				'fixture' => function(){
					$_GET['mapId'] = 'some-map-id';
				}
			],
			'layout edit tab' => [
				'tab' => Layout_Edit::get_id(),
				'fixture' => function(){
					$_GET['mapId'] = 'some-map-id';
					$_GET['layoutId'] = 'some-layout-id';
				}
			],
		];
	}

	/**
	 * It should add the sub-menu page
	 *
	 * @test
	 * @dataProvider tabs_data_provider
	 */
	public function should_add_the_sub_menu_page( string $tab = null, \Closure $fixture = null ): void {
		unset( $_GET['tab'], $_GET['layout'], $_GET['mapId'], $_REQUEST['tab'], $_REQUEST['layoutId'], $_REQUEST['mapId'] );

		if ( $fixture ) {
			$fixture();
		}
		add_filter( 'tec_tickets_seating_ephemeral_token', static fn() => 'test-ephemeral-token' );

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
		do_action( 'tickets_page_tec-tickets-seating' );
		$this->assertMatchesHtmlSnapshot( ob_get_clean() );
	}
}