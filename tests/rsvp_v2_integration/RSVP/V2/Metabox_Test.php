<?php
/**
 * Tests for the V2 Metabox class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;

/**
 * Class Metabox_Test
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Metabox_Test extends WPTestCase {

	/**
	 * @test
	 */
	public function it_should_be_instantiable(): void {
		$metabox = tribe( Metabox::class );

		$this->assertInstanceOf( Metabox::class, $metabox );
	}
}
