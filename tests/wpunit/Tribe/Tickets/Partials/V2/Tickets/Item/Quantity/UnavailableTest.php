<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Item\Quantity;

use Tribe\Tickets\Test\Partials\V2TestCase;

class UnavailableTest extends V2TestCase {

	protected $partial_path = 'v2/tickets/item/quantity/unavailable';

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
	public function test_should_render_unavailable_html() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}
}