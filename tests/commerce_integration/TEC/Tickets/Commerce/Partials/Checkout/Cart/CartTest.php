<?php

namespace TEC\Tickets\Commerce\Partials\Checkout\Cart;

use TEC\Tickets\Commerce\Utils\Value;
use Tribe\Tickets\Test\Testcases\TicketsCommerceSnapshotTestCase;

class CartTest extends TicketsCommerceSnapshotTestCase {

	protected $partial_path = 'checkout/cart';

	/**
	 * Test render cart header
	 */
	public function test_should_render_cart() {
		$event      = $this->get_mock_event( 'events/single/1.json' );
		$order      = $this->get_mock_thing( 'orders/1.json' );
		$cart_items = get_post_meta( $order, '_tec_tc_order_cart_items', true );
		$total      = get_post_meta( $order, '_tec_tc_order_total_value', true );

		foreach ( $cart_items as $key => $item ) {
			$cart_items[ $key ]['event_id'] = $event->ID;
		}

		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
				'section'     => $event,
				'event_id'    => $event->ID,
				'total_value' => Value::create( $total ),
				'items'       => $cart_items,
			]
		) );
	}
}
