<?php

namespace Tribe\Tickets\Partials\V2\Tickets;

use Tribe\Tickets\Test\Partials\V2TestCase;

class SubmitTest extends V2TestCase {

	protected $partial_path = 'v2/tickets/submit';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {
		return [
			'is_mini'            => false,
			'is_modal'           => false,
			'submit_button_name' => 'cart-button',
		];
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_is_mini() {
		$template        = tribe( 'tickets.editor.template' );
		$args            = $this->get_default_args();
		$args['is_mini'] = true;
		$html            = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_is_modal() {
		$template         = tribe( 'tickets.editor.template' );
		$args             = $this->get_default_args();
		$args['is_modal'] = true;
		$html             = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_if_not_modal_or_mini() {
		$template = tribe( 'tickets.editor.template' );
		$html     = $template->template( $this->partial_path, $this->get_default_args(), false );

		$this->assertMatchesSnapshot( $html );
	}
}