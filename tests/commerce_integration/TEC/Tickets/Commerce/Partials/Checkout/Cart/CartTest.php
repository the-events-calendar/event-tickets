<?php

namespace TEC\Tickets\Commerce\Partials\Checkout\Cart;

use Tribe\Tickets\Test\Testcases\Html_Partial_Test_Case;

class CartTest extends Html_Partial_Test_Case {

	protected $partial_path = 'checkout/cart';

	/**
	 * Test render cart header
	 *
	 * @todo: replace the inner HTML with a new `get_mock_order` method and data
	 */
	public function test_should_render_cart() {
		$event = $this->get_mock_event( 'events/single/1.json' );
		$order = $this->get_mock_thing( 'orders/1.json' );
		$cart_items = get_post_meta( $order, '_tec_tc_order_cart_items', true );
		$price = 0;

		foreach( $cart_items as $key => $item ) {
			$cart_items[ $key ]['event_id'] = $event->ID;
			$price += ($item['quantity'] * $item['price']);
		}

		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
				'section' => $event,
				'event_id' => $event->ID,
				'total_value' => '$' . $price,
				'items'       => $cart_items,
			]
		) );
	}
}
