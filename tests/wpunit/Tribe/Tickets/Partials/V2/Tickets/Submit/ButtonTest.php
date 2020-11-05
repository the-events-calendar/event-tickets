<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Submit;

use Tribe\Tickets\Test\Partials\V2TestCase;

class ButtonTest extends V2TestCase {

	public $partial_path = 'v2/tickets/submit/button';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {
		return [
			'must_login'         => false,
			'is_modal'           => false,
			'submit_button_name' => 'cart-button',
		];
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_requires_login() {
		$template           = tribe( 'tickets.editor.template' );
		$args               = $this->get_default_args();
		$args['must_login'] = true;
		$html               = $template->template( $this->partial_path, $args, false );

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
	public function test_should_render_button_html() {
		$template = tribe( 'tickets.editor.template' );
		$html     = $template->template( $this->partial_path, $this->get_default_args(), false );

		$this->assertMatchesSnapshot( $html );
	}
}