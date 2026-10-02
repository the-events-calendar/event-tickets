<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use TEC\Common\Tests\Provider\Controller_Test_Case;

class Assets_Test extends Controller_Test_Case {
	protected $controller_class = Assets::class;

	/**
	 * @before
	 */
	public function log_in_as_administrator(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Setting the admin screen fires an onboarding redirect that hangs the test run.
		remove_all_actions( 'tec_admin_headers_about_to_be_sent' );
	}

	/**
	 * @after
	 */
	public function reset_screen(): void {
		wp_dequeue_script( Assets::CLASSIC_SCRIPT );
		set_current_screen( 'front' );
	}

	/**
	 * @test
	 */
	public function should_enqueue_the_classic_script_on_the_event_edit_screen(): void {
		$this->make_controller()->register();
		set_current_screen( 'tribe_events' );

		do_action( 'admin_enqueue_scripts', 'post.php' );

		$this->assertTrue( wp_script_is( Assets::CLASSIC_SCRIPT, 'enqueued' ) );
	}

	/**
	 * @test
	 */
	public function should_not_enqueue_the_classic_script_on_the_page_edit_screen(): void {
		$this->make_controller()->register();
		set_current_screen( 'page' );

		do_action( 'admin_enqueue_scripts', 'post.php' );

		$this->assertFalse( wp_script_is( Assets::CLASSIC_SCRIPT, 'enqueued' ) );
	}

	/**
	 * @test
	 */
	public function should_not_enqueue_the_classic_script_on_the_events_list(): void {
		$this->make_controller()->register();
		set_current_screen( 'edit-tribe_events' );

		do_action( 'admin_enqueue_scripts', 'edit.php' );

		$this->assertFalse( wp_script_is( Assets::CLASSIC_SCRIPT, 'enqueued' ) );
	}
}
