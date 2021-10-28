<?php

namespace Tribe\Tickets\Commerce\Cart;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Cart\Unmanaged_Cart;

class Unmanaged_CartTest extends \Codeception\TestCase\WPTestCase {

	public function test_does_not_have_public_page() {
		$cart = new Unmanaged_Cart();

		$assertion_msg = 'UnmanagedCart->has_public_page() should return false.';
		$this->assertFalse( $cart->has_public_page(), $assertion_msg );
	}

	public function test_default_mode_is_redirect() {
		$cart = new Unmanaged_Cart();

		$assertion_msg = 'UnmanagedCart->get_mode() should return the value of Cart::REDIRECT_MODE.';
		$this->assertEquals( Cart::REDIRECT_MODE, $cart->get_mode(), $assertion_msg );
	}

	public function test_get_items_returns_false_if_no_items() {
		$cart = new Unmanaged_Cart();

		$assertion_msg = 'UnmanagedCart->get_items() should return an empty array if no items were added';
		$this->assertEmpty( $cart->get_items(), $assertion_msg );
	}

	/**
	 * @dataProvider items_data_provider
	 */
	public function test_add_items_formats_items( $ticket_id, $quantity, $extra, $formatted_item ) {
		$cart = new Unmanaged_Cart();

		if ( $extra ) {
			$cart->add_item( $ticket_id, $quantity, $extra );
		} else {
			$cart->add_item( $ticket_id, $quantity );
		}

		$assertion_msg = 'Adding an item to the cart should sanitize and format it properly before storing.';
		$this->assertEquals( $formatted_item, $cart->get_items(), $assertion_msg );
	}

	public function test_add_item_updates_items_by_id() {
		$cart     = new Unmanaged_Cart();
		$items    = $this->items_data_provider();
		$quantity = (int) $cart->has_items();

		$assertion_msg = 'UnmanagedCart->has_items() should return zero if no items were added.';
		$this->assertEquals( 0, $quantity, $assertion_msg );

		foreach ( $items as $item ) {

			if ( is_numeric( $item[0] ) ) {
				$quantity += $item[1];
				$cart->add_item( $item[0], $item[1], $item[2] ?? [] );

				// Update quantities as each item gets updated
				$item[3][ $item[0] ]['quantity'] = $quantity;

				$assertion_msg = 'Adding items with an id already existing in the cart should not create new items in the cart.';
				$this->assertEquals( 1, $cart->has_items(), $assertion_msg );

				$assertion_msg = 'Adding items with an id already existing in the cart should change the quantity of that item in the cart.';
				$this->assertEquals( $item[3], $cart->get_items(), $assertion_msg );
			}

		}
	}

	public function test_remove_item_removes_correct_quantities() {
		$cart = new Unmanaged_Cart();
		$i    = 0;

		while ( $i < 3 ) {
			$i ++;
			$quantity = pow( $i, 2 );
			$cart->add_item( $i, $quantity );

			switch ( $i ) {
				case 1:
					$cart->remove_item( $i, $quantity );

					$assertion_msg = 'Removing the exact quantity available should remove the item.';
					$this->assertFalse( $cart->has_item( $i ), $assertion_msg );
					break;
				case 2:
					$cart->remove_item( $i, $quantity / 2 );

					$assertion_msg = 'Removing fewer items than available should result in a positive amount.';
					$this->assertEquals( $quantity / 2, $cart->has_item( $i ), $assertion_msg );
					break;
				case 3:
					$cart->remove_item( $i, pow( $quantity, 2 ) );

					$assertion_msg = 'Removing more items than available should remove the item.';
					$this->assertEquals( 0, $cart->has_item( $i ), $assertion_msg );
					break;
			}
		}

	}

	public function test_remove_item_removes_all_items_by_id() {
		$cart     = new Unmanaged_Cart();
		$i        = 0;
		$quantity = [ PHP_INT_MAX, PHP_INT_SIZE ];

		while ( $i < 2 ) {
			$i ++;
			$cart->add_item( $i, $quantity[ $i - 1 ] );
		}

		while ( $i > 0 ) {
			$cart->remove_item( $i );
			$i --;
		}

		$assertion_msg = 'Removing all items added should result in an empty cart.';
		$this->assertEmpty( $cart->has_items(), $assertion_msg );
	}

	public function test_has_items_returns_ticket_count() {
		$cart  = new Unmanaged_Cart();
		$items = $this->items_data_provider();
		$count = 0;

		foreach ( $items as $item ) {
			if ( is_numeric( $item[0] ) ) {
				$count ++;
				// Add $count to the item_id so they count as different tickets
				$cart->add_item( $item[0] + $count, $item[1] );

				$assertion_msg = 'Adding items with unique ids should properly update the item count in the cart.';
				$this->assertEquals( $count, $cart->has_items(), $assertion_msg );
			}
		}
	}

	public function test_has_item_finds_item_by_id() {
		$cart  = new Unmanaged_Cart();
		$items = $this->items_data_provider();
		$item  = reset( $items );

		$cart->add_item( $item[0], $item[1] );

		$assertion_msg = '`UnmanagedCart->has_item( $id )` should return the quantity of that item currently in the cart.';
		$this->assertEquals( $item[1], $cart->has_item( $item[0] ), $assertion_msg );

		$assertion_msg = '`UnmanagedCart->has_item( $id )` should return false if the ID does not exist.';
		$this->assertFalse( $cart->has_item( $item[0] + 10 ), $assertion_msg );
		$this->assertFalse( $cart->has_item( 0 ), $assertion_msg );
		$this->assertFalse( $cart->has_item( 'abc' ), $assertion_msg );
	}

	public function test_does_not_process_empty_data() {
		$cart = new Unmanaged_Cart();

		$assertion_msg = '`UnmanagedCart->process( $data )` should return false if no data was passed in.';
		$this->assertFalse( $cart->process(), $assertion_msg );
		$this->assertFalse( $cart->process( [] ), $assertion_msg );
	}

	public function test_prepare_data_does_not_modify_data() {
		$cart  = new Unmanaged_Cart();
		$items = $this->items_data_provider();
		$data  = $cart->prepare_data( $items );

		$assertion_msg = '`UnmanagedCart->prepare_data( $items )` should not modify the data passed in.';
		$this->assertEquals( $items, $data, $assertion_msg );

	}

	public function items_data_provider() {
		return [
			// Int inputs, with named extra values
			[
				10,
				2,
				[ 'name' => 'Item Name' ],
				[ 10 => [ 'ticket_id' => 10, 'quantity' => 2, 'extra' => [ 'name' => 'Item Name' ] ] ],
			],
			// Numeric inputs, with numerically keyed extra values
			[
				'10',
				'2',
				[ 'Item Name', 'Item SKU' ],
				[ 10 => [ 'ticket_id' => 10, 'quantity' => 2, 'extra' => [ 'Item Name', 'Item SKU' ] ] ],
			],
			// Int inputs, with empty extra values
			[ 10, 2, [], [ 10 => [ 'ticket_id' => 10, 'quantity' => 2, 'extra' => [] ] ] ],
			// Int inputs, without extra values
			[ 10, 2, null, [ 10 => [ 'ticket_id' => 10, 'quantity' => 2, 'extra' => [] ] ] ],
			// Numeric inputs, without extra values are converted to int
			[ '10', '2', null, [ 10 => [ 'ticket_id' => 10, 'quantity' => 2, 'extra' => [] ] ] ],
			// Non-numeric inputs, without extra values are not added
			[ 'abc', 'def', null, [] ],
			// Non-numeric inputs, without extra values are not added
			[ 'abc', 'def', [ 'name' => 'Item Name' ], [] ],
		];
	}

}
