<?php
namespace Tribe\Tickets\Partials\Tickets;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class OptOutHidden extends WPTestCase {
	use MatchesSnapshots;

	protected $partial_path = 'blocks/tickets/opt-out-hidden';

	/**
	 * @test
	 */
	public function test_should_render_input() {
		$template = tribe( 'tickets.editor.template' );
		$args     = [
			'ticket' => (object) [
				'name' => 'Ticket Title',
				'ID'   => 7,
			],
		];
		$html     = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_empty() {
		$template = tribe( 'tickets.editor.template' );
		$args     = [
			'ticket' => (object) [
				'name' => 'Ticket Title',
				'ID'   => 7,
			],
		];

		add_filter( 'tribe_tickets_plus_hide_attendees_list_optout', '__return_true' );

		$html     = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}
}
