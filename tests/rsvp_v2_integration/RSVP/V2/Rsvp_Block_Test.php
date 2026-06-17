<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe__Tickets__Editor__Template as Tickets_Editor_Template;

/**
 * Tests for RSVP block frontend rendering with TC-RSVP tickets.
 */
class Rsvp_Block_Test extends WPTestCase {
	use Ticket_Maker;

	public function test_rsvp_block_get_tickets_should_include_tc_rsvp_tickets(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		/** @var \Tribe__Tickets__Editor__Blocks__Rsvp $rsvp_block */
		$rsvp_block = tribe( 'tickets.editor.blocks.rsvp' );
		$tickets    = $rsvp_block->get_tickets( $post_id );

		$this->assertCount( 1, $tickets, 'RSVP block should find the TC-RSVP ticket.' );
		$this->assertSame( $ticket_id, $tickets[0]->ID, 'RSVP block should return the TC-RSVP ticket ID.' );
		$this->assertSame( Constants::TC_RSVP_TYPE, $tickets[0]->type(), 'Ticket type should be tc-rsvp.' );
	}

	public function test_rsvp_block_should_render_tc_rsvp_ticket_on_frontend(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->create_tc_rsvp_ticket( $post_id );

		/** @var \Tribe__Tickets__Editor__Blocks__Rsvp $rsvp_block */
		$rsvp_block = tribe( 'tickets.editor.blocks.rsvp' );

		/** @var Tickets_Editor_Template $editor_template */
		$editor_template = tribe( 'tickets.editor.template' );
		$editor_template->set( 'post_id', $post_id, false );

		$html = $rsvp_block->render();

		$this->assertNotEmpty( $html, 'RSVP block should render TC-RSVP tickets on the frontend.' );
		$this->assertStringContainsString( 'tribe-tickets__rsvp-wrapper', $html, 'RSVP block should include the RSVP wrapper markup.' );
	}
}
