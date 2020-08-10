<?php

namespace Tribe\Tickets\Partials\V2\RSVP\Actions\Success;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;

class TitleTest extends WPTestCase {

	use MatchesSnapshots;

	protected $partial_path = 'v2/rsvp/actions/success/title';

	/**
	 * @test
	 */
	public function test_should_render_success_title_going() {
		$template = tribe( 'tickets.editor.template' );

		$html   = $template->template( $this->partial_path, [ 'is_going' => true ], false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://test.tribe.dev' );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_success_title_not_going() {
		$template = tribe( 'tickets.editor.template' );

		$html   = $template->template( $this->partial_path, [ 'is_going' => false ], false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://test.tribe.dev' );

		$this->assertMatchesSnapshot( $html, $driver );
	}

}
