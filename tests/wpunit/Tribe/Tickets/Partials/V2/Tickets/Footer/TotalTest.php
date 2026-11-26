<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Footer;

use Codeception\TestCase\WPTestCase;
use Generator;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;

class TotalTest extends WPTestCase {

	use MatchesSnapshots;

	protected $partial_path = 'v2/tickets/footer/total';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {
		return [
			'post_id'     => '999',
			'provider_id' => 'Tribe__Tickets__Commerce__PayPal__Main',
			'currency'    => tribe( 'tickets.commerce.currency' ),
		];
	}

	/**
	 * @test
	 */
	public function test_should_render_footer_total_block() {
		$template = tribe( 'tickets.editor.template' );

		$html   = $template->template( $this->partial_path, $this->get_default_args(), false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://wordpress.test' );

		// Check we have the proper blocks.
		$this->assertContains( 'tribe-common-b2 tribe-tickets__tickets-footer-total', $html );
		$this->assertContains( 'tribe-tickets__tickets-footer-total-label', $html );
		$this->assertContains( 'tribe-tickets__tickets-footer-total-wrap', $html );

		// Confirm if we have the currency blocks.
		$this->assertContains( '<span class="tribe-formatted-currency-wrap tribe-currency-prefix">', $html );
		$this->assertContains( '<span class="tribe-currency-symbol">', $html );
		$this->assertContains( '<span class="tribe-amount">0.00</span>', $html );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * Provider values the Attendee Registration mini-cart can pass when its cart provider does not resolve.
	 *
	 * @return Generator<string, array{0: false|null}> The cases, each holding the unresolved provider value.
	 */
	public function unresolved_provider_data_provider(): Generator {
		yield 'false' => [ false ];
		yield 'null' => [ null ];
	}

	/**
	 * @test
	 * @dataProvider unresolved_provider_data_provider
	 *
	 * @param false|null $provider The unresolved provider.
	 */
	public function test_should_render_footer_total_with_an_unresolved_provider( $provider ): void {
		$template = tribe( 'tickets.editor.template' );
		$args     = array_merge( $this->get_default_args(), [ 'provider' => $provider ] );

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertContains( '<span class="tribe-currency-symbol">', $html );
		$this->assertContains( '<span class="tribe-amount">0.00</span>', $html );
	}

}