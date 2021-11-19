<?php

namespace TEC\Tickets\Commerce\Partials\Checkout\Cart;

use TEC\Tickets\Commerce\Order;
use Tribe\Tickets\Test\Testcases\TicketsCommerceSnapshotTestCase;

class CartFooterTest extends TicketsCommerceSnapshotTestCase {

	protected $partial_path = 'checkout/cart/footer';

	/**
	 * Test render cart footer
	 */
	public function test_should_render_cart() {
		$order      = $this->get_mock_thing( 'orders/1.json' );
		$cart_items = get_post_meta( $order, Order::$items_meta_key, true );
		$total      = get_post_meta( $order, Order::$total_value_meta_key, true );

		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
				'total_value' => '$' . $total,
				'items'       => $cart_items,
			]
		) );
	}
}
