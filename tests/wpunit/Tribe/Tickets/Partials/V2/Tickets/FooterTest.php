<?php

namespace Tribe\Tickets\Partials\V2\Tickets;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;

class FooterTest extends WPTestCase {

	use MatchesSnapshots;
	use With_Post_Remapping;

	protected $partial_path = 'v2/tickets/footer';

	/**
	 * @test
	 */
	public function test_should_not_render_if_not_is_mini_and_empty_ticket_on_sale() {
		$template = tribe( 'tickets.editor.template' );

		$args = [
			'is_mini' => false,
			'tickets_on_sale' => [],
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://wordpress.test' );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_is_mini_and_empty_ticket_on_sale() {
		$template = tribe( 'tickets.editor.template' );

		$args = [
			'is_mini' => true,
			'tickets_on_sale' => [],
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://wordpress.test' );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_not_is_mini_and_not_empty_ticket_on_sale() {
		$template = tribe( 'tickets.editor.template' );

		$args = [
			'is_mini' => false,
			'tickets_on_sale' => [ 'Heck', 'Yeah' ],
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://wordpress.test' );

		$this->assertMatchesSnapshot( $html, $driver );
	}

}
