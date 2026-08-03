<?php

namespace TEC\Tickets\Admin\Attendees;

/**
 * @covers \TEC\Tickets\Admin\Attendees\Provider
 *
 * Regression coverage for SMTNC-1897: the provider decided whether to wire the
 * admin-only Attendees screens by running a capability check inside register(),
 * which the container executes while it is still being built on `plugins_loaded`.
 * On the front end that resolved the current user - auth cookie validation plus a
 * usermeta capabilities read - before `init`, on every single request, to reach a
 * conclusion no front-end request ever uses.
 */
class ProviderTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * How many times user_can_manage_attendees() reached its capability list.
	 *
	 * @var int
	 */
	protected $cap_checks = 0;

	public function setUp(): void {
		parent::setUp();

		/*
		 * The capability check short-circuits before the filter for a logged-out user, so
		 * an anonymous visitor would make the counter read zero whether or not the bug is
		 * present. Acting as an administrator keeps the filter reachable.
		 */
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->cap_checks = 0;

		add_filter(
			'tribe_tickets_caps_can_manage_attendees',
			function ( $caps ) {
				++$this->cap_checks;

				return $caps;
			}
		);
	}

	public function tearDown(): void {
		unset( $GLOBALS['current_screen'] );

		parent::tearDown();
	}

	/**
	 * Builds the provider the way the container does, without registering it.
	 *
	 * @return Provider
	 */
	protected function make_provider(): Provider {
		return new Provider( tribe() );
	}

	/**
	 * @test
	 */
	public function it_should_not_check_capabilities_on_front_end_requests(): void {
		$this->assertFalse( is_admin(), 'This test only means something on a front-end request.' );

		$this->make_provider()->register();

		$this->assertSame(
			0,
			$this->cap_checks,
			'Registering the Attendees provider on the front end must not resolve the current user to check capabilities.'
		);
	}

	/**
	 * @test
	 */
	public function it_should_not_wire_the_admin_attendees_screens_on_front_end_requests(): void {
		$this->make_provider()->register();

		$this->assertFalse(
			tribe()->isBound( Hooks::class ),
			'The admin Attendees hooks are unreachable on the front end and should not be registered there.'
		);
	}

	/**
	 * @test
	 */
	public function it_should_check_capabilities_on_admin_requests(): void {
		set_current_screen( 'edit-post' );

		$this->make_provider()->register();

		$this->assertSame(
			1,
			$this->cap_checks,
			'In the admin the capability check still decides whether the Attendees screens are wired.'
		);
	}

	/**
	 * @test
	 */
	public function it_should_wire_the_admin_attendees_screens_for_a_permitted_admin_user(): void {
		set_current_screen( 'edit-post' );

		$this->make_provider()->register();

		$this->assertTrue(
			tribe()->isBound( Hooks::class ),
			'An administrator in the admin should still get the Attendees screens wired.'
		);
	}
}
