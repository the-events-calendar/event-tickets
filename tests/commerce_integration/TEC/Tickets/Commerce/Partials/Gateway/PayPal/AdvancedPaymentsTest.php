<?php
namespace TEC\Tickets\Commerce\Partials\Gateway\PayPal;

use Tribe\Tickets\Test\Partials\V2CommerceTestCase;

class AdvancedPayments extends V2CommerceTestCase {

	public $partial_path = 'gateway/paypal/advanced-payments';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {
		$args = [
			'must_login'               => false,
			'supports_custom_payments' => true,
		];

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

	/**
	 * @test
	 */
	public function test_should_render_empty_if_no_custom_payments_support() {
		$args                             = $this->get_default_args();
		$args['supports_custom_payments'] = false;
		$html                             = $this->template_class()->template( $this->partial_path, $args, false );
		$driver                           = $this->get_html_output_driver();

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
