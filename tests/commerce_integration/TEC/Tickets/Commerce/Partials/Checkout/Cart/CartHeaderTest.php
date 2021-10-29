<?php

namespace TEC\Tickets\Commerce\Partials\Checkout\Cart;

use Tribe\Tickets\Test\Testcases\TicketsCommerceSnapshotTestCase;

class CartHeaderTest extends TicketsCommerceSnapshotTestCase {

	protected $partial_path = 'checkout/cart/header';

	/**
	 * Test render cart header
	 */
	public function test_should_render_cart_header() {
		$event = $this->get_mock_event( 'events/single/1.json' );

		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
				'post' => $event,
			]
		) );
	}
}
