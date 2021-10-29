<?php

namespace Tribe\Tickets\Commerce;

use TEC\Tickets\Commerce;
use TEC\Tickets\Commerce\Cart;

class CartTest extends \Codeception\TestCase\WPTestCase {

	private static $cart_hash;

	public function test_repository_is_returned() {
		$cart       = new Cart();
		$repository = $cart->get_repository();

		$assertion_msg = 'Cart->get_repository() should return an instance of Unmanaged_Cart';
		$this->assertTrue( is_a( $repository, 'TEC\Tickets\Commerce\Cart\Unmanaged_Cart' ), $assertion_msg );
	}

	public function test_does_not_process_empty_cart() {
		$cart = new Cart();

		$assertion_msg = 'Cart->process() should return false for empty carts.';
		$this->assertFalse( $cart->process( [] ), $assertion_msg );
	}

	public function test_generates_valid_cart_hash_when_requested() {
		$cart1 = new Cart();
		$cart2 = new Cart();
		// Do not generate a hash
		$cart1_hash0 = $cart1->get_cart_hash();

		$assertion_msg = 'Cart->get_cart_hash() should return null when called in an empty cart without the $generate parameter.';
		$this->assertNull( $cart1_hash0, $assertion_msg );

		// Generate hashes
		$cart1_hash1 = $cart1->get_cart_hash( true );
		$cart1_hash2 = $cart1->get_cart_hash( true );
		$cart2_hash1 = $cart2->get_cart_hash( true );

		static::$cart_hash = $cart1_hash1;

		$assertion_msg = 'Cart hash should be a string';
		$this->assertIsString( $cart1_hash1, $assertion_msg );

		$assertion_msg = 'Cart hashes should be 12 characters long';
		$this->assertTrue( mb_strlen( $cart1_hash1, 'utf-8' ) === 12, $assertion_msg );

		preg_match( '/[a-zA-Z0-9]/', $cart1_hash1, $positive_matches );
		preg_match( '/[^a-zA-Z0-9]/', $cart1_hash1, $negative_matches );

		$assertion_msg = 'Cart hashes should contain alphanumeric characters.';
		$this->assertNotEmpty( $positive_matches, $assertion_msg );

		$assertion_msg = 'Cart hashes should not contain non-alphanumeric characters.';
		$this->assertEmpty( $negative_matches, $assertion_msg );

		$assertion_msg = 'Cart hash should remain the same and not be regenerated within the same instance.';
		$this->assertEquals( $cart1_hash1, $cart1_hash2, $assertion_msg );

		$assertion_msg = 'Cart hash should remain the same across different cart instances in the same request.';
		$this->assertEquals( $cart1_hash1, $cart2_hash1, $assertion_msg );
	}

	/**
	 * @depends test_generates_valid_cart_hash_when_requested
	 */
	public function test_cart_hash_changes_when_cart_is_cleared() {
		if ( empty( static::$cart_hash ) ) {
			$this->markTestSkipped( 'Cart hash was empty' );
		}

		$cart = new Cart();
		$cart->clear_cart();
		$cart_hash = $cart->get_cart_hash();

		$assertion_msg = 'Cart hash should return null if not re-generated after clearing';
		$this->assertNull( $cart_hash, $assertion_msg );

		$cart_hash = $cart->get_cart_hash( true );

		$assertion_msg = 'Cart hash should be unique if re-generated after clearing';
		$this->assertNotEquals( static::$cart_hash, $cart_hash, $assertion_msg );
	}

	public function test_cart_has_valid_url() {
		$cart     = new Cart();
		$cart_url = $cart->get_url();

		$assertion_msg = 'Cart->get_url() should return a url containing Cart::$url_query_arg=Cart::REDIRECT_MODE as query args.';
		$this->assertContains( Cart::$url_query_arg . '=' . Cart::REDIRECT_MODE, $cart_url, $assertion_msg );
	}

	public function test_cart_transient_name_format_is_valid() {
		$id             = '10';
		$hash           = md5( $id );
		$transient_name = Cart::get_transient_name( $id );

		$assertion_msg = 'Invoice transient names should be in the format Commerce::ABBR-cart-ID_HASH.';
		$this->assertEquals( Commerce::ABBR . '-cart-' . $hash, $transient_name, $assertion_msg );
	}

	public function test_current_page_detection_parameters_are_valid() {
		$cart = new Cart();

		// Empty request
		$assertion_msg = 'When called without Cart::$url_query_arg, Cart->is_current_page() should return false.';
		$this->assertFalse( $cart->is_current_page(), $assertion_msg );

		// Redirect request
		$_REQUEST[ Cart::$url_query_arg ] = Cart::REDIRECT_MODE;

		$assertion_msg = 'When called with Cart::$url_query_arg, Cart->is_current_page() should return true.';
		$this->assertTrue( $cart->is_current_page(), $assertion_msg );
	}

	public function test_redirect_mode_is_available() {
		$cart = new Cart();

		$assertion_msg = 'Cart::REDIRECT_MODE should always be one of the available modes.';
		$this->assertContains( Cart::REDIRECT_MODE, $cart->get_available_modes(), $assertion_msg );
	}
}
