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

		yield 'does not authorize subscriber' => [
			function () use ( $namespace ): array {
				wp_set_current_user( $this->factory()->user->create( [ 'role' => 'subscriber' ] ) );

				$request = new Request( 'GET', $namespace( '/tickets' ) );

				return [
					'request'         => $request,
					'expected_status' => 403,
					'expected_data'   => [
						'code'    => 'rest_forbidden',
						'message' => 'Sorry, you are not allowed to access this resource.',
						'data'    => [ 'status' => 403 ],
					],
				];
			},
		];

		yield 'authorizes contributor' => [
			function () use ( $namespace ): array {
				wp_set_current_user( $this->factory()->user->create( [ 'role' => 'contributor' ] ) );

				$request = new Request( 'GET', $namespace( '/tickets' ) );

				return [
					'request'         => $request,
					'expected_status' => 200,
					'expected_data'   => [
						'rest_url'    => rest_url( urlencode( $namespace( '/tickets' ) ) ),
						'total'       => 0,
						'total_pages' => 0,
						'tickets'     => [],
					],
				];
			},
		];

		yield 'authorizes author' => [
			function () use ( $namespace ): array {
				wp_set_current_user( $this->factory()->user->create( [ 'role' => 'author' ] ) );

				$request = new Request( 'GET', $namespace( '/tickets' ) );

				return [
					'request'         => $request,
					'expected_status' => 200,
					'expected_data'   => [
						'rest_url'    => rest_url( urlencode( $namespace( '/tickets' ) ) ),
						'total'       => 0,
						'total_pages' => 0,
						'tickets'     => [],
					],
				];
			},
		];

		yield 'authorizes editor' => [
			function () use ( $namespace ): array {
				wp_set_current_user( $this->factory()->user->create( [ 'role' => 'editor' ] ) );

				$request = new Request( 'GET', $namespace( '/tickets' ) );

				return [
					'request'         => $request,
					'expected_status' => 200,
					'expected_data'   => [
						'rest_url'    => rest_url( urlencode( $namespace( '/tickets' ) ) ),
						'total'       => 0,
						'total_pages' => 0,
						'tickets'     => [],
					],
				];
			},
		];

		yield 'authorizes administrator' => [
			function () use ( $namespace ): array {
				wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

				$request = new Request( 'GET', $namespace( '/tickets' ) );

				return [
					'request'         => $request,
					'expected_status' => 200,
					'expected_data'   => [
						'rest_url'    => rest_url( urlencode( $namespace( '/tickets' ) ) ),
						'total'       => 0,
						'total_pages' => 0,
						'tickets'     => [],
					],
				];
			},
		];

		yield 'handles include_post parameter with valid post ids' => [
			function () use ( $namespace ): array {
				wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

				// Create test posts
				$post_id_1 = $this->factory()->post->create( [ 'post_type' => 'tribe_events' ] );
				$post_id_2 = $this->factory()->post->create( [ 'post_type' => 'tribe_events' ] );

				$request = new Request( 'GET', $namespace( '/tickets' ) );
				$request->set_param( 'include_post', [ $post_id_1, $post_id_2 ] );

				return [
					'request'         => $request,
					'expected_status' => 200,
					'expected_data'   => [
						'rest_url'    => rest_url( urlencode( $namespace( '/tickets' ) ) ),
						'total'       => 0,
						'total_pages' => 0,
						'tickets'     => [],
					],
				];
			},
		];

		yield 'handles include_post parameter with single post id' => [
			function () use ( $namespace ): array {
				wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

				$post_id = $this->factory()->post->create( [ 'post_type' => 'tribe_events' ] );

				$request = new Request( 'GET', $namespace( '/tickets' ) );
				$request->set_param( 'include_post', [ $post_id ] );

				return [
					'request'         => $request,
					'expected_status' => 200,
					'expected_data'   => [
						'rest_url'    => rest_url( urlencode( $namespace( '/tickets' ) ) ),
						'total'       => 0,
						'total_pages' => 0,
						'tickets'     => [],
					],
				];
			},
		];

		yield 'fails with invalid include_post parameter' => [
			function () use ( $namespace ): array {
				wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

				$request = new Request( 'GET', $namespace( '/tickets' ) );

				// Set Non-existent post IDs.
				$request->set_param( 'include_post', [ 99999, 99998 ] );

				return [
					'request'         => $request,
					'expected_status' => 400,
					'expected_data'   => [
						'code'    => 'rest_invalid_param',
						'message' => 'Invalid parameter(s): include_post',
						'data'    => [ 'status' => 400 ],
					],
				];
			},
		];

		yield 'handles per_page parameter with valid value' => [
			function () use ( $namespace ): array {
				wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

				$request = new Request( 'GET', $namespace( '/tickets' ) );
				$request->set_param( 'per_page', 25 );

				return [
					'request'         => $request,
					'expected_status' => 200,
					'expected_data'   => [
						'rest_url'    => rest_url( urlencode( $namespace( '/tickets' ) ) ),
						'total'       => 0,
						'total_pages' => 0,
						'tickets'     => [],
					],
				];
			},
		];

		yield 'fails with per_page parameter below minimum' => [
			function () use ( $namespace ): array {
				wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

				$request = new Request( 'GET', $namespace( '/tickets' ) );
				$request->set_param( 'per_page', 0 );

				return [
					'request'         => $request,
					'expected_status' => 400,
					'expected_data'   => [
						'code'    => 'rest_invalid_param',
						'message' => 'Invalid parameter(s): per_page',
						'data'    => [ 'status' => 400 ],
					],
				];
			},
		];

		yield 'fails with per_page parameter above maximum' => [
			function () use ( $namespace ): array {
				wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

				$request = new Request( 'GET', $namespace( '/tickets' ) );
				$request->set_param( 'per_page', 101 );

				return [
					'request'         => $request,
					'expected_status' => 400,
					'expected_data'   => [
						'code'    => 'rest_invalid_param',
						'message' => 'Invalid parameter(s): per_page',
						'data'    => [ 'status' => 400 ],
					],
				];
			},
		];

		yield 'handles page parameter with valid value' => [
			function () use ( $namespace ): array {
				wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

				$request = new Request( 'GET', $namespace( '/tickets' ) );
				$request->set_param( 'page', 1 );

				return [
					'request'         => $request,
					'expected_status' => 200,
					'expected_data'   => [
						'rest_url'    => rest_url( urlencode( $namespace( '/tickets' ) ) ),
						'total'       => 0,
						'total_pages' => 0,
						'tickets'     => [],
					],
				];
			},
		];

		yield 'fails with page parameter below minimum' => [
			function () use ( $namespace ): array {
				wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

				$request = new Request( 'GET', $namespace( '/tickets' ) );
				$request->set_param( 'page', 0 );

				return [
					'request'         => $request,
					'expected_status' => 400,
					'expected_data'   => [
						'code'    => 'rest_invalid_param',
						'message' => 'Invalid parameter(s): page',
						'data'    => [ 'status' => 400 ],
					],
				];
			},
		];

		yield 'handles multiple parameters together' => [
			function () use ( $namespace ): array {
				wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

				$post_id = $this->factory()->post->create( [ 'post_type' => 'tribe_events' ] );

				$request = new Request( 'GET', $namespace( '/tickets' ) );
				$request->set_param( 'include_post', [ $post_id ] );
				$request->set_param( 'per_page', 15 );
				$request->set_param( 'page', 1 );

				return [
					'request'         => $request,
					'expected_status' => 200,
					'expected_data'   => [
						'rest_url'    => rest_url( urlencode( $namespace( '/tickets' ) ) ),
						'total'       => 0,
						'total_pages' => 0,
						'tickets'     => [],
					],
				];
			},
		];

		yield 'handles empty include_post array' => [
			function () use ( $namespace ): array {
				wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

				$request = new Request( 'GET', $namespace( '/tickets' ) );
				$request->set_param( 'include_post', [] );

				return [
					'request'         => $request,
					'expected_status' => 400,
					'expected_data'   => [
						'code'    => 'rest_invalid_param',
						'message' => 'Invalid parameter(s): include_post',
						'data'    => [ 'status' => 400 ],
					],
				];
			},
		];

		yield 'handles include_post with mixed valid and invalid post ids' => [
			function () use ( $namespace ): array {
				wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

				$valid_post_id = $this->factory()->post->create( [ 'post_type' => 'tribe_events' ] );

				$request = new Request( 'GET', $namespace( '/tickets' ) );
				$request->set_param( 'include_post', [ $valid_post_id, 99999 ] );

				return [
					'request'         => $request,
					'expected_status' => 400,
					'expected_data'   => [
						'code'    => 'rest_invalid_param',
						'message' => 'Invalid parameter(s): include_post',
						'data'    => [ 'status' => 400 ],
					],
				];
			},
		];

		yield 'handles non-numeric per_page parameter' => [
			function () use ( $namespace ): array {
				wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

				$request = new Request( 'GET', $namespace( '/tickets' ) );
				$request->set_param( 'per_page', 'invalid' );

				return [
					'request'         => $request,
					'expected_status' => 400,
					'expected_data'   => [
						'code'    => 'rest_invalid_param',
						'message' => 'Invalid parameter(s): per_page',
						'data'    => [ 'status' => 400 ],
					],
				];
			},
		];

		yield 'handles non-numeric page parameter' => [
			function () use ( $namespace ): array {
				wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

				$request = new Request( 'GET', $namespace( '/tickets' ) );
				$request->set_param( 'page', 'invalid' );

				return [
					'request'         => $request,
					'expected_status' => 400,
					'expected_data'   => [
						'code'    => 'rest_invalid_param',
						'message' => 'Invalid parameter(s): page',
						'data'    => [ 'status' => 400 ],
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
		$this->assertEquals( $expected_status, $response->get_status(), print_r( $response->get_data(), true ) );

		// Handle cases where we expect the response to be refused.
		if ( $expected_status >= 400 ) {
			return;
		}

		$data = $response->get_data();
		if ( null === $expected_data ) {
			$this->assertNull( $data );

			return;
		}
	}
}
