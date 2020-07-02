<?php

namespace Tribe\Tickets\Partials\V2\Components\Icons;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;

class GuestTest extends WPTestCase {

	use MatchesSnapshots;

	protected $partial_path = 'v2/components/icons/guest';

	/**
	 * @test
	 */
	public function test_should_render_icon() {

		$template = tribe( 'tickets.editor.template' );
		$html     = $template->template( $this->partial_path, [ 'classes' => [] ], false );
		$driver   = new WPHtmlOutputDriver( home_url(), 'http://test.tribe.dev' );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_icon_with_custom_classes() {

		$template = tribe( 'tickets.editor.template' );
		$html     = $template->template( $this->partial_path, [ 'classes' => [ 'css-test-class' ] ], false );
		$driver   = new WPHtmlOutputDriver( home_url(), 'http://test.tribe.dev' );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
