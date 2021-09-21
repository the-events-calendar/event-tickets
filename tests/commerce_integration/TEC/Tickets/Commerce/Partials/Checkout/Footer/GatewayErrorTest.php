<?php
namespace TEC\Tickets\Commerce\Partials\Checkout\Footer;

use Tribe\Tickets\Test\Partials\V2CommerceTestCase;

class GatewayErrorTest extends V2CommerceTestCase {

	public $partial_path = 'checkout/footer/gateway-error';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {
		$args = [
			'items'           => [ 'Ticket 1', 'Ticket 2' ],
			'gateways_active' => 0,
		];

		return $args;
	}

	/**
	 * @test
	 */
	public function test_should_render_notice() {
		$args   = $this->get_default_args();
		$html   = $this->template_class()->template( $this->partial_path, $args, false );
		$driver = $this->get_html_output_driver();

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_empty_if_no_tickets() {
		$args   = $this->get_default_args();
		$args['items'] = [];
		$html   = $this->template_class()->template( $this->partial_path, $args, false );
		$driver = $this->get_html_output_driver();

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_empty_if_gateway_active() {
		$args   = $this->get_default_args();
		$args['gateways_active'] = 1;
		$html   = $this->template_class()->template( $this->partial_path, $args, false );
		$driver = $this->get_html_output_driver();

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
