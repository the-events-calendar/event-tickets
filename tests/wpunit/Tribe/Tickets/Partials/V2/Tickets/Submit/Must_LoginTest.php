<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Submit;

use Tribe\Tickets\Test\Partials\V2TestCase;

class Must_LoginTest extends V2TestCase {

	public $partial_path = 'v2/tickets/submit/must-login';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {
		return [
			'must_login' => true,
		];
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_not_requires_login() {
		$template           = tribe( 'tickets.editor.template' );
		$args               = $this->get_default_args();
		$args['must_login'] = false;
		$html               = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_must_login_html() {
		$template = tribe( 'tickets.editor.template' );
		$html     = $template->template( $this->partial_path, $this->get_default_args(), false );

		$this->assertMatchesSnapshot( $html );
	}
}