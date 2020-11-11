<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Item\Content;

use Tribe\Tickets\Test\Partials\V2TestCase;

class InactiveTest extends V2TestCase {

	public $partial_path = 'v2/tickets/item/content/inactive';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {

		return [
			'is_sale_past' => false,
		];
	}

	/**
	 * @test
	 */
	public function test_should_show_not_available_message() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'is_sale_past' => true,
		];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertContains( 'are no longer available', $html );
		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_show_ticket_not_yet_available_message() {
		$template = tribe( 'tickets.editor.template' );

		$html = $template->template( $this->partial_path, $this->get_default_args(), false );

		$this->assertContains( 'are not yet available', $html );
		$this->assertMatchesSnapshot( $html );
	}

}
