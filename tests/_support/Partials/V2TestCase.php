<?php

namespace Tribe\Tickets\Test\Partials;

use Codeception\TestCase\WPTestCase;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Spatie\Snapshots\MatchesSnapshots;
use Tribe\Events\Test\Factories\Event;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;

/**
 * Class V2TestCase for snapshot testing.
 * @package Tribe\Tickets\Test\Partials
 */
abstract class V2TestCase extends WPTestCase {

	use MatchesSnapshots {
		assertMatchesSnapshot as _assertMatchesSnapshot;
	}

	use With_Post_Remapping;

	public function setUp() {
		// before
		parent::setUp();

		$this->factory()->event = new Event();
	}

	/**
	 * Get an HTML Driver.
	 *
	 * @return WPHtmlOutputDriver
	 */
	public function get_html_output_driver() {
		return new WPHtmlOutputDriver( home_url(), TRIBE_TESTS_HOME_URL );
	}

	/**
	 * Override snapshot assertion with support for default driver.
	 *
	 * @param string                  $html
	 * @param WPHtmlOutputDriver|null $driver
	 */
	public function assertMatchesSnapshot( $html, WPHtmlOutputDriver $driver = null ) {
		$driver = $driver ? $driver : $this->get_html_output_driver();
		$this->_assertMatchesSnapshot( $html, $driver );
	}
}
