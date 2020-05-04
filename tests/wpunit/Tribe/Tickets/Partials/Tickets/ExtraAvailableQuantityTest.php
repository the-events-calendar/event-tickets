<?php
namespace Tribe\Tickets\Partials\Tickets;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ExtraAvailableQuantity extends WPTestCase {
	use MatchesSnapshots;

	protected $partial_path = 'blocks/tickets/extra-available-quantity';

	/**
	 * @test
	 */
	public function test_render_extra_available_quantity_should_render() {
		$template = tribe( 'tickets.editor.template' );
		$args     = [
			'ticket' => (object) [
				'name' => 'Ticket Title',
				'ID'   => 18,
			],
			'available' => 10,
		];
		$html     = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_render_extra_available_quantity_should_render_empty_wo_ticket_id() {
		$template = tribe( 'tickets.editor.template' );
		$args     = [
			'ticket' => (object) [
				'name' => 'Ticket Title',
			],
			'available' => 10,
		];
		$html     = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_render_extra_available_quantity_should_render_empty_wo_available() {
		$template = tribe( 'tickets.editor.template' );
		$args     = [
			'ticket' => (object) [
				'name' => 'Ticket Title',
				'ID'   => 18,
			],
			'available' => null,
		];
		$html     = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}
}
