<?php

namespace TEC\Tickets\Seating\Service;

use TEC\Tickets\Seating\Meta;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tests\Traits\WP_Remote_Mocks;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Reservations_Test extends \Codeception\TestCase\WPTestCase {
	use WP_Remote_Mocks;
	use OAuth_Token;
	use With_Uopz;
	use Attendee_Maker;
	use Ticket_Maker;

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
				$service_confirmations++;

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
				$service_confirmations++;

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
				$service_cancellations++;

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
				$service_cancellations++;

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

	public function test_delete_reservations_from_attendees(): void {
		// Create 3 Attendees and assign a reservation ID to each one of them.
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );
		[
			$attendee_1,
			$attendee_2,
			$attendee_3,
			$attendee_4,
			$attendee_5,
		]          = $this->create_many_attendees_for_ticket( 6, $ticket_id, $post_id );
		update_post_meta( $attendee_1, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-1' );
		update_post_meta( $attendee_2, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-2' );
		update_post_meta( $attendee_3, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-3' );
		update_post_meta( $attendee_4, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-4' );
		update_post_meta( $attendee_5, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-5' );
		add_filter( 'tec_tickets_seating_delete_reservations_from_attendees_batch_size', fn() => 2 );
		$deleted = [];
		add_action(
			'tec_tickets_seating_delete_reservations_from_attendees',
			function ( $map ) use ( &$deleted ) {
				$deleted[] = $map;
			} 
		);

		$reservations = tribe( Reservations::class );

		// Empty list of reservation UUIDs.
		$this->assertEquals( 0, $reservations->delete_reservations_from_attendees( [] ) );

		// List of reservation UUIDs with no Attendees.
		$this->assertEquals(
			0,
			$reservations->delete_reservations_from_attendees(
				[
					'reservation-uuid-6',
					'reservation-uuid-7',
					'reservation-uuid-8',
				] 
			)
		);
		$this->assertEquals( [], $deleted );

		// List of reservation UUIDs with some Attendees.
		$deleted = [];
		$this->assertEquals(
			3,
			$reservations->delete_reservations_from_attendees(
				[
					'reservation-uuid-1',
					'reservation-uuid-2',
					'reservation-uuid-3',
					'reservation-uuid-6',
					'reservation-uuid-7',
					'reservation-uuid-8',
				] 
			)
		);
		$this->assertEquals( '', get_post_meta( $attendee_1, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( '', get_post_meta( $attendee_2, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( '', get_post_meta( $attendee_3, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'reservation-uuid-4', get_post_meta( $attendee_4, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'reservation-uuid-5', get_post_meta( $attendee_5, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals(
			[
				[
					'reservation-uuid-1' => $attendee_1,
					'reservation-uuid-2' => $attendee_2,
				],
				[
					'reservation-uuid-3' => $attendee_3,
				],
			],
			$deleted 
		);

		// A second request to delete the rest.
		$deleted = [];
		$this->assertEquals(
			2,
			$reservations->delete_reservations_from_attendees(
				[
					'reservation-uuid-1',
					'reservation-uuid-2',
					'reservation-uuid-3',
					'reservation-uuid-4',
					'reservation-uuid-5',
					'reservation-uuid-6',
					'reservation-uuid-7',
					'reservation-uuid-8',
				] 
			)
		);
		$this->assertEquals( '', get_post_meta( $attendee_1, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( '', get_post_meta( $attendee_2, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( '', get_post_meta( $attendee_3, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( '', get_post_meta( $attendee_4, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( '', get_post_meta( $attendee_5, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals(
			[
				[ 'reservation-uuid-4' => $attendee_4 ],
				[ 'reservation-uuid-5' => $attendee_5 ],
			],
			$deleted 
		);
	}
	
	public function test_delete_reservations_with_seat_meta() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );
		[
			$attendee_1,
			$attendee_2,
			$attendee_3,
			$attendee_4,
		]          = $this->create_many_attendees_for_ticket( 4, $ticket_id, $post_id );
		
		// Assign a reservation ID to each one of them.
		update_post_meta( $attendee_1, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-1' );
		update_post_meta( $attendee_2, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-2' );
		update_post_meta( $attendee_3, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-2' );
		update_post_meta( $attendee_4, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-3' );
		
		// Assign seat labels to the Attendees.
		update_post_meta( $attendee_1, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'A1' );
		update_post_meta( $attendee_2, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'B1' );
		update_post_meta( $attendee_3, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'B2' );
		
		$reservations = tribe( Reservations::class );
		
		$deleted_count = $reservations->delete_reservations_from_attendees( [ 'reservation-uuid-1' ] );
		// Count should be 2 as we are deleting both reservation meta and seat label meta.
		$this->assertEquals( 2, $deleted_count );
		
		// Check that the meta has been deleted.
		$this->assertEquals( '', get_post_meta( $attendee_1, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( '', get_post_meta( $attendee_1, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ) );
		
		// Now deleting 2nd reservation.
		$deleted_count = $reservations->delete_reservations_from_attendees( [ 'reservation-uuid-2' ] );
		
		// Count should be 4 as we are deleting both reservation meta and seat label meta.
		$this->assertEquals( 4, $deleted_count );
		
		// Check that the meta has been deleted.
		$this->assertEquals( '', get_post_meta( $attendee_2, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( '', get_post_meta( $attendee_2, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ) );
		$this->assertEquals( '', get_post_meta( $attendee_3, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( '', get_post_meta( $attendee_3, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ) );
		
		// Now deleting 3rd reservation.
		$deleted_count = $reservations->delete_reservations_from_attendees( [ 'reservation-uuid-3' ] );
		
		// Count should be 1 as there is no seat label meta for this reservation.
		$this->assertEquals( 1, $deleted_count );
	}
}
