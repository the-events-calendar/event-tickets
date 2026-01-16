<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;

class Metabox_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_be_instantiable(): void {
		$metabox = tribe( Metabox::class );

		$this->assertInstanceOf( Metabox::class, $metabox );
	}
}
