<?php
namespace Tribe\Tickets\Partials\Tickets;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ContentInactive extends WPTestCase {
	use MatchesSnapshots;

	protected $partial_path = 'blocks/tickets/content-inactive';

	/**
	 * @test
	 */
	public function test_should_render_no_longer_available() {
		$template = tribe( 'tickets.editor.template' );
		$args     = [
			'is_sale_past' => true,
		];
		$html     = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_no_yet_available() {
		$template = tribe( 'tickets.editor.template' );
		$args     = [
			'is_sale_past' => null,
		];
		$html     = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}
}
