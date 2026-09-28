<?php

namespace TEC\Tickets\Deferred_Save;

use TEC\Common\Tests\Provider\Controller_Test_Case;

/**
 * The feature is on for every ticketable post unless it is switched off as a whole.
 */
class Controller_Test extends Controller_Test_Case {
	protected string $controller_class = Controller::class;

	/**
	 * @after
	 */
	public function reset_the_switches(): void {
		putenv( 'TEC_TICKETS_DEFERRED_SAVE_DISABLED' );
		remove_all_filters( 'tec_tickets_deferred_save_active' );
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
}
