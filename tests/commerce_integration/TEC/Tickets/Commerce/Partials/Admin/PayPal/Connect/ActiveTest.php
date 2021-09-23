<?php
namespace TEC\Tickets\Commerce\Partials\Admin\PayPal\Connect;

use Tribe__Tickets__Main;
use Tribe\Tickets\Test\Partials\V2AdminTestCase;
use TEC\Tickets\Commerce\Gateways\PayPal\Merchant;
use TEC\Tickets\Commerce\Gateways\PayPal\Signup;

class ActiveTest extends V2AdminTestCase {

	public $partial_path = 'settings/tickets-commerce/paypal/connect/active';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {
		$merchant = tribe( Merchant::class );
		$signup   = tribe( SignUp::class );

		$args = [
			'plugin_url'         => Tribe__Tickets__Main::instance()->plugin_url,
			'merchant'           => $merchant,
			'is_merchant_active' => true,
			'signup'             => $signup,
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
	public function test_should_render_empty() {
		$args                       = $this->get_default_args();
		$args['is_merchant_active'] = false;
		$html                       = $this->template_class()->template( $this->partial_path, $args, false );
		$driver                     = $this->get_html_output_driver();

		$this->assertMatchesSnapshot( $html, $driver );
	}

}
