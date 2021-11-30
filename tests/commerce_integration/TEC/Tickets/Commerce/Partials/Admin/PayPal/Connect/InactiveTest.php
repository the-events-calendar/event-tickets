<?php

namespace TEC\Tickets\Commerce\Partials\Admin\PayPal\Connect;

use TEC\Tickets\Commerce\Gateways\PayPal\Location\Country;
use Tribe__Tickets__Main;
use TEC\Tickets\Commerce\Gateways\PayPal\Merchant;
use TEC\Tickets\Commerce\Gateways\PayPal\Signup;

use Tribe\Tickets\Test\Testcases\Html_Partial_Test_Case;

class InactiveTest extends Html_Partial_Test_Case {

	protected $partial_path = 'settings/tickets-commerce/paypal/connect/inactive';
	protected $folder_path = 'src/admin-views';

	public function test_should_render() {
		$merchant = tribe( Merchant::class );
		$signup   = tribe( SignUp::class );

		$html = $this->get_partial_html( [
				'plugin_url'            => Tribe__Tickets__Main::instance()->plugin_url,
				'merchant'              => $merchant,
				'is_merchant_connected' => false,
				'signup'                => $signup,
			]
		);

		$html = str_replace( $signup->generate_url( Country::DEFAULT_COUNTRY_CODE, true ), 'http://thepaypalsandboxlink.tec.com/hash', $html );

		$html = preg_replace( '/referralToken=([^&]+)/', 'referralToken=[PAYPAL_TOKEN_STRING]', $html );
		$html = preg_replace( '/token=([^&]+)/', 'token=[PAYPAL_TOKEN_STRING]', $html );

		$this->assertMatchesHtmlSnapshot( $html );

	}

	public function test_should_render_empty() {
		$this->assertEmpty( $this->get_partial_html( [
				'plugin_url'            => Tribe__Tickets__Main::instance()->plugin_url,
				'is_merchant_connected' => true,
			]
		) );
	}
}
