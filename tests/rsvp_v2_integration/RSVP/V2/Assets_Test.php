<?php
/**
 * Tests for the V2 Assets class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;

/**
 * Class Assets_Test
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Assets_Test extends WPTestCase {

	/**
	 * @test
	 */
	public function it_should_be_instantiable(): void {
		$assets = tribe( Assets::class );

		$this->assertInstanceOf( Assets::class, $assets );
	}

	/**
	 * @test
	 */
	public function it_should_register_without_errors(): void {
		$assets = tribe( Assets::class );
		$assets->register();

		// If we get here without exception, test passes.
		$this->assertTrue( true );
	}
}
