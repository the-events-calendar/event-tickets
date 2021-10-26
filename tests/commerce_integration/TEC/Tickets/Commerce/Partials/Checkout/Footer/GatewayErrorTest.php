<?php
namespace TEC\Tickets\Commerce\Partials\Checkout\Footer;

use Tribe\Tickets\Test\Testcases\Html_Partial_Test_Case;

class GatewayErrorTest extends Html_Partial_Test_Case {

	protected $partial_path = 'src/views/v2/commerce/checkout/footer/gateway-error';

	/**
	 * Test render cart footer
	 */
	public function test_should_render_notice() {
		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
				'items'           => [ 'Ticket 1', 'Ticket 2' ],
				'gateways_active' => 0,
			]
		) );
	}

	public function test_should_render_empty_if_no_tickets() {
		$this->assertEmpty( $this->get_partial_html( [
				'items'           => [],
				'gateways_active' => 0,
			]
		) );
	}

	public function test_should_render_empty_if_gateway_active() {
		$this->assertEmpty( $this->get_partial_html( [
				'items'           => [ 'Ticket 1', 'Ticket 2' ],
				'gateways_active' => 1,
			]
		) );
	}
}
