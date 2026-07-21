<?php

namespace TEC\Tickets\Commerce;

use Codeception\TestCase\WPTestCase;
use TEC\Common\Monolog\Logger;

class Pending_Order_Test extends WPTestCase {

	/**
	 * The cart hash used across tests.
	 *
	 * @var string
	 */
	private const CART_HASH = 'abc123def456';

	/**
	 * The transient name that maps to CART_HASH.
	 *
	 * @var string
	 */
	private const TRANSIENT_NAME = 'tec_tickets_commerce_pending_order_abc123def456';

	/**
	 * @after
	 */
	public function clean_up_transient(): void {
		delete_transient( self::TRANSIENT_NAME );
	}

	/**
	 * Builds a Pending_Order with a mocked Cart and Logger.
	 *
	 * @param string|false|null $cart_hash  The value returned by Cart::get_cart_hash().
	 * @param int               $expiration The value returned by Cart::get_cart_expiration().
	 *
	 * @return array{0: Pending_Order, 1: Logger} The instance and its logger mock.
	 */
	private function make_pending_order( $cart_hash = self::CART_HASH, int $expiration = HOUR_IN_SECONDS ): array {
		$cart = $this->createMock( Cart::class );
		$cart->method( 'get_cart_hash' )->willReturn( $cart_hash );
		$cart->method( 'get_cart_expiration' )->willReturn( $expiration );

		$logger = $this->createMock( Logger::class );

		return [ new Pending_Order( $cart, $logger ), $logger ];
	}

	public function test_set_stores_gateway_order_id_in_transient(): void {
		[ $pending_order ] = $this->make_pending_order();

		$pending_order->set( 'gateway-order-1' );

		$this->assertSame( 'gateway-order-1', get_transient( self::TRANSIENT_NAME ) );
	}

	public function test_get_returns_stored_gateway_order_id(): void {
		[ $pending_order ] = $this->make_pending_order();
		set_transient( self::TRANSIENT_NAME, 'gateway-order-2', HOUR_IN_SECONDS );

		$this->assertSame( 'gateway-order-2', $pending_order->get() );
	}

	public function test_get_returns_null_when_nothing_stored(): void {
		[ $pending_order ] = $this->make_pending_order();

		$this->assertNull( $pending_order->get() );
	}

	public function test_set_then_get_round_trips_the_value(): void {
		[ $pending_order ] = $this->make_pending_order();

		$pending_order->set( 'round-trip-id' );

		$this->assertSame( 'round-trip-id', $pending_order->get() );
	}

	public function test_clear_removes_the_stored_value(): void {
		[ $pending_order ] = $this->make_pending_order();
		$pending_order->set( 'gateway-order-3' );

		$pending_order->clear();

		$this->assertNull( $pending_order->get() );
		$this->assertFalse( get_transient( self::TRANSIENT_NAME ) );
	}

	public function test_set_logs_and_stores_nothing_when_no_cart_hash(): void {
		[ $pending_order, $logger ] = $this->make_pending_order( false );
		$logger->expects( $this->once() )->method( 'debug' );

		$pending_order->set( 'gateway-order-4' );

		$this->assertFalse( get_transient( self::TRANSIENT_NAME ) );
	}

	public function test_get_returns_null_and_logs_when_no_cart_hash(): void {
		[ $pending_order, $logger ] = $this->make_pending_order( false );
		$logger->expects( $this->once() )->method( 'debug' );

		$this->assertNull( $pending_order->get() );
	}
}
