<?php
namespace Tribe\Tickets\Partials\RSVP;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;

class ContentInactive extends WPTestCase {
	use MatchesSnapshots;
	use With_Post_Remapping;

	protected $partial_path = 'blocks/rsvp/content-inactive';

	/**
	 * @test
	 */
	public function test_should_render_no_longer_available() {
		$template  = tribe( 'tickets.editor.template' );
		$args      = [
			'all_past' => true,
		];

		$html     = $template->template( $this->partial_path, $args, false );
		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_no_yet_available() {
		$template  = tribe( 'tickets.editor.template' );
		$args      = [
			'all_past' => null,
		];

		$html     = $template->template( $this->partial_path, $args, false );
		$this->assertMatchesSnapshot( $html );
	}
}
