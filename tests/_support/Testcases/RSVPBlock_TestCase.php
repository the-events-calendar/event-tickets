<?php

namespace Tribe\Tickets\Test\Testcases;

use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class RSVPBlock_TestCase extends TicketsBlock_TestCase {

	use RSVP_Ticket_Maker;

	/**
	 * Get list of providers for test.
	 *
	 * @return array List of providers.
	 */
	protected function get_providers() {
		return $this->get_rsvp_providers();
	}

	/**
	 * Get ticket matrix variations.
	 *
	 * @return array List of variations.
	 */
	public function _get_ticket_matrix() {
		return $this->_get_rsvp_matrix();
	}

	/**
	 * Get ticket update matrix variations.
	 *
	 * @return array List of variations.
	 */
	public function _get_ticket_update_matrix() {
		return $this->_get_rsvp_update_matrix();
	}

	/**
	 * Create ticket.
	 *
	 * @param int   $post_id   The ID of the post this ticket should be related to.
	 * @param int   $price     Ticket price (not used for RSVPs).
	 * @param array $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return int Ticket ID.
	 */
	protected function create_block_ticket( $post_id, $price, $overrides ) {
		return $this->create_rsvp_ticket( $post_id, $overrides );
	}

	/**
	 * @dataProvider _get_ticket_matrix_as_args
	 * @test
	 */
	public function test_should_render_ticket_block( $matrix ) {
		$post_id = $this->factory()->post->create();

		$ticket_id = $this->setup_block_ticket( $post_id, $matrix );

		/** @var \Tribe__Tickets__Editor__Blocks__Rsvp $rsvp_block */
		$rsvp_block = tribe( 'tickets.editor.blocks.rsvp' );

		/** @var \Tribe__Tickets__Editor__Template $editor_template */
		$editor_template = tribe( 'tickets.editor.template' );
		$editor_template->set( 'post_id', $post_id, false );

		$html = $rsvp_block->render();

		$driver = new WPHtmlOutputDriver( getenv( 'WP_URL' ), 'http://wp.localhost' );

		$driver->setTolerableDifferences( [ $ticket_id, $post_id ] );
		$driver->setTimeDependentAttributes( [
			'data-rsvp-id',
		] );

		// Remove pesky SVG.
		$html = preg_replace( '/<svg.*<\/svg>/Ums', '', $html );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @dataProvider _get_ticket_update_matrix_as_args
	 * @test
	 */
	public function test_should_render_ticket_block_after_update( $matrix ) {
		$post_id = $this->factory()->post->create();

		// Create ticket.
		$ticket_id = $this->setup_block_ticket( $post_id, $matrix['from'] );

		// Update ticket.
		$this->setup_block_ticket( $post_id, $matrix['to'], [
			'ticket_id' => $ticket_id,
		] );

		/** @var \Tribe__Tickets__Editor__Blocks__Rsvp $rsvp_block */
		$rsvp_block = tribe( 'tickets.editor.blocks.rsvp' );

		/** @var \Tribe__Tickets__Editor__Template $editor_template */
		$editor_template = tribe( 'tickets.editor.template' );
		$editor_template->set( 'post_id', $post_id, false );

		$html = $rsvp_block->render();

		$driver = new WPHtmlOutputDriver( getenv( 'WP_URL' ), 'http://wp.localhost' );

		$driver->setTolerableDifferences( [ $ticket_id, $post_id ] );
		$driver->setTimeDependentAttributes( [
			'data-rsvp-id',
		] );

		// Remove pesky SVG.
		$html = preg_replace( '/<svg.*<\/svg>/Ums', '', $html );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
