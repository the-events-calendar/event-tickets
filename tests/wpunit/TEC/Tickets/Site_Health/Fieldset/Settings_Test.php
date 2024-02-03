<?php

namespace TEC\Tickets\Site_Health\Fieldset;

use \Codeception\TestCase\WPTestCase;

/**
 * Class Commerce_Test
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Site_Health\Fieldset
 */
class Settings_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function should_be_able_to_instantiate(): void {
		$fieldset = new Settings();
		$this->assertInstanceOf( Settings::class, $fieldset );
	}
}