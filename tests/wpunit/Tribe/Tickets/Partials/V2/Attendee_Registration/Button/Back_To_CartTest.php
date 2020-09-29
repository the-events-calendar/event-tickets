<?php

namespace Tribe\Tickets\Partials\V2\Attendee_Registration\Button;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe__Tickets__Editor__Template;

/**
 * Class Back_To_CartTest.
 * @package Tribe\Tickets\Partials\V2\Attendee_Registration\Button
 */
class Back_To_CartTest extends V2TestCase {

	/** @var string Relative path to V2 template file. */
	private $partial_path = 'v2/attendee-registration/button/back-to-cart';

	/**
	 * @test
	 */
	public function test_should_render_nothing_if_cart_url_is_empty() {
		/** @var Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		$args = [
			'cart_url'     => '',
			'checkout_url' => TRIBE_TESTS_HOME_URL . 'checkout/?anything',
			'provider'     => 'any-provider',
		];

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_nothing_if_cart_and_checkout_url_match() {
		/** @var Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		$args = [
			'cart_url'     => TRIBE_TESTS_HOME_URL . 'checkout/?anything',
			'checkout_url' => TRIBE_TESTS_HOME_URL . 'checkout/?anything',
			'provider'     => 'any-provider',
		];

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_successfully() {
		/** @var Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		$args = [
			'cart_url'     => TRIBE_TESTS_HOME_URL . 'cart/?anything',
			'checkout_url' => TRIBE_TESTS_HOME_URL . 'checkout/?something-else',
			'provider'     => 'any-provider',
		];

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}
}
