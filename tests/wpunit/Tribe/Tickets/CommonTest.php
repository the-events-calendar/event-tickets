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

	/**
	 * Common must be, at least in MIN_COMMON_VERSION
	 *
	 * @test
	 * @since 4.8.0
	*/
	public function it_is_loading_common_required_version() {

		$this->assertTrue(
			version_compare( Common::VERSION, Tickets::MIN_COMMON_VERSION, '>=' ),
			'Tribe Common version should be at least ' . Tickets::MIN_COMMON_VERSION . ', currently on ' . Common::VERSION
		);
	}

}
