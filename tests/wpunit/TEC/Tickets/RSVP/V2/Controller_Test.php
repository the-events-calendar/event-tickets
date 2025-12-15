<?php
/**
 * Tests for the RSVP V2 Controller.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;

/**
 * Class Controller_Test
 *
 * @since TBD
 */
class Controller_Test extends WPTestCase {

	/**
	 * Test that register works without errors.
	 *
	 * @test
	 */
	public function test_register_works_without_errors(): void {
		$controller = new Controller( tribe() );
		$controller->register();

		// If we get here without exception, test passes.
		$this->assertTrue( true );
	}

	/**
	 * Test that unregister does not throw.
	 *
	 * @test
	 */
	public function test_unregister_does_not_throw(): void {
		$controller = new Controller( tribe() );
		$controller->unregister();

		// If we get here without exception, test passes.
		$this->assertTrue( true );
	}

	/**
	 * Test that Constants are registered as singleton.
	 *
	 * @test
	 */
	public function test_registers_constants_singleton(): void {
		$controller = new Controller( tribe() );
		$controller->register();

		$constants1 = tribe( Constants::class );
		$constants2 = tribe( Constants::class );

		$this->assertSame( $constants1, $constants2 );
	}
}
