<?php

namespace TEC\Tickets\Commerce\Partials\Admin\PayPal\Connect;

use Tribe__Tickets__Main;
use TEC\Tickets\Commerce\Gateways\PayPal\Merchant;
use TEC\Tickets\Commerce\Gateways\PayPal\Signup;

use Tribe\Tickets\Test\Testcases\Html_Partial_Test_Case;

class ActiveTest extends Html_Partial_Test_Case {

	protected $partial_path = 'settings/tickets-commerce/paypal/connect/active';
	protected $folder_path = 'src/admin-views';

	public function test_should_render() {
		$merchant = tribe( Merchant::class );
		$signup   = tribe( Signup::class );

		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
				'plugin_url'            => Tribe__Tickets__Main::instance()->plugin_url,
				'merchant'              => $merchant,
				'is_merchant_connected' => true,
				'signup'                => $signup,
			]
		) );
	}

	public function test_should_render_empty() {
		$this->assertEmpty( $this->get_partial_html( [
				'plugin_url'            => Tribe__Tickets__Main::instance()->plugin_url,
				'is_merchant_connected' => false,
			]
		) );
	}
}
