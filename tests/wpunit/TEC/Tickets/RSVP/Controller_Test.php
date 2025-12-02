<?php
/**
 * Tests for the main RSVP Controller.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP;

use TEC\Common\Tests\Provider\Controller_Test_Case;

/**
 * Class Controller_Test
 *
 * @since TBD
 */
class Controller_Test extends Controller_Test_Case {

	/**
	 * The controller class to test.
	 *
	 * @var string
	 */
	protected $controller_class = Controller::class;

	/**
	 * @test
	 */
	public function test_is_active_always_returns_true(): void {
		$controller = $this->make_controller();
		$this->assertTrue( $controller->is_active() );
	}

	/**
	 * @test
	 */
	public function test_is_rsvp_enabled_by_default(): void {
		$controller = $this->make_controller();
		$this->assertTrue( $controller->is_rsvp_enabled() );
	}

	/**
	 * @test
	 */
	public function test_filter_can_disable_rsvp(): void {
		add_filter( 'tec_tickets_rsvp_enabled', '__return_false' );
		$controller = $this->make_controller();
		$this->assertFalse( $controller->is_rsvp_enabled() );
		remove_filter( 'tec_tickets_rsvp_enabled', '__return_false' );
	}

	/**
	 * @test
	 */
	public function test_option_can_disable_rsvp(): void {
		// Must set option before creating controller instance.
		update_option( 'tec_tickets_rsvp_active', 0 );
		$controller = $this->make_controller();
		$this->assertFalse( $controller->is_rsvp_enabled() );
	}

	/**
	 * @test
	 */
	public function test_env_var_disables_rsvp(): void {
		putenv( Controller::DISABLED . '=1' );
		$controller = $this->make_controller();
		$this->assertFalse( $controller->is_rsvp_enabled() );
		putenv( Controller::DISABLED ); // Clean up.
	}

	/**
	 * @test
	 */
	public function test_registers_v1_controller_when_enabled(): void {
		$controller = $this->make_controller();
		$controller->register();

		// Verify the V1 Controller bindings are present (this confirms registration).
		$this->assertInstanceOf(
			\Tribe__Tickets__RSVP::class,
			tribe( 'tickets.rsvp' )
		);

		// Verify RSVP repositories are registered.
		$this->assertTrue( tribe()->has( 'tickets.ticket-repository.rsvp' ) );
		$this->assertTrue( tribe()->has( 'tickets.attendee-repository.rsvp' ) );
	}

	/**
	 * @test
	 */
	public function test_registers_rsvp_disabled_when_disabled(): void {
		add_filter( 'tec_tickets_rsvp_enabled', '__return_false' );
		$controller = $this->make_controller();
		$controller->register();
		$this->assertInstanceOf( RSVP_Disabled::class, tribe( 'tickets.rsvp' ) );
		remove_filter( 'tec_tickets_rsvp_enabled', '__return_false' );
	}

	/**
	 * @test
	 */
	public function test_registers_null_object_repositories_when_disabled(): void {
		add_filter( 'tec_tickets_rsvp_enabled', '__return_false' );
		$controller = $this->make_controller();
		$controller->register();

		$this->assertInstanceOf(
			Repositories\Ticket_Repository_Disabled::class,
			tribe( 'tickets.ticket-repository.rsvp' )
		);
		$this->assertInstanceOf(
			Repositories\Attendee_Repository_Disabled::class,
			tribe( 'tickets.attendee-repository.rsvp' )
		);

		remove_filter( 'tec_tickets_rsvp_enabled', '__return_false' );
	}

	/**
	 * @test
	 */
	public function test_unregister_delegates_to_v1_controller(): void {
		$controller = $this->make_controller();
		$controller->register();

		// Verify the V1 Controller bindings are present, confirming registration.
		$this->assertInstanceOf(
			\Tribe__Tickets__RSVP::class,
			tribe( 'tickets.rsvp' )
		);

		// Get a reference to the registered RSVP instance.
		$rsvp = tribe( 'tickets.rsvp' );

		// Verify hooks are registered.
		$this->assertGreaterThan(
			0,
			has_action( 'init', [ $rsvp, 'init' ] ),
			'RSVP init hook should be registered'
		);

		// Manually set the V1 Controller as registered since container->register() was called.
		tribe()->setVar( V1\Controller::class . '_registered', true );

		$controller->unregister();

		// Verify V1 Controller hooks are removed after unregistration.
		$this->assertFalse(
			has_action( 'init', [ $rsvp, 'init' ] ),
			'RSVP init hook should be unregistered'
		);
	}

	/**
	 * @test
	 */
	public function test_unregister_does_not_fail_when_disabled(): void {
		add_filter( 'tec_tickets_rsvp_enabled', '__return_false' );
		$controller = $this->make_controller();
		$controller->register();

		// Should not throw any errors.
		$controller->unregister();

		// If we get here without exception, test passes.
		$this->assertTrue( true );

		remove_filter( 'tec_tickets_rsvp_enabled', '__return_false' );
	}
}
