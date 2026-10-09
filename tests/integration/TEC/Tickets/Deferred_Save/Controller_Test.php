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
		// The filter must not switch the feature back on.
		add_filter( 'tec_tickets_deferred_save_active', '__return_true', 1000 );

		$this->assertFalse( $this->make_controller()->is_active() );
	}

	/**
	 * @test
	 */
	public function it_should_be_switched_off_by_the_environment_variable(): void {
		putenv( 'TEC_TICKETS_DEFERRED_SAVE_DISABLED=1' );
		// The filter must not switch the feature back on.
		add_filter( 'tec_tickets_deferred_save_active', '__return_true', 1000 );

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
}
