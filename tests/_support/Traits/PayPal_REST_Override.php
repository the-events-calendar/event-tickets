<?php

declare( strict_types=1 );

namespace Tribe\Tickets\Test\Traits;

use PHPUnit\Framework\Assert;
use TEC\Tickets\Commerce\Gateways\PayPal\Client;
use Tribe\Tests\Traits\With_Uopz;
use WP_REST_Request as Request;

trait PayPal_REST_Override {

	use With_Uopz;

	protected function create_order_via_rest(): array {
		$request = new Request( 'POST', '/tribe/tickets/v1/commerce/paypal/order' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			json_encode(
				[
					'purchaser' => [
						'name'  => 'John Doe',
						'email' => 'johndoe@example.com',
					],
				]
			)
		);

		// Set up a function to replace Client::post(), and to return the data passed to it.
		$fake_id = 'PAY-1234567890';
		$this->set_class_fn_return(
			Client::class,
			'post',
			static function () use ( &$spy_data, $fake_id ) {
				$spy_data = func_get_args();

				return [
					'id'          => $fake_id,
					'create_time' => '2200-01-01T00:00:00Z',
				];
			},
			true
		);

		$response = rest_do_request( $request );
		Assert::assertEquals( 200, $response->get_status() );
		Assert::assertEquals( $fake_id, $response->get_data()['id'] );

		return $spy_data;
	}
}
