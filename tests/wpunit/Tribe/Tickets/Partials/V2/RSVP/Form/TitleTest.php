<?php

namespace Tribe\Tickets\Partials\V2\RSVP\Form;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;

class TitleTest extends WPTestCase {

	use MatchesSnapshots;

	protected $partial_path = 'v2/rsvp/form/title';

	/**
	 * @test
	 */
	public function test_should_render_going_title() {
		$template     = tribe( 'tickets.editor.template' );
		$_GET['step'] = 'going';

		$html   = $template->template( $this->partial_path, [ 'going' => 'going' ], false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://test.tribe.dev' );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_not_going_title() {
		$template     = tribe( 'tickets.editor.template' );
		$_GET['step'] = 'going';

		$html   = $template->template( $this->partial_path, [ 'going' => 'not-going' ], false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://test.tribe.dev' );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
