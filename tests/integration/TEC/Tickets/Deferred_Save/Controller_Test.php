<?php

namespace TEC\Tickets\Deferred_Save;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tests\Traits\With_Uopz;

/**
 * The feature is on for every ticketable post unless it is switched off as a whole.
 */
class Controller_Test extends Controller_Test_Case {
	use With_Uopz;

	protected string $controller_class = Controller::class;

	/**
	 * The value the disable environment variable had before the test, or `false` when it was unset.
	 *
	 * The framework restores the WordPress hooks after every test; the environment is ours to restore.
	 *
	 * @var string|false
	 */
	private $original_disabled_env = false;

	/**
	 * @before
	 */
	public function remember_the_environment(): void {
		$this->original_disabled_env = getenv( 'TEC_TICKETS_DEFERRED_SAVE_DISABLED' );
	}

	/**
	 * @after
	 */
	public function restore_the_environment(): void {
		if ( false === $this->original_disabled_env ) {
			putenv( 'TEC_TICKETS_DEFERRED_SAVE_DISABLED' );

			return;
		}

		putenv( 'TEC_TICKETS_DEFERRED_SAVE_DISABLED=' . $this->original_disabled_env );
	}

	/**
	 * @test
	 */
	public function it_should_be_active_by_default(): void {
		$this->assertTrue( $this->make_controller()->is_active() );
	}

	/**
	 * @test
	 */
	public function it_should_be_switched_off_by_the_constant(): void {
		$this->set_const_value( 'TEC_TICKETS_DEFERRED_SAVE_DISABLED', true );

		$this->assertFalse( $this->make_controller()->is_active() );
	}

	/**
	 * @test
	 */
	public function it_should_be_switched_off_by_the_environment_variable(): void {
		putenv( 'TEC_TICKETS_DEFERRED_SAVE_DISABLED=1' );

		$this->assertFalse( $this->make_controller()->is_active() );
	}

	/**
	 * @test
	 */
	public function it_should_be_switched_off_by_the_filter(): void {
		add_filter( 'tec_tickets_deferred_save_active', '__return_false' );

		$this->assertFalse( $this->make_controller()->is_active() );
	}

	/**
	 * @test
	 */
	public function it_should_register_nothing_when_switched_off(): void {
		add_filter( 'tec_tickets_deferred_save_active', '__return_false' );
		$controller = $this->make_controller();

		$controller->register();

		$this->assertFalse( $controller->is_registered() );
	}

	/**
	 * @test
	 */
	public function it_should_hook_the_classic_save_and_unhook_it_on_unregister(): void {
		$controller = $this->make_controller();
		$callback   = $this->test_services->callback( Classic_Save::class, 'on_save_post' );

		$controller->register();

		$this->assertSame( 20, has_action( 'save_post', $callback ) );

		$controller->unregister();

		$this->assertFalse( has_action( 'save_post', $callback ) );
	}

	/**
	 * @test
	 */
	public function it_should_hook_the_block_save_for_every_ticketable_post_type_and_unhook_it_on_unregister(): void {
		$controller = $this->make_controller();
		$on_insert  = $this->test_services->callback( Block_Save::class, 'on_rest_after_insert' );
		$on_prepare = $this->test_services->callback( Block_Save::class, 'add_result_to_response' );
		$post_types = \Tribe__Tickets__Main::instance()->post_types();
		$this->assertNotEmpty( $post_types );

		$controller->register();

		foreach ( $post_types as $post_type ) {
			$this->assertSame( Block_Save::PRIORITY, has_action( "rest_after_insert_{$post_type}", $on_insert ) );
			$this->assertSame( 10, has_filter( "rest_prepare_{$post_type}", $on_prepare ) );
		}

		$controller->unregister();

		foreach ( $post_types as $post_type ) {
			$this->assertFalse( has_action( "rest_after_insert_{$post_type}", $on_insert ) );
			$this->assertFalse( has_filter( "rest_prepare_{$post_type}", $on_prepare ) );
		}
	}

	/**
	 * @test
	 */
	public function it_should_hook_the_classic_editor_fields_and_notices_and_unhook_them_on_unregister(): void {
		$controller = $this->make_controller();
		$hooks      = [
			[ 'tribe_tickets_metabox_end', $this->test_services->callback( Classic\Editor::class, 'print_fields' ) ],
			[ 'tec_tickets_deferred_save_classic_committed', $this->test_services->callback( Classic\Notices::class, 'remember' ) ],
			[ 'admin_notices', $this->test_services->callback( Classic\Notices::class, 'render' ) ],
		];

		$controller->register();

		foreach ( $hooks as [ $hook, $callback ] ) {
			$this->assertSame( 10, has_action( $hook, $callback ), $hook );
		}

		$controller->unregister();

		foreach ( $hooks as [ $hook, $callback ] ) {
			$this->assertFalse( has_action( $hook, $callback ), $hook );
		}
	}
}
