<?php

namespace TEC\Tickets\Commerce\Partials\Checkout\Cart\EmptyCart;

use Tribe\Tickets\Test\Partials\V2CommerceTestCase;

class DescriptionTest extends V2CommerceTestCase {

	public $partial_path = 'checkout/cart/empty/description';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {
		$args = [
			'items'      => [],
			'tec_active' => class_exists( 'Tribe__Events__Main' ),
		];

		return $args;
	}

	/**
	 * @test
	 */
	public function test_should_render() {
		$args   = $this->get_default_args();
		$html   = $this->template_class()->template( $this->partial_path, $args, false );
		$driver = $this->get_html_output_driver();

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_empty() {
		$args          = $this->get_default_args();
		$args['items'] = [ 'Ticket 1', 'Ticket 2' ];
		$html          = $this->template_class()->template( $this->partial_path, $args, false );
		$driver        = $this->get_html_output_driver();

		$this->assertMatchesSnapshot( $html, $driver );
	}

}