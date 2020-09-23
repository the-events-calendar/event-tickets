<?php

namespace Tribe\Tickets\Partials\V2\Attendee_Registration\Button;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe__Tickets__Editor__Template;

class SubmitTest extends WPTestCase {

	use MatchesSnapshots;

	protected $partial_path = 'v2/attendee-registration/button/submit';

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
