<?php

namespace Tribe\Tickets\Partials\V2;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;

class TicketsTest extends WPTestCase {

	use MatchesSnapshots;

	protected $partial_path = 'v2';

	/**
	 * @test
	 */
	public function test_should_not_render_if_not_is_sale_future__and__empty_provider_or_empty_tickets() {
		$template = tribe( 'tickets.editor.template' );

		$args = [
			'is_sale_future' => false,
			'provider'       => null,
			'tickets'        => [],
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://wordpress.test' );

		$this->assertMatchesSnapshot( $html, $driver );
	}

}
