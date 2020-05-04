<?php
namespace Tribe\Tickets\Partials\Tickets;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class IconTest extends WPTestCase {
	use MatchesSnapshots;

	protected $partial_path = 'blocks/tickets/icon';

	/**
	 * @test
	 */
	public function test_render_icon() {
		$template = tribe( 'tickets.editor.template' );
		$html     = $template->template( $this->partial_path, [], false );

		$this->assertMatchesSnapshot( $html );
	}
}
