<?php

namespace TEC\Tickets\Commerce\Partials\Checkout\Cart\Item;

use TEC\Tickets\Commerce\Ticket;
use Tribe\Tickets\Test\Testcases\Html_Partial_Test_Case;

class ItemPriceTest extends Html_Partial_Test_Case {

	protected $partial_path = 'src/views/v2/commerce/checkout/cart/item/price';

	public function test_should_render_cart_item_details() {

		$order        = $this->get_mock_thing( 'orders/1.json' );
		$ticket        = $this->get_mock_thing( 'tickets/1.json' );
		$cart_items = get_post_meta( $order, '_tec_tc_order_cart_items', true );
		$item = reset( $cart_items );
		$item['ticket_id'] = $ticket;

		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
				'item' => $item,
				'provider' => tribe( Ticket::class ),
			]
		) );

	}
}
