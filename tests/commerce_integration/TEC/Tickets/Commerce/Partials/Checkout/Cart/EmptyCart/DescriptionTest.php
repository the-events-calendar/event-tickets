<?php

namespace TEC\Tickets\Commerce\Partials\Checkout\Cart\EmptyCart;

use Tribe\Tickets\Test\Testcases\Html_Partial_Test_Case;

class DescriptionTest extends Html_Partial_Test_Case {

	protected $partial_path = 'src/views/v2/commerce/checkout/cart/empty/description';

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