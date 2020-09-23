<?php

namespace Tribe\Tickets\Partials\V2\Attendee_Registration\Button;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;
use Tribe__Tickets__Editor__Template;

class Back_To_CartTest extends WPTestCase {

	use MatchesSnapshots;
	use With_Post_Remapping;

	protected $partial_path = 'v2/attendee-registration/button/back-to-cart';

	/**
	 * @test
	 */
	public function test_should_render_nothing_if_cart_url_is_empty() {
		/** @var Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		$args = [
			'cart_url'     => '',
			'checkout_url' => 'https://wordpress.test/checkout/?anything',
			'provider'     => 'any-provider',
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'https://wordpress.test' );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_nothing_if_cart_and_checkout_url_match() {
		/** @var Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		$args = [
			'cart_url'     => 'https://wordpress.test/checkout/?anything',
			'checkout_url' => 'https://wordpress.test/checkout/?anything',
			'provider'     => 'any-provider',
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'https://wordpress.test' );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_successfully() {
		/** @var Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		$args = [
			'cart_url'     => 'https://wordpress.test/cart/?anything',
			'checkout_url' => 'https://wordpress.test/checkout/?something-else',
			'provider'     => 'any-provider',
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'https://wordpress.test' );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
