<?php

namespace TEC\Tickets\Commerce\Partials\Checkout\Cart;

use Tribe\Tickets\Test\Testcases\Html_Partial_Test_Case;

class CartFooterTest extends Html_Partial_Test_Case {

	protected $partial_path = 'checkout/cart/footer';

	/**
	 * Test render cart footer
	 *
	 * @todo: replace the inner HTML with a new `get_mock_order` method and data
	 */
	public function test_should_render_cart() {
		$order = $this->get_mock_thing( 'orders/1.json' );
		$cart_items = get_post_meta( $order, '_tec_tc_order_cart_items', true );

		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
				'total_value' => '$1.90',
				'items'       => $cart_items,
			]
		) );
	}
}
