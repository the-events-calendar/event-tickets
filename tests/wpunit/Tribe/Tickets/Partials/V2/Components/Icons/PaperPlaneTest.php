<?php

namespace Tribe\Tickets\Partials\V2\Components\Icons;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;

class PaperPlane extends WPTestCase {

	use MatchesSnapshots;

	protected $partial_path = 'v2/components/icons/paper-plane';

	/**
	 * @test
	 */
	public function test_should_render_icon() {

		$template = tribe( 'tickets.editor.template' );
		$html     = $template->template( $this->partial_path, [], false );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
