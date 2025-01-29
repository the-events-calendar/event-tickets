<?php

namespace ET;

use Codeception\TestCase\WPTestCase;
use Tribe__Tickets__Tickets as Tickets;
use TEC\Tickets\Commerce\Module;

class CommerceModuleLoaded_Test extends WPTestCase {
	public function test_commerce_module_is_loaded_early() {
		$this->assertArrayHasKey( Module::class, Tickets::modules() );
	}
}
