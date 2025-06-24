<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Common\Tests\Provider\Controller_Test_Case;

class REST_Test extends Controller_Test_Case {
	protected string $controller_class = REST::class;

	/**
	 * @test
	 */
	public function it_should_register_endpoints_on_api_init(): void {
		remove_all_actions( 'rest_api_init' );
		$this->make_controller()->register();

		$this->assertEquals( 0, did_action( 'rest_api_init' ) );

		$rest_server = rest_get_server();

		$this->assertEquals( 1, did_action( 'rest_api_init' ) );

		$routes = $rest_server->get_routes();
		$this->assertArrayHasKey( '/tribe/tickets/v1/commerce/square/on-boarding', $routes );
		$this->assertArrayHasKey( '/tribe/tickets/v1/commerce/square/order', $routes );
		$this->assertArrayHasKey( '/tribe/tickets/v1/commerce/square/order/(?P<order_id>[0-9a-zA-Z_-]+)', $routes );
		$this->assertArrayHasKey( '/tribe/tickets/v1/commerce/square/webhooks', $routes );
	}
}
