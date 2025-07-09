<?php

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Cart;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Cart;

/**
 * Class Agnostic_CartTest
 *
 * @since 5.21.0
 */
class Agnostic_CartTest extends WPTestCase {

	public function test_does_not_have_public_page() {
		$cart = new Agnostic_Cart();

		$assertion_msg = 'Agnostic_Cart->has_public_page() should return false.';
		$this->assertFalse( $cart->has_public_page(), $assertion_msg );
	}

	public function test_default_mode_is_redirect() {
		$cart = new Agnostic_Cart();

		$assertion_msg = 'Agnostic_Cart->get_mode() should return the value of Cart::REDIRECT_MODE.';
		$this->assertEquals( Cart::REDIRECT_MODE, $cart->get_mode(), $assertion_msg );
	}

	public function test_get_items_returns_false_if_no_items() {
		$cart = new Agnostic_Cart();

		$assertion_msg = 'Agnostic_Cart->get_items() should return an empty array if no items were added';
		$this->assertEmpty( $cart->get_items(), $assertion_msg );
	}

	/**
	 * @dataProvider items_data_provider
	 */
	public function test_add_items_formats_items( $ticket_id, $quantity, $extra, $formatted_item ) {
		$cart = new Agnostic_Cart();

		if ( $extra ) {
			$cart->upsert_item( $ticket_id, $quantity, $extra );
		} else {
			$cart->upsert_item( $ticket_id, $quantity );
		}

		$assertion_msg = 'Adding an item to the cart should sanitize and format it properly before storing.';
		$this->assertEquals( $formatted_item, $cart->get_items(), $assertion_msg );
	}

	public function test_add_item_updates_items_by_id() {
		$cart  = new Agnostic_Cart();
		$items = $this->items_data_provider();

		$this->assertFalse(
			$cart->has_items(),
			'Agnostic_Cart->has_items() should return false if no items were added.'
		);

		$item_ids = [];

		foreach ( $items as $i => $item ) {
			[ $id, $quantity, $extra, ] = $item;

			// Skip testing items with a quantity of zero.
			if ( 0 === $quantity ) {
				continue;
			}

			$item_ids[ $id ] = true;
			$quantity        = (int) $quantity;
			$extra           ??= [];

			$cart->upsert_item( $id, $quantity, $extra );

			$assertion_msg = 'Adding items with an id already existing in the cart should not create new items in the cart.';
			$this->assertEquals( count( $item_ids ), $cart->has_items(), $assertion_msg );

			$cart_items = $cart->get_items();
			$this->assertArrayHasKey( $id, $cart_items, $assertion_msg );

			$assertion_msg = "Adding items with an id already existing in the cart should change the quantity of that item in the cart. Item: {$i}";
			$this->assertEquals( abs( $quantity ), $cart_items[ $id ]['quantity'], $assertion_msg );
		}
	}

	public function test_remove_item_removes_all_items_by_id() {
		$cart     = new Agnostic_Cart();
		$i        = 0;
		$quantity = [ PHP_INT_MAX, PHP_INT_SIZE ];

		while ( $i < 2 ) {
			$i++;
			$cart->upsert_item( $i, $quantity[ $i - 1 ] );
		}

		while ( $i > 0 ) {
			$cart->remove_item( $i );
			$i--;
		}

		$assertion_msg = 'Removing all items added should result in an empty cart.';
		$this->assertEmpty( $cart->has_items(), $assertion_msg );
	}

	public function test_has_items_returns_ticket_count() {
		$cart = new Agnostic_Cart();

		// Add items to the cart and check the quantity of items.
		$cart->upsert_item( 1, 1 );
		$this->assertEquals( 1, $cart->has_items(), 'Adding items to the cart should increase the item count.' );
		$this->assertEquals( 1, $cart->get_item_quantity( 1 ), 'Adding items to the cart should increase the item count.' );

		$cart->upsert_item( 2, 2 );
		$this->assertEquals( 2, $cart->has_items(), 'Adding items to the cart should increase the item count.' );
		$this->assertEquals( 2, $cart->get_item_quantity( 2 ), 'Adding items to the cart should increase the item count.' );

		// Update the quantity of an existing item.
		$cart->upsert_item( 1, 3 );
		$this->assertEquals( 2, $cart->has_items(), 'Updating the quantity of an existing item should not increase the item count.' );
		$this->assertEquals( 3, $cart->get_item_quantity( 1 ), 'Updating the quantity of an existing item should update the item count.' );
	}

	public function test_has_item_finds_item_by_id() {
		$cart = new Agnostic_Cart();
		[ $id, $quantity, , ] = $this->items_data_provider()[0];

		$cart->upsert_item( $id, $quantity );

		$assertion_msg = '`Agnostic_Cart->has_item( $id )` should return the quantity of that item currently in the cart.';
		$this->assertEquals( $quantity, $cart->has_item( $id ), $assertion_msg );

		$assertion_msg = '`Agnostic_Cart->has_item( $id )` should return false if the ID does not exist.';
		$this->assertFalse( $cart->has_item( $id + 10 ), $assertion_msg );
		$this->assertFalse( $cart->has_item( 0 ), $assertion_msg );
		$this->assertFalse( $cart->has_item( 'abc' ), $assertion_msg );
	}

	public function test_does_not_process_empty_data() {
		$assertion_msg = '`Agnostic_Cart->process( $data )` should return false if no data was passed in.';
		$this->assertFalse( ( new Agnostic_Cart() )->process(), $assertion_msg );
	}

	public function test_prepare_data_does_not_modify_data() {
		$cart  = new Agnostic_Cart();
		$items = $this->items_data_provider();
		$data  = $cart->prepare_data( $items );

		$assertion_msg = '`Agnostic_Cart->prepare_data( $items )` should not modify the data passed in.';
		$this->assertEquals( $items, $data, $assertion_msg );
	}

	public function test_it_overrides_an_existing_item_quantity() {
		$cart = new Agnostic_Cart();
		$cart->upsert_item( 1, 1 );

		// Assert we have the initial item.
		$this->assertTrue( $cart->has_item( 1 ) );
		$this->assertEquals( 1, $cart->get_item_quantity( 1 ) );

		// Assert we have an updated item quantity.
		$cart->upsert_item( 1, 2 );
		$this->assertEquals( 2, $cart->get_item_quantity( 1 ) );

		// Setting the quantity to zero should remove the item.
		$cart->upsert_item( 1, 0 );
		$this->assertFalse( $cart->has_item( 1 ) );

		// Passing a negative quantity should result in a positive quantity.
		$cart->upsert_item( 1, -1 );
		$this->assertEquals( 1, $cart->get_item_quantity( 1 ) );
	}

	/**
	 * @test
	 */
	public function it_should_filter_by_item_type() {
		$cart = new Agnostic_Cart();

		// Add different item types
		$cart->upsert_item( 1, 1 );
		$cart->upsert_item( 2, 1, [ 'type' => 'non-ticket' ] );
		$cart->upsert_item( 3, 2, [ 'type' => 'ticket' ] );

		$this->assertEquals( 3, $cart->has_items(), 'Adding items to the cart should increase the item count.' );
		$this->assertEquals( 2, count( $cart->get_items_in_cart() ), 'It should get ticket items by default' );
		$this->assertEquals( 1, count( $cart->get_items_in_cart( false, 'non-ticket' ) ), 'It should get items by type' );
	}

	/**
	 * @test
	 */
	public function it_should_calculate_full_params_once() {
		$cart = new class() extends Agnostic_Cart {
			protected $called_count = 0;
			protected function add_ticket_params( $item ) {
				$this->called_count++;
				return $item;
			}

			public function get_called_count(): int {
				return $this->called_count;
			}
		};

		$cart->upsert_item( 1, 1 );
		$this->assertEquals( 0, $cart->get_called_count(), 'Full item params should not be called after item is added' );

		$cart->get_items_in_cart( true );
		$this->assertEquals( 1, $cart->get_called_count(), 'Full item params should be called once' );

		$cart->get_items_in_cart( true );
		$this->assertEquals( 1, $cart->get_called_count(), 'Full item params should not be called again' );
	}

	public function items_data_provider() {
		/*
		 * Format for these tests is:
		 * [ ticket_id, quantity, extra_data, expected_formatted_item ]
		 */

		return [
			// Int inputs, with named extra values
			[
				10,
				2,
				[ 'name' => 'Item Name' ],
				[
					10 => [
						'type'      => 'ticket',
						'ticket_id' => 10,
						'quantity'  => 2,
						'extra'     => [ 'name' => 'Item Name' ],
					],
				],
			],

			// Numeric inputs, with numerically keyed extra values
			[
				'10',
				2,
				[ 'Item Name', 'Item SKU' ],
				[
					10 => [
						'ticket_id' => 10,
						'quantity'  => 2,
						'type'      => 'ticket',
						'extra'     => [ 'Item Name', 'Item SKU' ],
					],
				],
			],

			// Int inputs, with empty extra values
			[
				10,
				2,
				[],
				[
					10 => [
						'ticket_id' => 10,
						'quantity'  => 2,
						'type'      => 'ticket',
						'extra'     => [],
					],
				],
			],

			// Int inputs, without extra values
			[
				10,
				2,
				null,
				[
					10 => [
						'ticket_id' => 10,
						'quantity'  => 2,
						'type'      => 'ticket',
						'extra'     => [],
					],
				],
			],

			// Numeric inputs, without extra values are converted to int
			[
				'10',
				2,
				null,
				[
					10 => [
						'ticket_id' => 10,
						'quantity'  => 2,
						'type'      => 'ticket',
						'extra'     => [],
					],
				],
			],

			// Non-ticket type.
			[
				15,
				3,
				[
					'type' => 'non-ticket',
				],
				[
					15 => [
						'non-ticket_id' => 15,
						'quantity'      => 3,
						'type'          => 'non-ticket',
						'extra'         => [],
					],
				],
			],

			// Negative quantity should be converted to positive.
			[
				20,
				-1,
				null,
				[
					20 => [
						'ticket_id' => 20,
						'quantity'  => 1,
						'type'      => 'ticket',
						'extra'     => [],
					],
				],
			],

			// Quantity of zero should result in no items added.
			[
				25,
				0,
				null,
				[],
			],
		];
	}
}
