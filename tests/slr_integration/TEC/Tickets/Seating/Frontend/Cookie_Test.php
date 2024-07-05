<?php

namespace TEC\Tickets\Seating\Frontend;

class Cookie_Test extends \Codeception\TestCase\WPTestCase {
	/**
	 * @before
	 * @after
	 */
	public function reset_cookies(): void {
		unset( $_COOKIE[ Timer::COOKIE_NAME ] );
	}

	public function test_get_set_timer_cookie() {
		$cookie = new Timer();

		$this->assertNull( $cookie->get_session_token_object_id() );

		$_COOKIE[ Timer::COOKIE_NAME ] = $cookie->format_timer_cookie_string( 'test-token', 23 );

		$this->assertEquals( [ 'test-token', 23 ], $cookie->get_session_token_object_id() );
	}

	public function test_get_ephemeral_token_returns_null_if_cookie_missing() {
		$cookie = new Timer();

		$this->assertNull( $cookie->get_session_token_object_id() );
	}
}
