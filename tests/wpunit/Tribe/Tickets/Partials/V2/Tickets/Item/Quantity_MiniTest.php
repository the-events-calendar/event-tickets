<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Item;

use Tribe\Tickets\Test\Partials\V2TestCase;

class Quantity_MiniTest extends V2TestCase {

	protected $partial_path = 'v2/tickets/item/quantity-mini';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {
		return [
			'is_mini'  => true,
		];
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_is_mini() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'is_mini' => false,
		];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_if_is_mini() {
		$template = tribe( 'tickets.editor.template' );

		$html = $template->template( $this->partial_path, $this->get_default_args(), false );

		$this->assertMatchesSnapshot( $html );
	}
}