<?php

namespace TEC\Tickets\Commerce\Partials\Checkout\Cart\Item;

use TEC\Tickets\Commerce\Utils\Value;
use Tribe\Tickets\Test\Testcases\TicketsCommerceSnapshotTestCase;

class ItemSubtotalTest extends TicketsCommerceSnapshotTestCase {

	protected $partial_path = 'checkout/cart/item/sub-total';

	public function test_should_render_cart_item_subtotal() {

		$order        = $this->get_mock_thing( 'orders/1.json' );
		$cart_items = get_post_meta( $order, '_tec_tc_order_cart_items', true );

		foreach( $cart_items as $i => $item ) {
			$cart_items[ $i ]['sub_total'] = Value::create( 97 );
		}

		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
				'item' => reset( $cart_items ),
			]
		) );

	}
}
