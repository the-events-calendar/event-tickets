<?php
/**
 * Tests for the RSVP V2 Controller.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use RuntimeException;

/**
 * Class Controller_Test
 *
 * @since TBD
 */
class Controller_Test extends WPTestCase {

	/**
	 * Test that do_register throws RuntimeException.
	 *
	 * @test
	 */
	public function test_do_register_throws_runtime_exception(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'RSVP V2 is not implemented yet' );

		$controller = new Controller( tribe() );
		$controller->register();
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
}
