<?php

namespace Tribe\Tickets\Commerce\PayPal\Cart;

use Tribe__Tickets__Commerce__PayPal__Cart__Unmanaged as Cart;

class UnmanagedTest extends \Codeception\TestCase\WPTestCase {
	/**
	 * It should allow adding items to it
	 *
	 * @test
	 */
	public function should_allow_adding_items_to_it() {
		$cart = new Cart();
		$cart->set_id( 'foo' );
		$cart->add_item( 'id1', 3 );

		$this->assertEquals( 1, $cart->has_items() );
		$this->assertEquals( 3, $cart->has_item( 'id1' ) );
	}

	/**
	 * It should allow addding and removing the same item from the cart
	 *
	 * @test
	 */
	public function should_allow_addding_and_removing_the_same_item_from_the_cart() {
		$cart = new Cart();
		$cart->set_id( 'foo' );
		$cart->add_item( 'id1', 3 );
		$cart->add_item( 'id1', 3 );
		$cart->add_item( 'id2', 3 );

		$this->assertEquals( 6, $cart->has_item( 'id1' ) );
		$this->assertEquals( 3, $cart->has_item( 'id2' ) );

		$cart->remove_item( 'id2', 2 );
		$cart->remove_item( 'id1', 6 );

		$this->assertEquals( 1, $cart->has_items() );
		$this->assertFalse( $cart->has_item( 'id1' ) );
		$this->assertEquals( 1, $cart->has_item( 'id2' ) );
	}

	/**
	 * It should correctly save to db
	 *
	 * @test
	 */
	public function should_correctly_save_to_db() {
		$cart = new Cart();
		$cart->set_id( 'foo' );
		$cart->add_item( 'bar', 23 );
		$cart->add_item( 'baz', 89 );

		$cart->save();

		$this->assertEqualSets( [
			'bar' => [
				'quantity' => 23,
			],
			'baz' => [
				'quantity' => 89,
			],
		], get_transient( Cart::get_transient_name( 'foo' ) ) );

		$cart->clear();

		$this->assertFalse( get_transient( Cart::get_transient_name( 'foo' ) ) );
	}

	/**
	 * It should allow asserting the existence of the cart in the db
	 *
	 * @test
	 */
	public function should_allow_asserting_the_existence_of_the_cart_in_the_db() {
		$cart = new Cart();
		$cart->set_id( 'foo' );

		$this->assertFalse( $cart->exists() );

		$_COOKIE[\Tribe__Tickets__Commerce__PayPal__Gateway::$invoice_cookie_name] = 'foo';

		$this->assertFalse( $cart->exists() );

		set_transient( Cart::get_transient_name( 'foo' ), [ 'bar' => 23, 'baz' => 89 ] );

		$this->assertTrue( $cart->exists() );

		$cart->set_id( 'bar' );

		$this->assertFalse( $cart->exists() );

		$cart->set_id( 'bar' );

		set_transient( Cart::get_transient_name( 'bar' ), [ 'bar' => 23, 'baz' => 89 ] );

		$this->assertTrue( $cart->exists() );
	}
}
