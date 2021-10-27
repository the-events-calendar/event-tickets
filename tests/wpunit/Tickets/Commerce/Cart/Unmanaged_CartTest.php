<?php

namespace Tribe\Tickets\Commerce\Cart;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Cart\Unmanaged_Cart;

class Unmanaged_CartTest extends \Codeception\TestCase\WPTestCase {

	public function test_does_not_have_public_page() {
		$cart = new Unmanaged_Cart();
		$this->assertFalse( $cart->has_public_page() );
	}

	public function test_default_mode_is_redirect() {
		$cart = new Unmanaged_Cart();
		$this->assertEquals( Cart::REDIRECT_MODE, $cart->get_mode() );
	}

	public function test_get_items_returns_false_if_no_items() {
		$cart = new Unmanaged_Cart();
		$this->assertEmpty( $cart->get_items() );
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

		$this->assertEquals( $formatted_item, $cart->get_items() );
	}

	public function test_add_item_updates_items_by_id() {
		$cart     = new Unmanaged_Cart();
		$items    = $this->items_data_provider();
		$quantity = (int) $cart->has_items();
		$this->assertEmpty( $quantity );

		foreach ( $items as $item ) {

			if ( is_numeric( $item[0] ) ) {
				$quantity += $item[1];
				$cart->add_item( $item[0], $item[1], $item[2] ?? [] );

				// All items have the same ID so this count should not change
				$this->assertEquals( 1, $cart->has_items() );

				// Update quantities as each item gets updated
				$item[3][ $item[0] ]['quantity'] = $quantity;
				$this->assertEquals( $item[3], $cart->get_items() );
			}

		}
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
				$this->assertEquals( $count, $cart->has_items() );
			}

		}
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
