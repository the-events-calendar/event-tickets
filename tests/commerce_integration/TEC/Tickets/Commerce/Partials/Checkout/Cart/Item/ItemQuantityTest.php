<?php

namespace TEC\Tickets\Commerce\Partials\Checkout\Cart\Item;

use Tribe\Tickets\Test\Testcases\TicketsCommerceSnapshotTestCase;
use TEC\TIckets\Commerce\Order;

class ItemQuantityTest extends TicketsCommerceSnapshotTestCase {

	protected $partial_path = 'checkout/cart/item/quantity';

	public function test_should_render_cart_item_details() {

		$order        = $this->get_mock_thing( 'orders/1.json' );
		$cart_items = get_post_meta( $order, Order::$items_meta_key, true );

		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
				'item' => reset( $cart_items ),
			]
		) );

	}
}
