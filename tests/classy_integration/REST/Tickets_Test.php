<?php

declare( strict_types=1 );

namespace TEC\Tickets\Tests\Classy\REST;

use Closure;
use Generator;
use TEC\Common\Classy\REST\Controller as Common_Controller;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Classy\REST\Controller;
use TEC\Tickets\Classy\REST\Endpoints\Tickets;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

/**
 * Class Tickets_Test
 *
 * @since TBD
 */
class Tickets_Test extends Controller_Test_Case {
	protected $controller_class = Controller::class;

	public static function check_tickets_endpoint_data_provider(): Generator {
		$namespace = function ( string $path ): string {
			return '/' . Common_Controller::REST_NAMESPACE . $path;
		};

		yield 'does not authorize visitor' => [
			function () use ( $namespace ): array {
				// Set the current user to a visitor.
				wp_set_current_user( 0 );

				$request = new Request( 'GET', $namespace( '/tickets' ) );

				return [
					'request'         => $request,
					'expected_status' => 401,
					'expected_data'   => [
						'code'    => 'rest_forbidden',
						'message' => 'Sorry, you are not allowed to access this resource.',
						'data'    => [ 'status' => 401 ],
					],
				];
			},
		];
	}

	/**
	 * @dataProvider check_tickets_endpoint_data_provider
	 * @covers       Controller::register_routes
	 * @covers       Tickets::get
	 *
	 * @param Closure $fixture
	 *
	 * @return void
	 */
	public function test_tickets_get_endpoint( Closure $fixture ): void {
		// Get the data from the fixture.
		$data = Closure::bind( $fixture, $this )();
		[
			$request,
			$expected_status,
			$expected_data,
		] = array_values( $data );

		// Make the controller and run the request.
		$controller = $this->make_controller();
		$controller->register();
		$response = rest_get_server()->dispatch( $request );

		// Ensure the response has the expected status code.
		$this->assertInstanceOf( Response::class, $response );
		$this->assertEquals( $expected_status, $response->get_status() );

		// Handle cases where we expect the response to be refused.
		if ( $expected_status >= 400 && $expected_status < 500 ) {
			return;
		}

		$data = $response->get_data();
		if ( null === $expected_data ) {
			$this->assertNull( $data );

			return;
		}
	}
}
