<?php
/**
 * Regression tests for the cart-hash session-fixation fix (SVUL-L34).
 *
 * The cart hash is now derived solely from the visitor's server-set cart cookie. The `tec-tc-cookie`
 * request param can no longer set or override it, so an attacker can no longer pin a victim onto a
 * cart hash of their choosing (and then hijack the resulting order via the gateway endpoints).
 *
 * These tests drive the real Checkout::parse_request() and assert it never writes the cart hash cookie
 * from the request param, regardless of the param value or any existing cookie.
 */

namespace TEC\Tickets\Commerce\Gateways;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Checkout;
use Tribe\Tests\Traits\With_Uopz;

class Pending_Order_Session_Fixation_Test extends WPTestCase {

	use With_Uopz;

	/**
	 * A 12 character cart hash an attacker might try to pin onto a victim.
	 */
	private const ATTACKER_HASH = 'attackrhash2';

	/**
	 * @after
	 */
	public function reset_request_state(): void {
		unset(
			$_REQUEST[ Cart::$cookie_query_arg ],
			$_GET[ Cart::$cookie_query_arg ],
			$_COOKIE[ Cart::get_cart_hash_cookie_name() ]
		);
	}

	/**
	 * Runs the real Checkout::parse_request() while capturing every value handed to the cart-hash
	 * cookie setter, so we can assert the request param never drives a cookie write.
	 *
	 * @param array $captured Populated with each value passed to set_cart_hash_cookie().
	 */
	private function run_parse_request_capturing_cookie_writes( array &$captured ): void {
		// parse_request() only runs on the checkout page; force that gate open.
		$this->set_class_fn_return( Checkout::class, 'is_current_page', true );

		// Spy on the cart-hash cookie setter.
		$this->set_class_fn_return(
			Cart::class,
			'set_cart_hash_cookie',
			function ( $value = '' ) use ( &$captured ) {
				$captured[] = $value;
				return true;
			},
			true
		);

		tribe( Checkout::class )->parse_request();
	}

	/**
	 * A cart hash supplied only in the request param must not be adopted.
	 */
	public function test_parse_request_ignores_the_query_param_without_a_cookie(): void {
		unset( $_COOKIE[ Cart::get_cart_hash_cookie_name() ] );
		$_REQUEST[ Cart::$cookie_query_arg ] = self::ATTACKER_HASH;
		$_GET[ Cart::$cookie_query_arg ]     = self::ATTACKER_HASH;

		$captured = [];
		$this->run_parse_request_capturing_cookie_writes( $captured );

		$this->assertSame(
			[],
			$captured,
			'parse_request must not set the cart hash cookie from a request param.'
		);
	}

	/**
	 * Even a param that happens to match the visitor's existing cookie must not trigger a param-driven
	 * cookie write: the cookie is authoritative on its own, the param plays no role.
	 */
	public function test_parse_request_ignores_the_query_param_even_when_it_matches_the_cookie(): void {
		$_COOKIE[ Cart::get_cart_hash_cookie_name() ] = self::ATTACKER_HASH;
		$_REQUEST[ Cart::$cookie_query_arg ]          = self::ATTACKER_HASH;
		$_GET[ Cart::$cookie_query_arg ]              = self::ATTACKER_HASH;

		$captured = [];
		$this->run_parse_request_capturing_cookie_writes( $captured );

		$this->assertSame(
			[],
			$captured,
			'parse_request must not write the cart hash cookie based on the request param.'
		);
	}
}
