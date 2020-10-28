<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Footer;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;

class Return_To_Cart_Test extends WPTestCase {

	use MatchesSnapshots;

	protected $partial_path = 'v2/tickets/footer/return-to-cart';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {
		return [
			'is_mini'      => true,
			'cart_url'     => 'http://wordpress.test/cart/?foo',
			'checkout_url' => 'http://wordpress.test/checkout/?bar',
		];
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_not_is_mini() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'is_mini' => false,
		];

		$args = array_merge( $this->get_default_args(), $override );

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://wordpress.test' );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_cart_and_checkout_url_same() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'cart_url'     => 'http://wordpress.test/cart/?foo',
			'checkout_url' => 'http://wordpress.test/cart/?foo',
		];

		$args = array_merge( $this->get_default_args(), $override );

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://wordpress.test' );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_if_is_mini_and_different_cart_and_checkout_url() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'is_mini' => true,
		];

		$args = array_merge( $this->get_default_args(), $override );

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://wordpress.test' );

		// Check Cart URL is showing .
		$this->assertContains( 'href="'.$args['cart_url'], $html );

		$this->assertMatchesSnapshot( $html, $driver );
	}

}
