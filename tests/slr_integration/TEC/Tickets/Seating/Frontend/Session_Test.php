<?php

namespace TEC\Tickets\Seating\Frontend;

use PHPUnit\Framework\Assert;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Service\OAuth_Token;
use TEC\Tickets\Seating\Service\Reservations;
use TEC\Tickets\Seating\Tables\Sessions;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Traits\WP_Remote_Mocks;

class Session_Test extends \Codeception\TestCase\WPTestCase {
	use WP_Remote_Mocks;
	use With_Uopz;
	use OAuth_Token;

	public function test_entry_manipulation(): void {
		$session         = tribe( Session::class );
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
		$this->assertEquals( $session->get_cookie_string( [ 23 => 'test-token', 89 => 'test-token-2' ] ), $setcookie_value );

		$session->remove_entry( 23, 'test-token' );

		$this->assertEquals( [ 89 => 'test-token-2' ], $session->get_entries() );
		$this->assertEquals( $session->get_cookie_string( [ 89 => 'test-token-2' ] ), $setcookie_value );

		$this->unset_uopz_functions();
		$this->set_fn_return( 'setcookie',
			function ( $name, $value, $expire, $path, $domain, $secure, $httponly ) use ( &$setcookie_value) {
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

		$this->assertTrue( $session->cancel_previous_for_object( 89 ) );

		// Insert a previous session in the database for the token and object ID.
		$sessions = tribe( Sessions::class );
		$sessions->upsert( 'test-token', 23, time() + 100 );
		$sessions->update_reservations( 'test-token', [ '1234567890', '0987654321' ] );
		// Mock the remote request to cancel the reservations.
		$this->mock_wp_remote(
			'post',
			tribe( Reservations::class )->get_cancel_url(),
			[
				'headers' => [
					'Authorization' => 'Bearer auth-token',
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

		$this->assertTrue( $session->cancel_previous_for_object( 23 ) );
		$this->assertEquals( [], $session->get_entries() );
		$this->assertEquals( [], $sessions->get_reservations_for_token( 'test-token' ) );
	}

	public function test_cancel_previous_for_object_fails_if_reservation_cancelation_fails(): void {
		$session = tribe( Session::class );

		$session->add_entry( 23, 'test-token' );

		// Insert a previous session in the database for the token and object ID.
		$sessions = tribe( Sessions::class );
		$sessions->upsert( 'test-token', 23, time() + 100 );
		$sessions->update_reservations( 'test-token', [ '1234567890', '0987654321' ] );
		// Mock the remote request to cancel the reservations.
		$this->mock_wp_remote(
			'post',
			tribe( Reservations::class )->get_cancel_url(),
			[
				'headers' => [
					'Authorization' => 'Bearer auth-token',
				],
				'body'    => wp_json_encode( [
					'eventId' => 'test-post-uuid',
					'ids'     => [ '1234567890', '0987654321' ]
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

		$this->assertFalse( $session->cancel_previous_for_object( 23 ) );
		$this->assertEquals( [ 23 => 'test-token' ], $session->get_entries() );
		$this->assertEquals( [ '1234567890', '0987654321' ], $sessions->get_reservations_for_token( 'test-token' ) );
	}

	public function test_cancel_previous_for_object_succeeds_if_session_missing(): void {
		$session = tribe( Session::class );

		$session->add_entry( 23, 'test-token' );

		// Insert a previous session in the database for the token and object ID.
		$sessions = tribe( Sessions::class );
		// Do not insert a session for the token and object ID: this should not cause the session deletion to fail.
		// Mock the remote request to cancel the reservations.
		$this->mock_wp_remote(
			'post',
			tribe( Reservations::class )->get_cancel_url(),
			[
				'headers' => [
					'Authorization' => 'Bearer auth-token',
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

		$this->assertTrue( $session->cancel_previous_for_object( 23 ) );
		$this->assertEquals( [], $session->get_entries() );
		$this->assertEquals( [], $sessions->get_reservations_for_token( 'test-token' ) );
	}

	public function test_pick_earliest_expiring_token_object_id(): void {
		$session = tribe( Session::class );
		$session->add_entry( 23, 'test-token-1' );
		$sessions = tribe( Sessions::class );
		// The session for object 23 will expire in 100 seconds.
		$sessions->upsert( 'test-token-1', 23, time() + 100 );
		$sessions->update_reservations( 'test-token-1', [ '1234567890', '0987654321' ] );

		$this->assertEquals(
			[ 'test-token-1', 23 ],
			$session->pick_earliest_expiring_token_object_id( $session->get_entries() )
		);

		// Add more sessions.
		$session->add_entry( 89, 'test-token-2' );
		$session->add_entry( 66, 'test-token-2' );
		// The session for object 89 will expire in 30 seconds.
		$sessions->upsert( 'test-token-2', 89, time() + 30 );
		$sessions->update_reservations( 'test-token-2', [ '1234567890', '0987654321' ] );
		// The session for object 66 will expire in 300 seconds.
		$sessions->upsert( 'test-token-3', 66, time() + 300 );
		$sessions->update_reservations( 'test-token-3', [ '1234567890', '0987654321' ] );

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
		$sessions->update_reservations( 'test-token-1', [ '1234567890', '0987654321' ] );
		update_post_meta( 23, Meta::META_KEY_UUID, 'test-post-uuid' );

		$session->add_entry( 89, 'test-token-2' );
		$sessions->upsert( 'test-token-2', 89, time() + 30 );
		$sessions->update_reservations( 'test-token-2', [ '891234567890', '890987654321' ] );
		update_post_meta( 89, Meta::META_KEY_UUID, 'test-post-uuid-2' );

		$this->mock_wp_remote(
			'post',
			tribe( Reservations::class )->get_confirm_url(),
			function (): \Generator {
				yield [
					'headers' => [
						'Authorization' => 'Bearer auth-token',
					],
					'body'    => wp_json_encode( [
						'eventId' => 'test-post-uuid',
						'ids'     => [ '1234567890', '0987654321' ]
					] ),
				];
				yield [
					'headers' => [
						'Authorization' => 'Bearer auth-token',
					],
					'body'    => wp_json_encode( [
						'eventId' => 'test-post-uuid-2',
						'ids'     => [ '891234567890', '890987654321' ]
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
		$sessions->update_reservations( 'test-token-1', [ '1234567890', '0987654321' ] );
		update_post_meta( 23, Meta::META_KEY_UUID, 'test-post-uuid' );

		$session->add_entry( 89, 'test-token-2' );
		$sessions->upsert( 'test-token-2', 89, time() + 30 );
		$sessions->update_reservations( 'test-token-2', [ '891234567890', '890987654321' ] );
		update_post_meta( 89, Meta::META_KEY_UUID, 'test-post-uuid-2' );

		$this->mock_wp_remote(
			'post',
			tribe( Reservations::class )->get_confirm_url(),
			function (): \Generator {
				yield [
					'headers' => [
						'Authorization' => 'Bearer auth-token',
					],
					'body'    => wp_json_encode( [
						'eventId' => 'test-post-uuid',
						'ids'     => [ '1234567890', '0987654321' ]
					] ),
				];
				yield [
					'headers' => [
						'Authorization' => 'Bearer auth-token',
					],
					'body'    => wp_json_encode( [
						'eventId' => 'test-post-uuid-2',
						'ids'     => [ '891234567890', '890987654321' ]
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
}
