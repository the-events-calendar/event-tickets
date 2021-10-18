<?php
namespace TEC\Tickets\Commerce\Partials\Gateway\PayPal\AdvancedPayments;

use Tribe\Tickets\Test\Partials\V2CommerceTestCase;

class Separator extends V2CommerceTestCase {

	public $partial_path = 'gateway/paypal/advanced-payments/separator';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {
		$args = [];

		return $args;
	}

	/**
	 * @test
	 */
	public function test_should_render() {
		$args   = $this->get_default_args();
		$html   = $this->template_class()->template( $this->partial_path, $args, false );
		$driver = $this->get_html_output_driver();

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
