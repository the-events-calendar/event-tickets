<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use TEC\Common\Tests\Provider\Controller_Test_Case;

class Assets_Test extends Controller_Test_Case {
	protected $controller_class = Assets::class;

	/**
	 * The `TEC_TICKETS_COMMERCE` environment variable before a test changed it: `false` when it was not set, `null`
	 * when the test left it alone.
	 *
	 * @var string|false|null
	 */
	private $tickets_commerce_env = null;

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

		if ( null !== $this->tickets_commerce_env ) {
			putenv( false === $this->tickets_commerce_env ? 'TEC_TICKETS_COMMERCE' : 'TEC_TICKETS_COMMERCE=' . $this->tickets_commerce_env );
			$this->tickets_commerce_env = null;
		}
	}

	/**
	 * @test
	 */
	public function should_enqueue_the_classic_script_on_the_event_edit_screen(): void {
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		$this->make_controller()->register();
		set_current_screen( 'tribe_events' );

		do_action( 'admin_enqueue_scripts', 'post.php' );

		$this->assertTrue( wp_script_is( Assets::CLASSIC_SCRIPT, 'enqueued' ) );
	}

	/**
	 * The script bundles a copy of moment-timezone that would replace the one the block editor's dates rely on.
	 *
	 * @test
	 */
	public function should_not_enqueue_the_classic_script_in_the_block_editor(): void {
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		$this->make_controller()->register();
		set_current_screen( 'tribe_events' );
		get_current_screen()->is_block_editor( true );

		do_action( 'admin_enqueue_scripts', 'post.php' );

		$this->assertFalse( wp_script_is( Assets::CLASSIC_SCRIPT, 'enqueued' ) );
	}

	/**
	 * @test
	 */
	public function should_not_enqueue_the_classic_script_without_tickets_commerce(): void {
		// The environment variable wins over the setting and its filter.
		$this->tickets_commerce_env = getenv( 'TEC_TICKETS_COMMERCE' );
		putenv( 'TEC_TICKETS_COMMERCE=0' );
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_false' );
		$this->make_controller()->register();
		set_current_screen( 'tribe_events' );

		do_action( 'admin_enqueue_scripts', 'post.php' );

		$this->assertFalse( wp_script_is( Assets::CLASSIC_SCRIPT, 'enqueued' ) );
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
