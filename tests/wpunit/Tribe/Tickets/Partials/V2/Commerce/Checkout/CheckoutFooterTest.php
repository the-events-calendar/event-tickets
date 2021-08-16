<?php

namespace Tribe\Tickets\Partials\V2\Commerce\Checkout;

use Tribe\Tickets\Test\Partials\V2CommerceTestCase;

class CheckoutFooterTest extends V2CommerceTestCase {

	public $partial_path = 'checkout/footer';

	/**
	 * @test
	 */
	public function test_should_render() {

		$args = [];

		$html   = $this->template_class()->template( $this->partial_path, $args, false );
		$driver = $this->get_html_output_driver();

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
