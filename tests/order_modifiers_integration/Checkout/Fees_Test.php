<?php

namespace TEC\Tickets\Commerce\Order_Modifiers\Checkout;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Float_Value;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Fee_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Reservations_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Commerce\Cart\Unmanaged_Cart as Cart;
use TEC\Tickets\Commerce\Shortcodes\Checkout_Shortcode;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Tickets\Commerce\Cart as Commerce_Cart;
use Generator;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\PayPal\Fees as PayPalFees;

class Fees_Test extends Controller_Test_Case {
	use Ticket_Maker;
	use Attendee_Maker;
	use With_Tickets_Commerce;
	use Reservations_Maker;
	use SnapshotAssertions;
	use Order_Maker;
	use Fee_Creator;
	use With_Uopz;

	protected string $controller_class = Fees::class;

	/**
	 * @test
	 * @dataProvider cart_totals_data_provider
	 */
	public function it_should_calculate_fees(
		Float_Value $ticket_price,
		Float_Value $fee_raw_amount,
		string $fee_application,
		Float_Value $expected_total,
		int $quantity
	) {
		$event_id = self::factory()->post->create(
			[
				'post_title' => 'The Event',
			]
		);

		// Create a fee with the specified raw amount.
		$fee = $this->create_fee( [ 'raw_amount' => $fee_raw_amount ] );
		$this->set_fee_application( $fee, $fee_application );

		// Create a ticket for the event with the specified price.
		$ticket = $this->create_tc_ticket( $event_id, $ticket_price->get() );

		// Associate the fee with the event.
		$this->create_fee_relationship( $fee, $ticket, get_post_type( $ticket ) );

		$this->make_controller()->register();
		$cart = tribe( Cart::class );
		$cart->add_item( $ticket, $quantity );

		// Assert the total value matches the expected total.
		$this->assertEquals(
			$quantity * $expected_total->get(),
			$cart->get_cart_total(),
			'Cart total should correctly include ticket price and fee.'
		);
	}

	/**
	 * @test
	 * @dataProvider cart_totals_data_provider
	 */
	public function it_should_display_fee_section(
		Float_Value $ticket_price,
		Float_Value $fee_raw_amount,
		string $fee_application
	) {
		$event_id = self::factory()->post->create(
			[
				'post_title' => 'The Event',
			]
		);

		// Create a fee with the specified raw amount.
		$fee = $this->create_fee( [ 'raw_amount' => $fee_raw_amount ] );
		$this->set_fee_application( $fee, $fee_application );

		// Create a ticket for the event with the specified price.
		$ticket = $this->create_tc_ticket( $event_id, $ticket_price->get() );

		// Associate the fee with the event.
		$this->create_fee_relationship( $fee, $ticket, get_post_type( $ticket ) );

		$this->make_controller()->register();
		$cart = tribe( Cart::class );
		$cart->add_item( $ticket, 1 );

		$this->set_fn_return( 'wp_create_nonce', '0987654321' );
		// Assert the total value matches the expected total.
		$this->assertMatchesHtmlSnapshot( preg_replace( '#<link rel=(.*)/>#', '', str_replace( [ $event_id, $ticket ], [ '{POST_ID}', '{TICKET_ID}' ], tribe( Checkout_Shortcode::class )->get_html() ) ) );
	}

	/**
	 * Data provider for testing order totals with various inputs.
	 *
	 * @return \Generator
	 */
	public function cart_totals_data_provider(): \Generator {
		yield 'Ticket $10, Fee $5, Application All' => [
			'ticket_price'      => Float_Value::from_number( 10 ),
			'fee_raw_amount'    => Float_Value::from_number( 5 ),
			'fee_application'   => 'all',
			'expected_total'    => Float_Value::from_number( 15 ),
			'quantity'          => 3,
		];

		yield 'Ticket $20, Fee $3, Application All' => [
			'ticket_price'      => Float_Value::from_number( 20 ),
			'fee_raw_amount'    => Float_Value::from_number( 3 ),
			'fee_application'   => 'all',
			'expected_total'    => Float_Value::from_number( 23 ),
			'quantity'          => 2,
		];

		yield 'Ticket $15, Fee $2, Application Per' => [
			'ticket_price'      => Float_Value::from_number( 15 ),
			'fee_raw_amount'    => Float_Value::from_number( 2 ),
			'fee_application'   => 'per',
			'expected_total'    => Float_Value::from_number( 17 ),
			'quantity'          => 3,
		];

		yield 'Ticket $50, Fee $10, Application Per' => [
			'ticket_price'      => Float_Value::from_number( 50 ),
			'fee_raw_amount'    => Float_Value::from_number( 10 ),
			'fee_application'   => 'per',
			'expected_total'    => Float_Value::from_number( 60 ),
			'quantity'          => 2,
		];
	}

	/**
	 * @test
	 */
	public function ticket_without_fees_in_checkout() {
		$post_id         = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $post_id, 15 );
		// Create the fee and set the application.
		$fee = $this->create_fee( [ 'display_name' => __METHOD__ ] );
		$this->set_fee_application( $fee, 'per' );

		$this->make_controller()->register();

		// Step 2: Create a cart with the tickets.
		$quantity = 10;
		$cart     = $this->get_cart_with_tickets( $ticket_id, $quantity );

		// Step 3: Get the cart total and subtotal.
		$cart_total    = $cart->get_cart_total();
		$cart_subtotal = $cart->get_cart_subtotal();

		// Clear the cart for the next test.
		$cart->clear_cart();

		// Step 4: Verify that the cart total and subtotal are correct.
		$this->assertEquals( 15 * $quantity, $cart_subtotal );
		$this->assertEquals( 15 * $quantity, $cart_total );
	}

	/**
	 * @test
	 */
	public function ticket_with_fees_in_checkout() {
		$post_id         = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $post_id, 15 );

		// Create the fee and set the application.
		$fee = $this->create_fee( [ 'display_name' => __METHOD__ ] );
		$this->set_fee_application( $fee, 'all' );

		$fee2 = $this->create_fee_for_ticket( $ticket_id, [ 'raw_amount' => 2.22 ] );

		$this->make_controller()->register();

		// Step 2: Create a cart with the tickets.
		$quantity = 2;
		$cart     = $this->get_cart_with_tickets( $ticket_id, $quantity );

		// Make sure we have the number of items we expect in the cart.
		$items = $cart->get_items_in_cart();
		$count = 0;
		foreach ( $items as $item ) {
			$count += $item['quantity'];
		}

		$this->assertEquals( $quantity, $count );

		// Step 3: Get the cart total and subtotal.
		$cart_total    = $cart->get_cart_total();
		$cart_subtotal = $cart->get_cart_subtotal();

		// Step 4: Verify that the cart total and subtotal are correct.
		$this->assertEquals( 15 * $quantity, $cart_subtotal );
		$this->assertEquals( ( 15 + 5 + 2.22 ) * $quantity, $cart_total );
	}

	/**
	 * Get a cart with the tickets.
	 *
	 * @param ?int $quantity The quantity of tickets to add to the cart.
	 *
	 * @return Commerce_Cart
	 */
	protected function get_cart_with_tickets( int $ticket_id, ?int $quantity = null ) {
		$cart     = tribe( Commerce_Cart::class );
		$quantity = $quantity ?? 1;
		$cart->add_ticket( $ticket_id, $quantity );

		return $cart;
	}

	/**
	 * Data provider for testing different types of fees, with support for multiple fees.
	 *
	 * @return Generator
	 */
	public function fee_type_provider(): Generator {
		// Single flat fee
		yield 'Single Flat Fee' => [
			'modifiers'                 => [
				[
					'sub_type'     => 'flat',
					'raw_amount'   => 5,
					'slug'         => 'flat_fee_1',
					'display_name' => 'Flat Fee 1',
				],
			],
			'expected_total_adjustment' => 5.00,
		];

		// Multiple flat fees
		yield 'Multiple Flat Fees (10 Fees)' => [
			'modifiers'                 => array_fill(
				0,
				10,
				[
					'sub_type'     => 'flat',
					'raw_amount'   => 10,
					'slug'         => 'flat_fee',
					'display_name' => 'Flat Fee',
				],
			),
			'expected_total_adjustment' => 10 * 10.00, // 10 flat fees of $10 each
		];

		// Multiple percent fees
		yield 'Multiple Percent Fees (10 Fees)' => [
			'modifiers'                 => array_fill(
				0,
				10,
				[
					'sub_type'     => 'percent',
					'raw_amount'   => 10,
					'slug'         => 'percent_fee',
					'display_name' => 'Percent Fee',
				],
			),
			'expected_total_adjustment' => 230 * ( 10 * 0.10 ), // 10% of $230 applied 10 times
		];

		// Combination of flat and percent fees
		yield 'Flat and Percent Fees (Mix)' => [
			'modifiers'                 => [
				[
					'sub_type'     => 'flat',
					'raw_amount'   => 5,
					'slug'         => 'flat_fee_1',
					'display_name' => 'Flat Fee 1',
				],
				[
					'sub_type'     => 'percent',
					'raw_amount'   => 10,
					'slug'         => 'percent_fee',
					'display_name' => 'Percent Fee',
				],
			],
			'expected_total_adjustment' => 5.00 + ( 230 * 0.10 ), // $5 flat + 10% of $230
		];

		// Excessively large fee
		yield 'Excessively Large Fee' => [
			'modifiers'                 => [
				[
					'sub_type'     => 'flat',
					'raw_amount'   => 1000000,
					'slug'         => 'large_fee',
					'display_name' => 'Large Fee',
				],
			],
			'expected_total_adjustment' => 1000000.00, // Add $1,000,000 to total
		];

		// 100% percent fee
		yield 'Max Percent Fee (100%)' => [
			'modifiers'                 => [
				[
					'sub_type'     => 'percent',
					'raw_amount'   => 100,
					'slug'         => 'max_percent_fee',
					'display_name' => 'Max Percent Fee',
				],
			],
			'expected_total_adjustment' => 230, // 100% of $230
		];

		// Multiple percent fees with varying percentages
		yield 'Multiple Percent Fees (5%, 10%, 15%)' => [
			'modifiers'                 => [
				[
					'sub_type'     => 'percent',
					'raw_amount'   => 5,
					'slug'         => 'percent_fee_5',
					'display_name' => '5% Percent Fee',
				],
				[
					'sub_type'     => 'percent',
					'raw_amount'   => 10,
					'slug'         => 'percent_fee_10',
					'display_name' => '10% Percent Fee',
				],
				[
					'sub_type'     => 'percent',
					'raw_amount'   => 15,
					'slug'         => 'percent_fee_15',
					'display_name' => '15% Percent Fee',
				],
			],
			'expected_total_adjustment' => ( 230 * 0.05 ) + ( 230 * 0.10 ) + ( 230 * 0.15 ), // Sum of all percentage adjustments
		];

		// Multiple 100% percent fees
		yield 'Multiple 100% Percent Fees' => [
			'modifiers'                 => array_fill(
				0,
				3,
				[
					'sub_type'     => 'percent',
					'raw_amount'   => 100,
					'slug'         => 'max_percent_fee',
					'display_name' => 'Max Percent Fee',
				],
			),
			'expected_total_adjustment' => 230 * 3, // Applying 100% fee 3 times (triple the order total)
		];

		// Maximum percent and flat fee combination
		yield 'Max Percent Fee with Flat Fee' => [
			'modifiers'                 => [
				[
					'sub_type'     => 'percent',
					'raw_amount'   => 100,
					'slug'         => 'max_percent_fee',
					'display_name' => 'Max Percent Fee',
				],
				[
					'sub_type'     => 'flat',
					'raw_amount'   => 50,
					'slug'         => 'flat_fee',
					'display_name' => 'Flat Fee',
				],
			],
			'expected_total_adjustment' => 230 + 50, // 100% of $230 plus $50 flat fee
		];
	}

	/**
	 * Test applying various types of fee modifiers during checkout using data provider.
	 *
	 * @dataProvider fee_type_provider
	 *
	 * @param array $modifiers                 The modifiers data for the test.
	 * @param float $expected_total_adjustment The expected total adjustment for the modifiers.
	 */
	public function test_apply_fee_modifiers_during_checkout( array $modifiers, float $expected_total_adjustment ) {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 23 );
		// Step 1: Insert the modifiers.
		$modifier_ids = [];
		foreach ( $modifiers as $modifier_data ) {
			$modifier_ids[] = $this->create_fee_for_ticket( $ticket_id, $modifier_data );
		}

		$this->make_controller()->register();
		$this->make_controller( PayPalFees::class )->register();

		// Step 2: Create the order.
		$order = $this->create_order( [ $ticket_id => 10 ] );

		// Step 3: Calculate the expected total.
		$expected_total = $order->subtotal->get_decimal() + $expected_total_adjustment;

		// Step 4: Assert that the total matches the expected total.
		$this->assertEquals( $expected_total, $order->total_value->get_decimal() );
	}

	/**
	 * @before
	 * @after
	 */
	public function reset_fees() {
		$this->make_controller( PayPalFees::class )->reset_fees_and_subtotal();
	}
}
