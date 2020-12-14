<?php

namespace Tribe\Tickets\Test\Partials;

use Codeception\TestCase\WPTestCase;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Spatie\Snapshots\MatchesSnapshots;
use Tribe\Events\Test\Factories\Event;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;
use Tribe__Tickets__Editor__Template;

/**
 * Class V2TestCase for snapshot testing.
 * @package Tribe\Tickets\Test\Partials
 */
abstract class V2TestCase extends WPTestCase {

	use MatchesSnapshots {
		assertMatchesSnapshot as _assertMatchesSnapshot;
	}

	use With_Post_Remapping;

	/**
	 * The path relative to the V2 views path.
	 *
	 * If empty, template's HTML result will be boolean false.
	 *
	 * @var string The V2 view path. Example: 'v2/tickets/footer'.
	 */
	public $partial_path = '';

	public function setUp() {
		// before
		parent::setUp();

		$this->factory()->event = new Event();
	}

	/**
	 * ET Template class instance.
	 *
	 * @return Tribe__Tickets__Editor__Template
	 */
	public function template_class() {
		return tribe( 'tickets.editor.template' );
	}

	/**
	 * ET Template's HTML result.
	 *
	 * @param array $args Any context data you need to expose to this file.
	 *
	 * @return string|false Either the final content HTML or `false` if no template could be found.
	 */
	public function template_html( array $args = [] ) {
		return $this->template_class()->template( $this->partial_path, $args, false );
	}

	/**
	 * The default Template Context array for the template being tested.
	 *
	 * Override this unless not necessary.
	 *
	 * @return array The Template Context array.
	 */
	public function get_default_args() {
		return [];
	}

	/**
	 * The Template Context array with overrides then defaults.
	 *
	 * @return array The Template Context array.
	 */
	public function get_args( array $overrides = [] ) {
		return array_merge( $this->get_default_args(), $overrides );
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
