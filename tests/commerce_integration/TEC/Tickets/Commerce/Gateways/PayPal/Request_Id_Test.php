<?php
/**
 * Regression tests for the idempotency key sent to PayPal.
 *
 * PayPal treats `PayPal-Request-Id` as an idempotency key: the same key against the same endpoint is
 * answered with the first call's result rather than performed again. It used to be an md5 of the
 * visitor's cart hash, read from a cookie, which is wrong in both directions. A buyer whose cookie was
 * dropped sent an md5 of the empty string, which is a fixed value every other cookie-less request on
 * every site also sends; and a buyer whose cart changed between a call and its retry sent a different
 * key, so a retried capture looked like a new one instead of being deduplicated.
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Cart;
use Tribe__Utils__Array as Arr;
use Tribe\Tests\Traits\With_Uopz;

class Request_Id_Test extends WPTestCase {

	use With_Uopz;

	/**
	 * Headers seen by the last intercepted outbound request, keyed by request order.
	 *
	 * @var array<int,array>
	 */
	private array $seen = [];

	public function setUp(): void {
		parent::setUp();

		$this->seen = [];

		add_filter( 'pre_http_request', [ $this, 'intercept' ], 10, 2 );
	}

	public function tearDown(): void {
		remove_filter( 'pre_http_request', [ $this, 'intercept' ], 10 );

		parent::tearDown();
	}

	/**
	 * Records the headers of an outbound request and answers it without touching the network.
	 *
	 * @param mixed $preempt The short-circuit value.
	 * @param array $args    The request arguments.
	 *
	 * @return array A canned PayPal-shaped response.
	 */
	public function intercept( $preempt, $args ) {
		$this->seen[] = $args['headers'] ?? [];

		return [
			'headers'  => [],
			'body'     => wp_json_encode( [ 'id' => 'IRRELEVANT', 'status' => 'COMPLETED' ] ),
			'response' => [ 'code' => 200, 'message' => 'OK' ],
			'cookies'  => [],
			'filename' => null,
		];
	}

	/**
	 * The regression: with no cart cookie the key used to be md5( '' ) for everybody, so two unrelated
	 * buyers captured under the same idempotency key.
	 */
	public function test_two_orders_do_not_share_a_key_without_a_cart_cookie(): void {
		$this->drop_the_cart_cookie();

		tribe( Client::class )->capture_order( 'ORDERAAAAAAAAAAAA' );
		tribe( Client::class )->capture_order( 'ORDERBBBBBBBBBBBB' );

		$first  = $this->request_id( 0 );
		$second = $this->request_id( 1 );

		$this->assertNotSame(
			'',
			$first,
			'A capture must carry an idempotency key.'
		);

		$this->assertNotSame(
			$first,
			$second,
			'Two different PayPal orders must never share an idempotency key.'
		);

		$this->assertNotSame(
			md5( '' ),
			$first,
			'The key must not be the md5 of an empty cart hash, which every cookie-less request shares.'
		);
	}

	/**
	 * Capturing the same order twice must present the same key, which is what lets PayPal recognise a
	 * retry rather than treating it as a second capture.
	 */
	public function test_the_same_order_captured_twice_reuses_its_key(): void {
		$this->drop_the_cart_cookie();

		tribe( Client::class )->capture_order( 'ORDERAAAAAAAAAAAA' );
		tribe( Client::class )->capture_order( 'ORDERAAAAAAAAAAAA' );

		$this->assertSame(
			$this->request_id( 0 ),
			$this->request_id( 1 ),
			'A retried capture of the same order must present the same idempotency key.'
		);
	}

	/**
	 * Losing the cart cookie mid-checkout must not change the key for the same order, which is the
	 * whole failure this ticket is about: the retry has to be recognised as a retry.
	 */
	public function test_key_survives_losing_the_cart_cookie(): void {
		tribe( Cart::class )->get_repository()->set_hash( 'carthash0001' );
		tribe( Client::class )->capture_order( 'ORDERAAAAAAAAAAAA' );

		$this->drop_the_cart_cookie();
		tribe( Client::class )->capture_order( 'ORDERAAAAAAAAAAAA' );

		$this->assertSame(
			$this->request_id( 0 ),
			$this->request_id( 1 ),
			'The idempotency key must not depend on the cart cookie surviving the round trip.'
		);
	}

	/**
	 * Reading the same order and capturing it are different operations, so they must not collide on one
	 * key: PayPal scopes idempotency per endpoint, and sharing a key across the two invites confusion.
	 */
	public function test_reading_and_capturing_an_order_use_different_keys(): void {
		$this->drop_the_cart_cookie();

		tribe( Client::class )->get_order( 'ORDERAAAAAAAAAAAA' );
		tribe( Client::class )->capture_order( 'ORDERAAAAAAAAAAAA' );

		$this->assertNotSame(
			$this->request_id( 0 ),
			$this->request_id( 1 ),
			'Reading an order and capturing it must not share an idempotency key.'
		);
	}

	/**
	 * PayPal documents the idempotency header as 38 single-byte characters, and an over-long one is
	 * refused rather than trimmed -- which would take idempotency off the very calls it protects, on
	 * every endpoint this client sends the header to.
	 */
	public function test_every_key_fits_the_header_paypal_accepts(): void {
		$this->drop_the_cart_cookie();

		tribe( Client::class )->get_order( 'ORDERAAAAAAAAAAAA' );
		tribe( Client::class )->capture_order( 'ORDERAAAAAAAAAAAA' );

		// Both calls, because the length is a property of the key and not of one endpoint.
		foreach ( array_keys( $this->seen ) as $index ) {
			$this->assertLessThanOrEqual(
				38,
				strlen( $this->request_id( $index ) ),
				'PayPal allows the idempotency header 38 single-byte characters.'
			);
		}
	}

	/**
	 * Empties the cart hash, modelling the cart cookie not reaching the request.
	 */
	private function drop_the_cart_cookie(): void {
		tribe( Cart::class )->get_repository()->set_hash( '' );
	}

	/**
	 * The PayPal-Request-Id header of the nth intercepted request.
	 */
	private function request_id( int $index ): string {
		return Arr::get( $this->seen, [ $index, 'PayPal-Request-Id' ], '' );
	}
}
