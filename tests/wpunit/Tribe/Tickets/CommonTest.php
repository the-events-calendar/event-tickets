<?php
namespace Tribe\Tickets;

use Prophecy\Argument;
use Tribe__Tickets__Main as Tickets;
use Tribe__Main as Common;

/**
 * Test that Common is being loaded correctly
 *
 * @group   core
 *
 * @package Tribe__Tickets_Main
 */
class CommonTest extends \Codeception\TestCase\WPTestCase {
	/**
	 * Common should be loaded
	 *
	 * @test
	 * @since 4.8.0
	 */
	public function it_is_loading_common() {

		$this->assertFalse(
			defined( Common::VERSION ),
			'Tribe Common is not loading, you probably need to check that'
		);
	}

}
