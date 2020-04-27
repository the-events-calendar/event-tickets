<?php
namespace Tribe\Tickets\Partials\Tickets;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class QuantityAdd extends WPTestCase {
	use MatchesSnapshots;

	protected $partial_path = 'blocks/tickets/quantity-add';

	/**
	 * @test
	 */
	public function test_render_quantity() {
		$template = tribe( 'tickets.editor.template' );
		$args     = [
			'ticket' => (object) [
				'name' => 'Ticket Title',
			],
		];
		$html     = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}
}
