<?php
namespace TEC\Tickets\Commerce\Partials\Admin\PayPal\Connect;

use TEC\Tickets\Commerce\Gateways\PayPal\Location\Country;
use Tribe__Tickets__Main;
use Tribe\Tickets\Test\Partials\V2AdminTestCase;
use TEC\Tickets\Commerce\Gateways\PayPal\Merchant;
use TEC\Tickets\Commerce\Gateways\PayPal\Signup;

class InactiveTest extends V2AdminTestCase {

	public $partial_path = 'settings/tickets-commerce/paypal/connect/inactive';

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
			'is_merchant_active' => false,
			'signup'             => $signup,
		];

		return $args;
	}

	private function replace_nonce( $html ) {
		$start     = strpos( $html, "nonce: '" );
		$end       = strpos( $html, "',", $start ); // Use the start as the offset.
		$sub_start = $start + strlen( "nonce: '" );
		$nonce     = substr( $html, $sub_start, ( $end - $sub_start ) );
		$html      = str_replace( $nonce, 'THE_PAYPAL_NONCE', $html );

		return $html;
	}

	/**
	 * @test
	 */
	public function test_should_render() {
		$args   = $this->get_default_args();
		$html   = $this->template_class()->template( $this->partial_path, $args, false );
		$driver = $this->get_html_output_driver();

		// Replace nonce.
		$html = $this->replace_nonce( $html );

		// Replace link.
		$html = str_replace( $args['signup']->generate_url( Country::DEFAULT_COUNTRY_CODE, true ), 'http://thepaypalsandboxlink.tec.com/hash', $html );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_empty() {
		$args                       = $this->get_default_args();
		$args['is_merchant_active'] = true;
		$html                       = $this->template_class()->template( $this->partial_path, $args, false );
		$driver                     = $this->get_html_output_driver();

		$this->assertMatchesSnapshot( $html, $driver );
	}

}
