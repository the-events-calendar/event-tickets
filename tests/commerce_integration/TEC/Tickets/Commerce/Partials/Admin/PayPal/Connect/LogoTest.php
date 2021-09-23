<?php
namespace TEC\Tickets\Commerce\Partials\Admin\PayPal\Connect;

use Tribe\Tickets\Test\Partials\V2AdminTestCase;

class LogoTest extends V2AdminTestCase {

	public $partial_path = 'settings/tickets-commerce/paypal/connect/logo';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {
		$args = [
			'is_merchant_active' => false,
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
	public function test_should_render_without_list() {
		$args   = $this->get_default_args();
		$args['is_merchant_active'] = true;
		$html   = $this->template_class()->template( $this->partial_path, $args, false );
		$driver = $this->get_html_output_driver();

		$this->assertMatchesSnapshot( $html, $driver );
	}

}
