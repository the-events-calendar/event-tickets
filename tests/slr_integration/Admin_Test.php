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
use TEC\Tickets\Seating\Meta;
use Tribe__Admin__Notices as Notices;
use Tribe__Tickets__Admin__Move_Tickets as Move_Tickets;
use WP_Screen;

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

	/**
	 * @test
	 */
	public function it_should_not_display_woo_incompatibility_notice_when_woo_inactive(): void {
		$this->set_fn_return( 'function_exists', static fn( $fn ) => $fn === 'WC' ? false : function_exists( $fn ), true );
		$this->set_fn_return( 'wp_create_nonce', '12345678' );
		add_filter( 'tribe_admin_is_wp_screen', '__return_true' );

		global $current_screen;

		$current_screen = WP_Screen::get( 'tickets_page_tec-tickets-attendees' );
		$controller = $this->make_controller();
		$controller->register_woo_incompatibility_notice();

		Notices::instance()->hook();

		$this->assertFalse( function_exists( 'WC' ) );

		ob_start();
		do_action( 'admin_notices' );
		$this->assertEmpty( ob_get_clean() );
	}

	/**
	 * @test
	 */
	public function it_should_display_woo_incompatibility_notice_when_woo_active(): void {
		$this->set_fn_return( 'function_exists', static fn( $fn ) => $fn === 'WC' ? true : function_exists( $fn ), true );
		$this->set_fn_return( 'wp_create_nonce', '12345678' );
		add_filter( 'tribe_admin_is_wp_screen', '__return_true' );

		global $current_screen;

		$current_screen = WP_Screen::get( 'tickets_page_tec-tickets-attendees' );

		$controller = $this->make_controller();
		$controller->register_woo_incompatibility_notice();

		Notices::instance()->hook();

		$this->assertTrue( function_exists( 'WC' ) );

		ob_start();
		do_action( 'admin_notices' );
		$this->assertMatchesHtmlSnapshot( ob_get_clean() );
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
