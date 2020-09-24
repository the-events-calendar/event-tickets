<?php

namespace Tribe\Tickets\Partials\V2\Attendee_Registration\Button;

use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe__Tickets__Editor__Template;

/**
 * Class SubmitTest.
 * @package Tribe\Tickets\Partials\V2\Attendee_Registration\Button
 */
class SubmitTest extends V2TestCase {

	/** @var string Relative path to V2 template file. */
	private $partial_path = 'v2/attendee-registration/button/submit';

	/**
	 * @test
	 */
	public function test_should_render_successfully() {
		/** @var Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		$html   = $template->template( $this->partial_path, [], false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://wordpress.test' );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
