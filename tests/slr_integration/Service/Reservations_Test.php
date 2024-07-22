<?php

namespace TEC\Tickets\Seating\Service;

use TEC\Tickets\Seating\Meta;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tests\Traits\WP_Remote_Mocks;

class Reservations_Test extends \Codeception\TestCase\WPTestCase {
	use WP_Remote_Mocks;
	use OAuth_Token;
	use With_Uopz;

	public function test_confirm(): void {
		$this->set_oauth_token( 'auth-token' );
		update_post_meta( 23, Meta::META_KEY_UUID, 'test-post-uuid' );

		$reservations = tribe( Reservations::class );

		// Mock the service to return a success response.
		$service_confirmations = 0;
		$this->mock_wp_remote(
			'post',
			$reservations->get_confirm_url(),
			function () use ( &$service_confirmations ) {
				$service_confirmations ++;

				return [
					'headers' => [
						'Authorization' => 'Bearer auth-token',
						'Content-Type'  => 'application/json',
					],
					'body'    => wp_json_encode(
						[
							'eventId' => 'test-post-uuid',
							'ids'     => [ '1234567890', '0987654321' ],
						]
					),
				];
			},
			[
				'response' => [
					'code' => 200,
				],
				'body'     => wp_json_encode(
					[
						'success' => true,
					]
				),
			]
		);

		$this->assertTrue( $reservations->confirm( 23, [ '1234567890', '0987654321' ] ) );

		$this->assertEquals( 1, $service_confirmations );
	}

	public function test_confirm_failures(): void {
		$this->set_oauth_token( 'auth-token' );
		update_post_meta( 23, Meta::META_KEY_UUID, 'test-post-uuid' );

		$reservations = tribe( Reservations::class );

		// Mock the service to return a failure response.
		$service_confirmations = 0;
		$this->mock_wp_remote(
			'post',
			$reservations->get_confirm_url(),
			function () use ( &$service_confirmations ) {
				$service_confirmations ++;

				return [
					'headers' => [
						'Authorization' => 'Bearer auth-token',
						'Content-Type'  => 'application/json',
					],
					'body'    => wp_json_encode(
						[
							'eventId' => 'test-post-uuid',
							'ids'     => [ '1234567890', '0987654321' ],
						]
					),
				];
			},
			function (): \Generator {
				// Failure 1: an HTTP API failure.
				yield new \WP_Error( 'error', 'error' );

				// Failure 2: a 500 code.
				yield [
					'response' => [
						'code' => 500,
					],
					'body'     => wp_json_encode(
						[
							'success' => false,
						]
					),
				];

				// Failure 3: missing response body.
				yield [
					'response' => [
						'code' => 200,
					],
					'body'     => null,
				];

				// Failure 4: bad JSON in response body.
				yield [
					'response' => [
						'code' => 200,
					],
					'body'     => 'not-json',
				];

				// Failure 5: unsuccessful response.
				yield [
					'response' => [
						'code' => 200,
					],
					'body'     => wp_json_encode(
						[
							'success' => false,
						]
					),
				];
			}
		);

		// First call: HTTP API failure.
		$this->assertFalse( $reservations->confirm( 23, [ '1234567890', '0987654321' ] ) );
		$this->assertEquals( 1, $service_confirmations );

		// Second call: 500 code failure.
		$this->assertFalse( $reservations->confirm( 23, [ '1234567890', '0987654321' ] ) );
		$this->assertEquals( 2, $service_confirmations );

		// Third call: missing response body.
		$this->assertFalse( $reservations->confirm( 23, [ '1234567890', '0987654321' ] ) );
		$this->assertEquals( 3, $service_confirmations );

		// Fourth call: bad JSON in response body.
		$this->assertFalse( $reservations->confirm( 23, [ '1234567890', '0987654321' ] ) );
		$this->assertEquals( 4, $service_confirmations );

		// Fifth call: unsuccessful response.
		$this->assertFalse( $reservations->confirm( 23, [ '1234567890', '0987654321' ] ) );
		$this->assertEquals( 5, $service_confirmations );
	}

	public function test_cancel(): void {
		$this->set_oauth_token( 'auth-token' );
		update_post_meta( 23, Meta::META_KEY_UUID, 'test-post-uuid' );

		$reservations = tribe( Reservations::class );

		// Mock the service to return a success response.
		$service_cancellations = 0;
		$this->mock_wp_remote(
			'post',
			$reservations->get_cancel_url(),
			function () use ( &$service_cancellations ) {
				$service_cancellations ++;

				return [
					'headers' => [
						'Authorization' => 'Bearer auth-token',
						'Content-Type'  => 'application/json',
					],
					'body'    => wp_json_encode(
						[
							'eventId' => 'test-post-uuid',
							'ids'     => [ '1234567890', '0987654321' ],
						]
					),
				];
			},
			[
				'response' => [
					'code' => 200,
				],
				'body'     => wp_json_encode(
					[
						'success' => true,
					]
				),
			]
		);

		$this->assertTrue( $reservations->cancel( 23, [ '1234567890', '0987654321' ] ) );

		$this->assertEquals( 1, $service_cancellations );
	}

	public function test_cancel_failures(): void {
		$this->set_oauth_token( 'auth-token' );
		update_post_meta( 23, Meta::META_KEY_UUID, 'test-post-uuid' );

		$reservations = tribe( Reservations::class );

		// Mock the service to return a failure response.
		$service_cancellations = 0;
		$this->mock_wp_remote(
			'post',
			$reservations->get_cancel_url(),
			function () use ( &$service_cancellations ) {
				$service_cancellations ++;

				return [
					'headers' => [
						'Authorization' => 'Bearer auth-token',
						'Content-Type'  => 'application/json',
					],
					'body'    => wp_json_encode(
						[
							'eventId' => 'test-post-uuid',
							'ids'     => [ '1234567890', '0987654321' ],
						]
					),
				];
			},
			function (): \Generator {
				// Failure 1: an HTTP API failure.
				yield new \WP_Error( 'error', 'error' );

				// Failure 2: a 500 code.
				yield [
					'response' => [
						'code' => 500,
					],
					'body'     => wp_json_encode(
						[
							'success' => false,
						]
					),
				];

				// Failure 3: missing response body.
				yield [
					'response' => [
						'code' => 200,
					],
					'body'     => null,
				];

				// Failure 4: bad JSON in response body.
				yield [
					'response' => [
						'code' => 200,
					],
					'body'     => 'not-json',
				];

				// Failure 5: unsuccessful response.
				yield [
					'response' => [
						'code' => 200,
					],
					'body'     => wp_json_encode(
						[
							'success' => false,
						]
					),
				];
			}
		);

		// First call: HTTP API failure.
		$this->assertFalse( $reservations->cancel( 23, [ '1234567890', '0987654321' ] ) );
		$this->assertEquals( 1, $service_cancellations );

		// Second call: 500 code failure.
		$this->assertFalse( $reservations->cancel( 23, [ '1234567890', '0987654321' ] ) );
		$this->assertEquals( 2, $service_cancellations );

		// Third call: missing response body.
		$this->assertFalse( $reservations->cancel( 23, [ '1234567890', '0987654321' ] ) );
		$this->assertEquals( 3, $service_cancellations );

		// Fourth call: bad JSON in response body.
		$this->assertFalse( $reservations->cancel( 23, [ '1234567890', '0987654321' ] ) );
		$this->assertEquals( 4, $service_cancellations );

		// Fifth call: unsuccessful response.
		$this->assertFalse( $reservations->cancel( 23, [ '1234567890', '0987654321' ] ) );
		$this->assertEquals( 5, $service_cancellations );
	}
}
