<?php

namespace TEC\Tickets\Commerce\Partials\Admin\Stripe\Connect;

use Tribe__Tickets__Main;
use TEC\Tickets\Commerce\Gateways\Stripe\Merchant;
use TEC\Tickets\Commerce\Gateways\Stripe\Signup;

use Tribe\Tickets\Test\Testcases\Html_Partial_Test_Case;

class InactiveTest extends Html_Partial_Test_Case {

	protected $partial_path = 'settings/tickets-commerce/stripe/connect/inactive';
	protected $folder_path  = 'src/admin-views';

	public function test_should_render() {
		$merchant = tribe( Merchant::class );
		$signup   = tribe( SignUp::class );

		$html = $this->get_partial_html( [
				'plugin_url'      => Tribe__Tickets__Main::instance()->plugin_url,
				'merchant'        => $merchant,
				'signup'          => $signup,
				'merchant_status' => [
					'connected'       => false,
					'errors'          => [],
					'capabilities'    => [],
					'charges_enabled' => false,
				],
			]
		);

		$html = str_replace( $merchant->get_client_id(), '[CLIENT_ID]', $html );
		$html = str_replace( $signup->generate_signup_url(), 'http://stripesandbox.tec.com/signup', $html );

		$this->assertMatchesHtmlSnapshot( $html );

	}

	public function test_should_render_empty() {
		$this->assertEmpty( $this->get_partial_html( [
				'plugin_url'      => Tribe__Tickets__Main::instance()->plugin_url,
				'merchant_status' => [
					'connected'       => true,
					'errors'          => [],
					'capabilities'    => [],
					'charges_enabled' => true,
				],
			]
		) );
	}
}
