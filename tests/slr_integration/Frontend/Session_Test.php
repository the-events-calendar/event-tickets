<?php

namespace TEC\Tickets\Seating\Frontend;

use PHPUnit\Framework\Assert;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Service\OAuth_Token;
use TEC\Tickets\Seating\Service\Reservations;
use TEC\Tickets\Seating\Tables\Sessions;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tests\Traits\WP_Remote_Mocks;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Reservations_Maker;

class Session_Test extends \Codeception\TestCase\WPTestCase {
	use WP_Remote_Mocks;
	use With_Uopz;
	use OAuth_Token;
	use Reservations_Maker;
	use Ticket_Maker;
	use SnapshotAssertions;

	public function test_entry_manipulation(): void {
		$session = tribe( Session::class );
		$expiration_time = $session->get_cookie_expiration_time();
		$setcookie_value = null;
		$this->set_fn_return( 'setcookie',
			function ( $name, $value, $expire, $path, $domain, $secure, $httponly ) use ( &$setcookie_value, $expiration_time ) {
				$setcookie_value = $value;
				Assert::assertEquals( Session::COOKIE_NAME, $name );
				Assert::assertEquals( time() + $expiration_time, $expire, '', 5 );
				Assert::assertEquals( COOKIEPATH, $path );
				Assert::assertEquals( COOKIE_DOMAIN, $domain );
				Assert::assertTrue( $secure );
				Assert::assertTrue( $httponly );
			},
			true
		);

		unset( $_COOKIE[ Session::COOKIE_NAME ] );

		$this->assertEquals( [], $session->get_entries() );

		$session->add_entry( 23, 'test-token' );

		$this->assertEquals( [ 23 => 'test-token' ], $session->get_entries() );
		$this->assertEquals( $session->get_cookie_string( [ 23 => 'test-token' ] ), $setcookie_value );

		$session->add_entry( 89, 'test-token-2' );

		$this->assertEquals( [ 23 => 'test-token', 89 => 'test-token-2' ], $session->get_entries() );
		$this->assertEquals( $session->get_cookie_string( [ 23 => 'test-token', 89 => 'test-token-2' ] ),
			$setcookie_value );

		$session->remove_entry( 23, 'test-token' );

		$this->assertEquals( [ 89 => 'test-token-2' ], $session->get_entries() );
		$this->assertEquals( $session->get_cookie_string( [ 89 => 'test-token-2' ] ), $setcookie_value );

		$this->unset_uopz_functions();
		$this->set_fn_return( 'setcookie',
			function ( $name, $value, $expire, $path, $domain, $secure, $httponly ) use ( &$setcookie_value ) {
				$setcookie_value = $value;
				Assert::assertEquals( Session::COOKIE_NAME, $name );
				Assert::assertEquals( time() - DAY_IN_SECONDS, $expire, '', 5 );
				Assert::assertEquals( COOKIEPATH, $path );
				Assert::assertEquals( COOKIE_DOMAIN, $domain );
				Assert::assertTrue( $secure );
				Assert::assertTrue( $httponly );
			},
			true
		);

		$session->remove_entry( 89, 'test-token-2' );

		$this->assertEquals( [], $session->get_entries() );
		$this->assertFalse( isset( $_COOKIE[ Session::COOKIE_NAME ] ) );
		$this->assertEquals( '', $setcookie_value );
	}

	public function test_cancel_previous_for_object(): void {
		$session = tribe( Session::class );
		$session->add_entry( 23, 'test-token' );

		$this->assertTrue( $session->cancel_previous_for_object( 89, 'test-token' ) );

		// Insert a previous session in the database for the token and object ID.
		$sessions = tribe( Sessions::class );
		$sessions->upsert( 'test-token', 23, time() + 100 );
		$sessions->update_reservations( 'test-token', $this->create_mock_reservations_data( [ 23 ], 2 ) );
		// Assign an UUID to the post.
		update_post_meta( 23, Meta::META_KEY_UUID, 'test-post-uuid' );
		// Set the oAuth token.
		$this->set_oauth_token( 'auth-token' );
		// Mock the remote request to cancel the reservations.
		$this->mock_wp_remote(
			'post',
			tribe( Reservations::class )->get_cancel_url(),
			[
				'headers' => [
					'Authorization' => 'Bearer auth-token',
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode( [
					'eventId' => 'test-post-uuid',
					'ids'     => [ 'reservation-id-1', 'reservation-id-2' ]
				] ),
			],
			[
				'response' => [
					'code' => 200,
				],
				'body'     => wp_json_encode( [ 'success' => true ] ),
			]
		);
		$previous_token_expiration_timestamp = DB::get_var(
			DB::prepare(
				"SELECT expiration FROM %i WHERE token = %s",
				Sessions::table_name(),
				'test-token'
			)
		);

		$this->assertTrue( $session->cancel_previous_for_object( 23, 'test-token' ) );
		$this->assertEquals( [ 23 => 'test-token' ], $session->get_entries() );
		$this->assertEquals( [], $sessions->get_reservations_for_token( 'test-token' ) );
		$current_token_expiration_timestamp = DB::get_var(
			DB::prepare(
				"SELECT expiration FROM %i WHERE token = %s",
				Sessions::table_name(),
				'test-token'
			)
		);
		$this->assertEquals(
			$previous_token_expiration_timestamp,
			$current_token_expiration_timestamp,
			'Cancelling the session should not reset the token expiration timestamp.'
		);
	}

	public function test_cancel_previous_reservation_for_object_with_diff_token_for_same_object_id(): void {
		// Create a previous session for the object ID 23 in the cookie.
		$session = tribe( Session::class );
		$session->add_entry( 23, 'test-token' );
		// Create a previous session for object ID 23 in the database.
		$sessions = tribe( Sessions::class );
		$sessions->upsert( 'test-token', 23, time() + 100 );
		$sessions->update_reservations( 'test-token', $this->create_mock_reservations_data( [ 23 ], 2 ) );
		// Assign an UUID to the post.
		update_post_meta( 23, Meta::META_KEY_UUID, 'test-post-uuid' );
		// Set the oAuth token.
		$this->set_oauth_token( 'auth-token' );
		// Mock the remote request to cancel the reservations.
		$cancelled_on_service = null;
		$this->mock_wp_remote(
			'post',
			tribe( Reservations::class )->get_cancel_url(),
			[
				'headers' => [
					'Authorization' => 'Bearer auth-token',
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode( [
					'eventId' => 'test-post-uuid',
					'ids'     => [ 'reservation-id-1', 'reservation-id-2' ]
				] ),
			],
			function () use ( &$cancelled_on_service ) {
				$cancelled_on_service = true;

				return [
					'response' => [
						'code' => 200,
					],
					'body'     => wp_json_encode( [ 'success' => true ] ),
				];
			}
		);

		// Sanity check.
		$this->assertEquals( [ 23 => 'test-token' ], $session->get_entries() );
		$this->assertEquals(
			[ 'reservation-id-1', 'reservation-id-2' ],
			$sessions->get_reservation_uuids_for_token( 'test-token' )
		);

		// Simulate a request to cancel the session for the same object ID, but with a different token.
		$session->cancel_previous_for_object( 23, 'new-token' );

		$this->assertEquals( [], $session->get_entries() );
		$this->assertEquals( [], $sessions->get_reservations_for_token( 'test-token' ) );
		$this->assertNull(
			DB::get_row(
				DB::prepare(
					"SELECT * FROM %i WHERE token = %s",
					Sessions::table_name(),
					'test-token'
				)
			)
		);
		$this->assertEquals( [], $sessions->get_reservations_for_token( 'new-token' ) );
		$this->assertTrue( $cancelled_on_service );
	}

	public function test_cancel_previous_for_object_fails_if_reservation_cancelation_fails(): void {
		$session = tribe( Session::class );

		$session->add_entry( 23, 'test-token' );

		// Insert a previous session in the database for the token and object ID.
		$sessions = tribe( Sessions::class );
		$sessions->upsert( 'test-token', 23, time() + 100 );
		$sessions->update_reservations( 'test-token', $this->create_mock_reservations_data( [ 23 ], 2 ) );
		// Mock the remote request to cancel the reservations.
		$this->mock_wp_remote(
			'post',
			tribe( Reservations::class )->get_cancel_url(),
			[
				'headers' => [
					'Authorization' => 'Bearer auth-token',
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode( [
					'eventId' => 'test-post-uuid',
					'ids'     => [ 'reservation-id-1', 'reservation-id-2' ]
				] ),
			],
			[
				'response' => [
					'code' => 500,
				],
				'body'     => wp_json_encode( [ 'success' => false ] ),
			]
		);
		// Assign an UUID to the post.
		update_post_meta( 23, Meta::META_KEY_UUID, 'test-post-uuid' );
		// Set the oAuth token.
		$this->set_oauth_token( 'auth-token' );

		$this->assertFalse( $session->cancel_previous_for_object( 23, 'test-token' ) );
		$this->assertEquals( [ 23 => 'test-token' ], $session->get_entries() );
		$this->assertEquals( [ 'reservation-id-1', 'reservation-id-2' ],
			$sessions->get_reservation_uuids_for_token( 'test-token' ) );
	}

	public function test_cancel_previous_for_object_succeeds_if_session_missing(): void {
		$session = tribe( Session::class );

		$session->add_entry( 23, 'test-token' );

		// Insert a previous session in the database for the token and object ID.
		$sessions = tribe( Sessions::class );
		// Do not insert a session for the token and object ID: this should not cause the session deletion to fail.
		// Mock the remote request to cancel the reservations.
		$wp_remote_mock = $this->mock_wp_remote(
			'post',
			tribe( Reservations::class )->get_cancel_url(),
			[
				'headers' => [
					'Authorization' => 'Bearer auth-token',
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode( [
					'eventId' => 'test-post-uuid',
					'ids'     => [ '1234567890', '0987654321' ]
				] ),
			],
			[
				'response' => [
					'code' => 200,
				],
				'body'     => wp_json_encode( [ 'success' => true ] ),
			]
		);
		// Assign an UUID to the post.
		update_post_meta( 23, Meta::META_KEY_UUID, 'test-post-uuid' );
		// Set the oAuth token.
		$this->set_oauth_token( 'auth-token' );

		$this->assertTrue( $session->cancel_previous_for_object( 23, 'test-token' ) );
		$this->assertEquals( [ 23 => 'test-token' ], $session->get_entries() );
		$this->assertEquals( [], $sessions->get_reservations_for_token( 'test-token' ) );
		$this->assertFalse( $wp_remote_mock->was_called() );
	}

	public function test_pick_earliest_expiring_token_object_id(): void {
		$session = tribe( Session::class );
		$session->add_entry( 23, 'test-token-1' );
		$sessions = tribe( Sessions::class );
		// The session for object 23 will expire in 100 seconds.
		$sessions->upsert( 'test-token-1', 23, time() + 100 );
		$sessions->update_reservations( 'test-token-1', $this->create_mock_reservations_data( [ 23 ], 2 ) );

		$this->assertEquals(
			[ 'test-token-1', 23 ],
			$session->pick_earliest_expiring_token_object_id( $session->get_entries() )
		);

		// Add more sessions.
		$session->add_entry( 89, 'test-token-2' );
		$session->add_entry( 66, 'test-token-2' );
		// The session for object 89 will expire in 30 seconds.
		$sessions->upsert( 'test-token-2', 89, time() + 30 );
		$sessions->update_reservations( 'test-token-2', $this->create_mock_reservations_data( [ 89 ], 2 ) );
		// The session for object 66 will expire in 300 seconds.
		$sessions->upsert( 'test-token-3', 66, time() + 300 );
		$sessions->update_reservations( 'test-token-3', $this->create_mock_reservations_data( [ 66 ], 2 ) );

		$this->assertEquals(
			[ 'test-token-2', 89 ],
			$session->pick_earliest_expiring_token_object_id( $session->get_entries() )
		);
	}

	public function test_get_session_token_object_id(): void {
		$sessions = tribe( Sessions::class );

		$session = tribe( Session::class );

		$this->assertEquals( null, $session->get_session_token_object_id() );

		$session->add_entry( 23, 'test-token-1' );
		// The session for object 23 will expire in 100 seconds.
		$sessions->upsert( 'test-token-1', 23, time() + 100 );
		$sessions->update_reservations( 'test-token-1', [ '1234567890', '0987654321' ] );

		$this->assertEquals( [ 'test-token-1', 23 ], $session->get_session_token_object_id() );

		// The session for object 89 will expire in 30 seconds.
		$session->add_entry( 89, 'test-token-2' );
		$sessions->upsert( 'test-token-2', 89, time() + 30 );
		$sessions->update_reservations( 'test-token-2', [ '1234567890', '0987654321' ] );

		$this->assertEquals( [ 'test-token-2', 89 ], $session->get_session_token_object_id() );

		// The session for object 66 will expire in 300 seconds.
		$session->add_entry( 66, 'test-token-3' );
		$sessions->upsert( 'test-token-3', 66, time() + 300 );
		$sessions->update_reservations( 'test-token-3', [ '1234567890', '0987654321' ] );

		$this->assertEquals( [ 'test-token-2', 89 ], $session->get_session_token_object_id() );

		add_filter( 'tec_tickets_seating_timer_token_object_id_handler', function () {
			return fn() => [ 'test-token-4', 2389 ];
		} );

		$this->assertEquals( [ 'test-token-4', 2389 ], $session->get_session_token_object_id() );
	}

	public function test_confirm_all_reservations(): void {
		$sessions = tribe( Sessions::class );
		$this->set_oauth_token( 'auth-token' );

		$session = tribe( Session::class );

		$session->add_entry( 23, 'test-token-1' );
		$sessions->upsert( 'test-token-1', 23, time() + 100 );
		$sessions->update_reservations( 'test-token-1', $this->create_mock_reservations_data( [ 23 ], 2 ) );
		update_post_meta( 23, Meta::META_KEY_UUID, 'test-post-uuid' );

		$session->add_entry( 89, 'test-token-2' );
		$sessions->upsert( 'test-token-2', 89, time() + 30 );
		$sessions->update_reservations( 'test-token-2', $this->create_mock_reservations_data( [ 89 ], 2 ) );
		update_post_meta( 89, Meta::META_KEY_UUID, 'test-post-uuid-2' );

		$this->mock_wp_remote(
			'post',
			tribe( Reservations::class )->get_confirm_url(),
			function (): \Generator {
				yield [
					'headers' => [
						'Authorization' => 'Bearer auth-token',
						'Content-Type'  => 'application/json',
					],
					'body'    => wp_json_encode( [
						'eventId' => 'test-post-uuid',
						'ids'     => [ 'reservation-id-1', 'reservation-id-2' ]
					] ),
				];
				yield [
					'headers' => [
						'Authorization' => 'Bearer auth-token',
						'Content-Type'  => 'application/json',
					],
					'body'    => wp_json_encode( [
						'eventId' => 'test-post-uuid-2',
						'ids'     => [ 'reservation-id-3', 'reservation-id-4' ]
					] ),
				];
			},
			[
				'response' => [
					'code' => 200,
				],
				'body'     => wp_json_encode( [ 'success' => true ] ),
			]
		);

		$this->assertTrue( $session->confirm_all_reservations() );
	}

	public function test_confirm_all_reservations_fails_if_reservation_confirmation_fails(): void {
		$sessions = tribe( Sessions::class );
		$this->set_oauth_token( 'auth-token' );

		$session = tribe( Session::class );

		$session->add_entry( 23, 'test-token-1' );
		$sessions->upsert( 'test-token-1', 23, time() + 100 );
		$sessions->update_reservations( 'test-token-1', $this->create_mock_reservations_data( [ 23 ], 2 ) );
		update_post_meta( 23, Meta::META_KEY_UUID, 'test-post-uuid' );

		$session->add_entry( 89, 'test-token-2' );
		$sessions->upsert( 'test-token-2', 89, time() + 30 );
		$sessions->update_reservations( 'test-token-2', $this->create_mock_reservations_data( [ 89 ], 2 ) );
		update_post_meta( 89, Meta::META_KEY_UUID, 'test-post-uuid-2' );

		$this->mock_wp_remote(
			'post',
			tribe( Reservations::class )->get_confirm_url(),
			function (): \Generator {
				yield [
					'headers' => [
						'Authorization' => 'Bearer auth-token',
						'Content-Type'  => 'application/json',
					],
					'body'    => wp_json_encode( [
						'eventId' => 'test-post-uuid',
						'ids'     => [ 'reservation-id-1', 'reservation-id-2' ]
					] ),
				];
				yield [
					'headers' => [
						'Authorization' => 'Bearer auth-token',
						'Content-Type'  => 'application/json',
					],
					'body'    => wp_json_encode( [
						'eventId' => 'test-post-uuid-2',
						'ids'     => [ 'reservation-id-3', 'reservation-id-4' ]
					] ),
				];
			},
			function (): \Generator {
				yield [
					'response' => [
						'code' => 200,
					],
					'body'     => wp_json_encode( [ 'success' => true ] ),
				];

				// Make the second confirmation request fail.
				yield [
					'response' => [
						'code' => 400,
					],
					'body'     => wp_json_encode( [ 'success' => false ] ),
				];
			}
		);

		$this->assertFalse( $session->confirm_all_reservations() );
	}

	public function test_get_post_ticket_reservations() {
		$sessions = tribe( Sessions::class );
		$this->set_oauth_token( 'auth-token' );
		$cache = tribe_cache();
		// Creat an ASC post with 3 tickets.
		$post_id = static::factory()->post->create();
		update_post_meta( $post_id, Meta::META_KEY_ENABLED, '1' );
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'test-layout-id-1' );
		$ticket_1 = $this->create_tc_ticket( $post_id, 10 );
		update_post_meta( $ticket_1, Meta::META_KEY_ENABLED, '1' );
		update_post_meta( $ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		$ticket_2 = $this->create_tc_ticket( $post_id, 20 );
		update_post_meta( $ticket_2, Meta::META_KEY_ENABLED, '1' );
		update_post_meta( $ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		$ticket_3 = $this->create_tc_ticket( $post_id, 30 );
		update_post_meta( $ticket_3, Meta::META_KEY_ENABLED, '1' );
		update_post_meta( $ticket_3, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );

		$session = tribe( Session::class );

		$session->add_entry( $post_id, 'test-token-1' );
		$sessions->upsert( 'test-token-1', $post_id, time() + 100 );

		$mock_reservations_data = $this->create_mock_reservations_data( [ $ticket_1 ], 2 );
		$sessions->update_reservations( 'test-token-1', $mock_reservations_data );

		$this->assertEquals(
			$mock_reservations_data[$ticket_1],
			$session->get_post_ticket_reservations( $post_id, $ticket_1 )
		);
		$this->assertNull( $session->get_post_ticket_reservations( $post_id, $ticket_2 ) );
		$this->assertNull( $session->get_post_ticket_reservations( $post_id, $ticket_3 ) );

		$mock_reservations_data = $this->create_mock_reservations_data( [ $ticket_1, $ticket_2 ], 2 );
		$sessions->update_reservations( 'test-token-1', $mock_reservations_data );

		$this->assertEquals(
			$mock_reservations_data[ $ticket_1 ],
			$session->get_post_ticket_reservations( $post_id, $ticket_1 )
		);
		$this->assertEquals(
			$mock_reservations_data[ $ticket_2 ],
			$session->get_post_ticket_reservations( $post_id, $ticket_2 )
		);
		$this->assertNull( $session->get_post_ticket_reservations( $post_id, $ticket_3 ) );

		$sessions->update_reservations( 'test-token-1', []);

		$this->assertNull( $session->get_post_ticket_reservations( $post_id, $ticket_1 ) );
		$this->assertNull( $session->get_post_ticket_reservations( $post_id, $ticket_2 ) );
		$this->assertNull( $session->get_post_ticket_reservations( $post_id, $ticket_3 ) );
	}
}
