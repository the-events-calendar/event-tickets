<?php

use Tribe\Tickets\Test\Partials\V2TestCase;

class TitleTest extends V2TestCase {
	protected $partial_path = 'v2/tickets/title';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {
		return [];
	}

	/**
	 * @test
	 */
	public function test_should_render_tickets_title_field() {
		$template = tribe( 'tickets.editor.template' );
		$html     = $template->template( $this->partial_path, $this->get_default_args(), false );

		$this->assertMatchesSnapshot( $html, $this->get_html_output_driver() );
	}
}