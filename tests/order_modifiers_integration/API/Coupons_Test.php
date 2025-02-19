<?php

namespace TEC\Tickets\Commerce\Order_Modifiers\API;

use Closure;
use Generator;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Coupons as CouponsRepository;
use Tribe\Tests\Traits\With_Clock_Mock;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Coupon_Creator;
use WP_Error;
use WP_REST_Request as Request;
use WP_REST_Response as Response;
use WP_REST_Server as Server;

class Coupons_Test extends Controller_Test_Case {

	use Coupon_Creator;
	use With_Clock_Mock;
	use With_Uopz;

	protected string $controller_class = Coupons::class;

	/**
	 * @dataProvider rest_endpoints_data_provider
	 * @test
	 */
	public function it_should_provide_expected_responses( Closure $fixture ) {
		$this->make_controller()->register();
		[ $path, $should_fail, $method, $data ] = $fixture();
		$result = $this->assert_endpoint( $path, $method, $should_fail, $data, 401 );
	}

	public function rest_endpoints_data_provider(): Generator {
		yield 'coupons archive -> unauthorized' => [
			function () {
				$coupons = $this->create_data();
				return [
					'/coupons',
					true,
					'GET',
					[]
				];
			},
		];
	}

	protected function create_data() {
		// Create 10 coupons that are flat rate discounts.
		$coupons = $this->create_coupons(
			10,
			[
				'sub_type'   => 'flat',
				'raw_amount' => 1,
			]
		);

		// Create 10 more coupons that are percentage discounts.
		$coupons = array_merge( $coupons, $this->create_coupons( 10 ) );

		$this->assertSame( 20, count( $coupons ) );

		// Sanity check: Ensure that querying the DB shows the same number.
		$repo   = tribe( CouponsRepository::class );
		$result = $repo->get_modifiers(
			[
				'limit' => -1,
			],
			false
		);

		$this->assertSame( 20, count( $result ) );

		return $coupons;
	}

	protected function assert_endpoint(
		string $path,
		string $method = Server::READABLE,
		bool $should_fail = false,
		array $data = [],
		int $error_code = 400
	) {
		$response = $this->do_rest_api_request( $path, $method, $data );

		if ( $should_fail ) {
			$this->assertTrue( $response->is_error(), "Expected an error response for path: {$path}" );
			$this->assertInstanceof( WP_Error::class, $response->as_error() );
			$this->assertSame( $error_code, $response->get_status() );

			return $response;
		}

		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $response->is_error(), "Expected a successful response for path: {$path}" );
		$this->assertNull( $response->as_error() );

		return $response;
	}

	protected function do_rest_api_request(
		$path,
		$method = Server::READABLE,
		array $data = []
	): Response {
		// Set up the request object.
		$request = new Request( $method, "/tribe/tickets/v1{$path}" );
		$request->set_param( 'context', 'view' );

		if ( ! empty( $data ) ) {
			$request->set_body_params( $data );
		}

		return rest_do_request( $request );
	}
}
