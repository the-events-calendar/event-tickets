<?php

namespace Tribe\Tickets\Commerce;

use TEC\Tickets\Commerce;
use TEC\Tickets\Commerce\Cart;

class CartTest extends \Codeception\TestCase\WPTestCase {

	public function test_repository_is_returned() {
		$cart       = new Cart();
		$repository = $cart->get_repository();
		$this->assertTrue( is_a( $repository, 'TEC\Tickets\Commerce\Cart\Unmanaged_Cart' ) );
	}

	public function test_does_not_process_empty_cart() {
		$cart = new Cart();
		$this->assertFalse( $cart->process( [] ) );
	}

	public function test_generates_valid_cart_hash_when_requested() {
		$cart1 = new Cart();
		$cart2 = new Cart();
		// Do not generate a hash
		$cart1_hash0 = $cart1->get_cart_hash();
		$this->assertNull( $cart1_hash0 );

		// Generate hashes
		$cart1_hash1 = $cart1->get_cart_hash( true );
		$cart1_hash2 = $cart1->get_cart_hash( true );
		$cart2_hash1 = $cart2->get_cart_hash( true );

		// Hash is 12 characters long
		$this->assertTrue( mb_strlen( $cart1_hash1, 'utf-8' ) === 12 );

		// Hash contains only alphanumeric characters
		preg_match( '/[^a-zA-Z0-9]/', $cart1_hash1, $matches );
		$this->assertEmpty( $matches );

		// Hash is persisted in the cart object
		$this->assertEquals( $cart1_hash1, $cart1_hash2 );

		// Hash is unique between carts
		$this->assertNotEquals( $cart1_hash1, $cart2_hash1 );
	}

	public function test_cart_has_valid_url() {
		$cart     = new Cart();
		$cart_url = $cart->get_url();

		$this->assertContains( Cart::$url_query_arg . '=' . Cart::REDIRECT_MODE, $cart_url );
	}

	public function test_invoice_transient_name_format_is_valid() {
		$id             = '10';
		$hash           = md5( $id );
		$transient_name = Cart::get_invoice_transient_name( $id );
		$this->assertEquals( Commerce::ABBR . '-invoice-' . $hash, $transient_name );
	}

	public function test_cart_transient_name_format_is_valid() {
		$id             = '10';
		$hash           = md5( $id );
		$transient_name = Cart::get_transient_name( $id );
		$this->assertEquals( Commerce::ABBR . '-cart-' . $hash, $transient_name );
	}
}
