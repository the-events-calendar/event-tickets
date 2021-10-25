<?php

namespace TEC\Tickets\Commerce\Partials\Checkout\Cart;

use Tribe\Tickets\Test\Testcases\Html_Partial_Test_Case;

class CartHeaderTest extends Html_Partial_Test_Case {

	protected $partial_path = 'checkout/cart/header';

	/**
	 * Test render cart header
	 *
	 * @todo: replace the inner HTML with a new `get_mock_order` method and data
	 */
	public function test_should_render_cart_header() {
		$event = $this->get_mock_event( 'events/single/1.json' );

		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
				'post' => $event,
			]
		) );
	}
}
