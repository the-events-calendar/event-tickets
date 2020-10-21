<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Item;

use Tribe\Tickets\Test\Partials\V2TestCase;

class InactiveTest extends V2TestCase {

	protected $partial_path = 'v2/tickets/item/inactive';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {

		return [
			'has_tickets_on_sale'         => false,
			'is_sale_past'                => false,
		];
	}

	/**
	 * @test
	 */
	public function test_should_render_if_has_tickets_on_sale_is_false() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	public function test_should_render_past_message_if_is_sale_past_true(){
		$template = tribe( 'tickets.editor.template' );

		$args                 = $this->get_default_args();
		$args['is_sale_past'] = true;

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_has_tickets_on_sale_is_true() {
		$template = tribe( 'tickets.editor.template' );

		$args                        = $this->get_default_args();
		$args['has_tickets_on_sale'] = true;

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}
}
