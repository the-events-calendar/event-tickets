<?php

namespace Tribe\Tickets\Partials\V2\RSVP\Actions;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;

class FullTest extends WPTestCase {

	use MatchesSnapshots;

	protected $partial_path = 'v2/rsvp/actions/full';

	/**
	 * @test
	 */
	public function test_should_render_full_message() {
		$template     = tribe( 'tickets.editor.template' );
		$_GET['step'] = 'success';

		$html   = $template->template( $this->partial_path, [], false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://test.tribe.dev' );

		$this->assertMatchesSnapshot( $html, $driver );
	}

}
