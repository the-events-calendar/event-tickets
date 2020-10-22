<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Footer;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;

class QuantityTest extends WPTestCase {

	use MatchesSnapshots;

	protected $partial_path = 'v2/tickets/footer/quantity';

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
	public function test_should_render_quantity_block() {
		$template = tribe( 'tickets.editor.template' );

		$html   = $template->template( $this->partial_path, $this->get_default_args(), false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://wordpress.test' );

		// Check we have the proper blocks.
		$this->assertContains( 'tribe-common-b2 tribe-tickets__tickets-footer-quantity', $html );
		$this->assertContains( 'tribe-tickets__tickets-footer-quantity-label', $html );
		$this->assertContains( '<span class="tribe-tickets__tickets-footer-quantity-number">0</span>', $html );

		$this->assertMatchesSnapshot( $html, $driver );
	}

}
