<?php

namespace TEC\Tickets\Seating\Admin;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\StellarWP\Assets\Assets;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Seating\Admin;
use TEC\Tickets\Seating\Admin\Tabs\Layout_Edit;
use TEC\Tickets\Seating\Admin\Tabs\Layouts;
use TEC\Tickets\Seating\Admin\Tabs\Map_Edit;
use TEC\Tickets\Seating\Admin\Tabs\Maps;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Tickets\Seating\Meta;
use Tribe__Admin__Notices as Notices;
use Tribe__Tickets__Admin__Move_Tickets as Move_Tickets;
use WP_Screen;
use TEC\Tickets\Seating\Service\Service_Status;

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

	public function license_states_data_provider() {
		return [
			'no tab - expired license' => [
				'fixture' => function () {
					add_filter( 'tec_tickets_seating_service_status',
						static fn( $_status, $backend_base_url ) => new Service_Status( $backend_base_url,
							Service_Status::EXPIRED_LICENSE ),
						10,
						2 );
				},
			],
			'no tab - no license'      => [
				'fixture'           => function () {
					add_filter( 'tec_tickets_seating_service_status',
						static fn( $_status, $backend_base_url ) => new Service_Status( $backend_base_url,
							Service_Status::NO_LICENSE ),
						10,
						2 );
					test_remove_seating_license_key_callback();
				},
				'should_menu_exist' => false,
			],
			'no tab - invalid license' => [
				'fixture' => function () {
					add_filter( 'tec_tickets_seating_service_status',
						static fn( $_status, $backend_base_url ) => new Service_Status( $backend_base_url,
							Service_Status::INVALID_LICENSE ),
						10,
						2 );
				}
			],
			'no tab - service down'    => [
				'fixture' => function () {
					add_filter( 'tec_tickets_seating_service_status',
						static fn( $_status, $backend_base_url ) => new Service_Status( $backend_base_url,
							Service_Status::SERVICE_UNREACHABLE ),
						10,
						2 );
				}
			],
			'no tab - not connected'   => [
				'fixture' => function () {
					add_filter( 'tec_tickets_seating_service_status',
						static fn( $_status, $backend_base_url ) => new Service_Status( $backend_base_url,
							Service_Status::NOT_CONNECTED ),
						10,
						2 );
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
	public function should_add_the_sub_menu_page( string $tab = null, \Closure $fixture = null, $should_menu_exist = true ): void {
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

		$html = ob_get_clean();

		if ( $should_menu_exist ) {
			$this->assertMatchesHtmlSnapshot( $html );
		}

		global $submenu;

		$new_sub_menu = [ 'Seating', 'manage_options', 'tec-tickets-seating', 'Seating' ];

		$this->assertEquals( $should_menu_exist, in_array( $new_sub_menu, $submenu['tec-tickets'], true ) );
	}

	/**
	 * It should add the sub-menu page
	 *
	 * @test
	 * @dataProvider license_states_data_provider
	 */
	public function should_add_the_sub_menu_page_when_license_exists( \Closure $fixture = null, $should_menu_exist = true ): void {
		$fixture();
		add_filter( 'tec_tickets_seating_ephemeral_token', static fn() => 'test-ephemeral-token' );

		$controller = $this->make_controller();
		$controller->register();

		// Register the Admin controller sub-menu page.
		do_action( 'admin_menu' );
		// Render the page.
		ob_start();
		do_action( 'tickets_page_tec-tickets-seating' );

		$html = ob_get_clean();

		if ( $should_menu_exist ) {
			$this->assertMatchesHtmlSnapshot( $html );
		}

		global $submenu;

		$new_sub_menu = [ 'Seating', 'manage_options', 'tec-tickets-seating', 'Seating' ];

		$this->assertEquals( $should_menu_exist, in_array( $new_sub_menu, $submenu['tec-tickets'], true ) );
	}

	public function asset_data_provider() {
		$assets = [
			'tec-tickets-seating-admin-maps'              => '/build/Seating/admin/maps.js',
			'tec-tickets-seating-admin-maps-style'        => '/build/Seating/admin/style-maps.css',
			'tec-tickets-seating-admin-layouts'           => '/build/Seating/admin/layouts.js',
			'tec-tickets-seating-admin-layouts-style'     => '/build/Seating/admin/style-layouts.css',
			'tec-tickets-seating-admin-map-edit'          => '/build/Seating/admin/mapEdit.js',
			'tec-tickets-seating-admin-map-edit-style'    => '/build/Seating/admin/style-mapEdit.css',
			'tec-tickets-seating-admin-layout-edit'       => '/build/Seating/admin/layoutEdit.js',
			'tec-tickets-seating-admin-layout-edit-style' => '/build/Seating/admin/style-layoutEdit.css',
		];

		foreach ( $assets as $slug => $path ) {
			yield $slug => [ $slug, $path ];
		}
	}

	/**
	 * @test
	 * @dataProvider asset_data_provider
	 */
	public function it_should_locate_assets_where_expected( $slug, $path ) {
		$this->make_controller()->register();

		$this->assertTrue( Assets::init()->exists( $slug ) );

		// We use false, because in CI mode the assets are not build so min aren't available. Its enough to check that the non-min is as expected.
		$asset_url = Assets::init()->get( $slug )->get_url( false );
		$this->assertEquals( plugins_url( $path, EVENT_TICKETS_MAIN_PLUGIN_FILE ), $asset_url );
	}

	/**
	 * @after
	 */
	public function cleanup(): void {
		global $submenu;

		$submenu = null;
	}

	/**
	 * @test
	 */
	public function it_should_exclude_asc_events_from_candidates_from_moving_tickets_to(): void {
		$this->make_controller()->register();

		[
			$post_id_1,
			$post_id_2,
			$post_id_3,
			$post_id_4,
		] = static::factory()->post->create_many( 4 );

		update_post_meta( $post_id_1, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_2, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_2, Meta::META_KEY_ENABLED, '1' );
		update_post_meta( $post_id_3, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $post_id_3, Meta::META_KEY_ENABLED, '3' );

		$_POST['post_type'] = 'post';
		$_POST['check']     = 'nonce';
		$this->set_fn_return('wp_verify_nonce', true );

		$wp_send_json_success_result = null;

		$this->set_fn_return('wp_send_json_success', function ( $value = null, $status_code = null, $flags = 0 ) use (&$wp_send_json_success_result) {
			$wp_send_json_success_result = $value;
		}, true );

		tribe( Move_Tickets::class )->get_post_choices();

		$this->assertEquals( [ $post_id_1, $post_id_3, $post_id_4 ], array_keys( $wp_send_json_success_result['posts'] ) );
	}
}
