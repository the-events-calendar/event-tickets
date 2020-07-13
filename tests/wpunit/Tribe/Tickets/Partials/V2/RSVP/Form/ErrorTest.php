<?php

namespace Tribe\Tickets\Partials\V2\RSVP\Form;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;


class ErrorTest extends WPTestCase {

	use MatchesSnapshots;

	protected $partial_path = 'v2/rsvp/form/error';

	/**
	 * @test
	 */
	public function test_should_render_error() {
		$template   = tribe( 'tickets.editor.template' );

		$html   = $template->template( $this->partial_path, [], false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://test.tribe.dev' );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
