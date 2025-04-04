<?php

namespace TEC\Tickets\Commerce\Partials\Checkout\Cart;

use TEC\Tickets\Commerce\Utils\Value;
use Tribe\Tests\Tickets\Traits\Tribe_URL;
use Tribe\Tickets\Test\Testcases\TicketsCommerceSnapshotTestCase;

class CartFooterTest extends TicketsCommerceSnapshotTestCase {

	use Tribe_URL;

	protected $partial_path = 'checkout/cart/footer';

	/**
	 * Test render cart footer
	 */
	public function test_should_render_cart() {
		$order      = $this->get_mock_thing( 'orders/1.json' );
		$cart_items = get_post_meta( $order, '_tec_tc_order_cart_items', true );
		$total      = get_post_meta( $order, '_tec_tc_order_total_value', true );

		foreach ( $cart_items as $i => $item ) {
			$cart_items[ $i ]['sub_total'] = Value::create( $item['sub_total'] );
		}

		$this->assertMatchesHtmlSnapshot(
			$this->get_partial_html(
				[
					'post'        => $order,
					'total_value' => Value::create( $total ),
					'items'       => $cart_items,
				]
			)
		);
	}
}
