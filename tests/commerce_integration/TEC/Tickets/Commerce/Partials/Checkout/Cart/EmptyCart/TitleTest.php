<?php

namespace TEC\Tickets\Commerce\Partials\Checkout\Cart\EmptyCart;

use Tribe\Tickets\Test\Testcases\TicketsCommerceSnapshotTestCase;

class TitleTest extends TicketsCommerceSnapshotTestCase {

	protected $partial_path = 'checkout/cart/empty/title';

	/**
	 * @test
	 */
	public function test_should_render() {
		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
				'items' => [],
				'is_tec_active' => class_exists( 'Tribe__Events__Main' ),
			]
		) );
	}

	/**
	 * @test
	 */
	public function test_should_render_empty() {
		$this->assertEmpty( $this->get_partial_html( [
				'items' => [ 'Ticket 1', 'Ticket 2' ],
				'is_tec_active' => class_exists( 'Tribe__Events__Main' ),
			]
		) );
	}

}