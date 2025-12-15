<?php
/**
 * Tests for the V2 Controller.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;

/**
 * Class Controller_Test
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Controller_Test extends WPTestCase {

	/**
	 * @test
	 */
	public function it_should_be_resolved_from_container(): void {
		$controller = tribe( Controller::class );

		$this->assertInstanceOf( Controller::class, $controller );
	}

	/**
	 * @test
	 */
	public function it_should_register_without_errors(): void {
		$controller = tribe( Controller::class );

		// Should not throw.
		$controller->register();

		$this->assertTrue( true );
	}

	/**
	 * @test
	 */
	public function it_should_unregister_without_errors(): void {
		$controller = tribe( Controller::class );
		$controller->register();
		$controller->unregister();

		$this->assertTrue( true );
	}

	/**
	 * @test
	 */
	public function it_should_register_constants_singleton(): void {
		$controller = tribe( Controller::class );
		$controller->register();

		$constants_1 = tribe( Constants::class );
		$constants_2 = tribe( Constants::class );

		$this->assertSame( $constants_1, $constants_2 );
	}
}
