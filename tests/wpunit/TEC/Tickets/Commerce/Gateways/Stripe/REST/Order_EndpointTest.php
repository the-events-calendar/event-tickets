<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\REST;

use TEC\Tickets\Commerce\Gateways\Stripe\REST\Order_Endpoint;
use TEC\Tickets\Commerce\Settings;
use Tribe\Tests\Traits\With_Uopz;

class Order_EndpointTest extends \Codeception\TestCase\WPTestCase {
	use With_Uopz;

	/**
	 * It should detect "test mode" when generating the route URL and return http.
	 *
	 * @test
	 */
	public function should_return_http_if_test_mode() {
		tribe_update_option( Settings::$option_sandbox, 'yes' );

		$this->assertTrue( Settings::is_test_mode() );
		$this->assertNotContains( 'https', tribe( Order_Endpoint::class )->get_route_url() );
	}

	/**
	 * It should detect "test mode" when generating the route URL and return https.
	 *
	 * @test
	 */
	public function should_return_https_if_test_mode_on_ssl() {
		tribe_update_option( Settings::$option_sandbox, 'yes' );
		$this->set_fn_return( 'is_ssl', true );

		$this->assertTrue( Settings::is_test_mode() );
		$this->assertContains( 'https', tribe( Order_Endpoint::class )->get_route_url() );
	}

	/**
	 * It should force https when generating the route URL and not in test mode.
	 *
	 * @test
	 */
	public function should_return_https_if_not_test_mode() {
		tribe_update_option( Settings::$option_sandbox, 'no' );

		$this->assertFalse( Settings::is_test_mode() );
		$this->assertContains( 'https', tribe( Order_Endpoint::class )->get_route_url() );
	}
}
