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
use TEC\Tickets\Commerce\Order_Modifiers\API\Fees as ApiFees;

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

		if ( 'per' === $fee_application ) {
			// Associate the fee with the event.
			$this->create_fee_relationship( $fee, $ticket, get_post_type( $ticket ) );
		}

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
	 */
	public function it_should_append_fees_correctly() {
		$post = static::factory()->post->create();
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );
		$ticket_id_3 = $this->create_tc_ticket( $post, 30 );
		$ticket_id_4 = $this->create_tc_ticket( $post, 40 );
		$ticket_id_5 = $this->create_tc_ticket( $post, 50 );

		$fee_for_all_1 = $this->create_fee_for_all( [ 'raw_amount' => 10, 'sub_type' => 'percent' ] );
		$fee_for_all_2 = $this->create_fee_for_all( [ 'raw_amount' => 3, 'sub_type' => 'flat' ] );

		$fee_per_ticket_1 = $this->create_fee_for_ticket( $ticket_id_1, [ 'raw_amount' => 2, 'sub_type' => 'percent' ] );
		$this->add_fee_to_ticket( $fee_per_ticket_1, $ticket_id_3 );
		$this->add_fee_to_ticket( $fee_per_ticket_1, $ticket_id_5 );

		$fee_per_ticket_2 = $this->create_fee_for_ticket( $ticket_id_2, [ 'raw_amount' => 2.5, 'sub_type' => 'flat' ] );
		$this->add_fee_to_ticket( $fee_per_ticket_2, $ticket_id_3 );

		$fee_per_ticket_3 = $this->create_fee_for_ticket( $ticket_id_3, [ 'raw_amount' => 5, 'sub_type' => 'percent' ] );

		$ticket_1_fees = $this->make_controller( ApiFees::class )->get_fees_for_ticket( $ticket_id_1 );
		$ticket_2_fees = $this->make_controller( ApiFees::class )->get_fees_for_ticket( $ticket_id_2 );
		$ticket_3_fees = $this->make_controller( ApiFees::class )->get_fees_for_ticket( $ticket_id_3 );
		$ticket_4_fees = $this->make_controller( ApiFees::class )->get_fees_for_ticket( $ticket_id_4 );
		$ticket_5_fees = $this->make_controller( ApiFees::class )->get_fees_for_ticket( $ticket_id_5 );

		$available_fees = [ $fee_per_ticket_1, $fee_per_ticket_2, $fee_per_ticket_3 ];
		$automatic_fees = [ $fee_for_all_1, $fee_for_all_2 ];

		$this->assertCount( 1, $ticket_1_fees['selected_fees'] );
		$this->assertCount( 2, $ticket_1_fees['automatic_fees'] );
		$this->assertCount( 3, $ticket_1_fees['available_fees'] );

		$this->assertEquals( $available_fees, array_keys( $ticket_1_fees['available_fees'] ) );
		$this->assertEquals( $automatic_fees, array_keys( $ticket_1_fees['automatic_fees'] ) );
		$this->assertEquals( [ $fee_per_ticket_1 ], $ticket_1_fees['selected_fees'] );

		$this->assertCount( 1, $ticket_2_fees['selected_fees'] );
		$this->assertCount( 2, $ticket_2_fees['automatic_fees'] );
		$this->assertCount( 3, $ticket_2_fees['available_fees'] );

		$this->assertEquals( $available_fees, array_keys( $ticket_2_fees['available_fees'] ) );
		$this->assertEquals( $automatic_fees, array_keys( $ticket_2_fees['automatic_fees'] ) );
		$this->assertEquals( [ $fee_per_ticket_2 ], $ticket_2_fees['selected_fees'] );

		$this->assertCount( 3, $ticket_3_fees['selected_fees'] );
		$this->assertCount( 2, $ticket_3_fees['automatic_fees'] );
		$this->assertCount( 3, $ticket_3_fees['available_fees'] );

		$this->assertEquals( $available_fees, array_keys( $ticket_3_fees['available_fees'] ) );
		$this->assertEquals( $automatic_fees, array_keys( $ticket_3_fees['automatic_fees'] ) );
		$this->assertEquals( [ $fee_per_ticket_1, $fee_per_ticket_2, $fee_per_ticket_3 ], $ticket_3_fees['selected_fees'] );

		$this->assertCount( 0, $ticket_4_fees['selected_fees'] );
		$this->assertCount( 2, $ticket_4_fees['automatic_fees'] );
		$this->assertCount( 3, $ticket_4_fees['available_fees'] );

		$this->assertEquals( $available_fees, array_keys( $ticket_4_fees['available_fees'] ) );
		$this->assertEquals( $automatic_fees, array_keys( $ticket_4_fees['automatic_fees'] ) );
		$this->assertEquals( [], $ticket_4_fees['selected_fees'] );

		$this->assertCount( 1, $ticket_5_fees['selected_fees'] );
		$this->assertCount( 2, $ticket_5_fees['automatic_fees'] );
		$this->assertCount( 3, $ticket_5_fees['available_fees'] );

		$this->assertEquals( $available_fees, array_keys( $ticket_5_fees['available_fees'] ) );
		$this->assertEquals( $automatic_fees, array_keys( $ticket_5_fees['automatic_fees'] ) );
		$this->assertEquals( [ $fee_per_ticket_1 ], $ticket_5_fees['selected_fees'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_calculate_fees_for_free_tickets() {
		$post = static::factory()->post->create(
			[ 'post_title' => 'The Event' ],
		);
		$ticket_id_1 = $this->create_tc_ticket( $post, 0 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );
		$ticket_id_3 = $this->create_tc_ticket( $post, 30 );
		$ticket_id_4 = $this->create_tc_ticket( $post, 0 );

		$fee_for_all_1 = $this->create_fee_for_all( [ 'raw_amount' => 10, 'sub_type' => 'percent' ] );
		$fee_for_all_2 = $this->create_fee_for_all( [ 'raw_amount' => 3, 'sub_type' => 'flat' ] );

		$fee_per_ticket_1 = $this->create_fee_for_ticket( $ticket_id_1, [ 'raw_amount' => 2, 'sub_type' => 'percent' ] );
		$this->add_fee_to_ticket( $fee_per_ticket_1, $ticket_id_3 );

		$fee_per_ticket_2 = $this->create_fee_for_ticket( $ticket_id_2, [ 'raw_amount' => 2.5, 'sub_type' => 'flat' ] );
		$this->add_fee_to_ticket( $fee_per_ticket_2, $ticket_id_3 );

		$fee_per_ticket_3 = $this->create_fee_for_ticket( $ticket_id_3, [ 'raw_amount' => 5, 'sub_type' => 'percent' ] );

		// Math time!
		// Ticket 1: 0
		// Ticket 2: 20 + 10% + 3 + 2.5 = 20 + 2 + 3 + 2.5 = 27.50 // 9 fees
		// Ticket 3: 30 + 10% + 3 + 2% + 2.5 + 5% = 30 + 3 + 3 + 0.6 + 2.5 + 1.5 = 40.60 // 20 fees
		// Ticket 4: 0
		// Calculated each ticket's price with fees applied.
		// Now lets create an cart with different quantities of each ticket.

		$this->make_controller()->register();

		$cart = tribe( Commerce_Cart::class );

		$cart->add_ticket( $ticket_id_1, 2 );
		$cart->add_ticket( $ticket_id_2, 3 );
		$cart->add_ticket( $ticket_id_3, 4 );
		$cart->add_ticket( $ticket_id_4, 5 );

		$cart_total    = $cart->get_cart_total();
		$cart_subtotal = $cart->get_cart_subtotal();

		$this->set_fn_return( 'wp_create_nonce', '1029384756' );
		$this->assertMatchesHtmlSnapshot(
			preg_replace(
				'#<link rel=(.*)/>#',
				'',
				str_replace(
					[ $post, $ticket_id_1, $ticket_id_2, $ticket_id_3, $ticket_id_4 ],
					[ '{POST_ID}', '{TICKET_ID_1}', '{TICKET_ID_2}', '{TICKET_ID_3}', '{TICKET_ID_4}' ],
					tribe( Checkout_Shortcode::class )->get_html()
				)
			)
		);

		$cart->clear_cart();

		$this->assertEquals(
			2 * 0 + 3 * 20 + 4 * 30 + 5 * 0, // 180
			$cart_subtotal,
			'Cart subtotal should correctly include only ticket price.'
		);

		// Assert the total value matches the expected total.
		$this->assertEquals(
			(float) number_format(2 * 0 + 3 * 27.5 + 4 * 40.6 + 5 * 0, 2, '.', '' ), // 244.9
			$cart_total,
			'Cart total should correctly include ticket price and fee.'
		);

		$order = $this->create_order( [
			$ticket_id_1 => 2,
			$ticket_id_2 => 3,
			$ticket_id_3 => 4,
			$ticket_id_4 => 5,
		] );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertEquals(
			$cart_total,
			$refreshed_order->total_value->get_decimal(),
			'Order total should correctly include ticket price and fee.'
		);

		$this->assertEquals(
			$cart_subtotal,
			$refreshed_order->subtotal->get_decimal(),
			'Order subtotal should correctly include ticket price and fee.'
		);
	}

	/**
	 * @test
	 */
	public function it_should_calculate_fees_and_store_them_correctly_simple_math() {
		$post = static::factory()->post->create(
			[ 'post_title' => 'The Event' ],
		);
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );
		$ticket_id_3 = $this->create_tc_ticket( $post, 30 );
		$ticket_id_4 = $this->create_tc_ticket( $post, 40 );
		$ticket_id_5 = $this->create_tc_ticket( $post, 50 );

		$fee_for_all_1 = $this->create_fee_for_all( [ 'raw_amount' => 10, 'sub_type' => 'percent' ] );
		$fee_for_all_2 = $this->create_fee_for_all( [ 'raw_amount' => 3, 'sub_type' => 'flat' ] );

		$fee_per_ticket_1 = $this->create_fee_for_ticket( $ticket_id_1, [ 'raw_amount' => 2, 'sub_type' => 'percent' ] );
		$this->add_fee_to_ticket( $fee_per_ticket_1, $ticket_id_3 );
		$this->add_fee_to_ticket( $fee_per_ticket_1, $ticket_id_5 );

		$fee_per_ticket_2 = $this->create_fee_for_ticket( $ticket_id_2, [ 'raw_amount' => 2.5, 'sub_type' => 'flat' ] );
		$this->add_fee_to_ticket( $fee_per_ticket_2, $ticket_id_3 );

		$fee_per_ticket_3 = $this->create_fee_for_ticket( $ticket_id_3, [ 'raw_amount' => 5, 'sub_type' => 'percent' ] );

		// Math time!
		// Ticket 1: 10 + 10% + 3 + 2% = 10 + 1 + 3 + 0.2 = 14.20 // 6 fees
		// Ticket 2: 20 + 10% + 3 + 2.5 = 20 + 2 + 3 + 2.5 = 27.50 // 9 fees
		// Ticket 3: 30 + 10% + 3 + 2% + 2.5 + 5% = 30 + 3 + 3 + 0.6 + 2.5 + 1.5 = 40.60 // 20 fees
		// Ticket 4: 40 + 10% + 3 = 40 + 4 + 3 = 47 // 10 fees
		// Ticket 5: 50 + 10% + 3 + 2% = 50 + 5 + 3 + 1 = 59 // 18 fees
		// Calculated each ticket's price with fees applied.
		// Now lets create an cart with different quantities of each ticket.

		$this->make_controller()->register();

		$cart = tribe( Commerce_Cart::class );

		$cart->add_ticket( $ticket_id_1, 2 );
		$cart->add_ticket( $ticket_id_2, 3 );
		$cart->add_ticket( $ticket_id_3, 4 );
		$cart->add_ticket( $ticket_id_4, 5 );
		$cart->add_ticket( $ticket_id_5, 6 );

		$cart_total    = $cart->get_cart_total();
		$cart_subtotal = $cart->get_cart_subtotal();

		$this->set_fn_return( 'wp_create_nonce', '1029384756' );
		$this->assertMatchesHtmlSnapshot(
			preg_replace(
				'#<link rel=(.*)/>#',
				'',
				str_replace(
					[ $post, $ticket_id_1, $ticket_id_2, $ticket_id_3, $ticket_id_4, $ticket_id_5 ],
					[ '{POST_ID}', '{TICKET_ID_1}', '{TICKET_ID_2}', '{TICKET_ID_3}', '{TICKET_ID_4}', '{TICKET_ID_5}' ],
					tribe( Checkout_Shortcode::class )->get_html()
				)
			)
		);

		$cart->clear_cart();

		$this->assertEquals(
			2 * 10 + 3 * 20 + 4 * 30 + 5 * 40 + 6 * 50, // 700
			$cart_subtotal,
			'Cart subtotal should correctly include only ticket price.'
		);

		// Assert the total value matches the expected total.
		$this->assertEquals(
			(float) number_format(2 * 14.2 + 3 * 27.5 + 4 * 40.6 + 5 * 47 + 6 * 59, 2, '.', '' ), // 862.30
			$cart_total,
			'Cart total should correctly include ticket price and fee.'
		);

		$order = $this->create_order( [
			$ticket_id_1 => 2,
			$ticket_id_2 => 3,
			$ticket_id_3 => 4,
			$ticket_id_4 => 5,
			$ticket_id_5 => 6,
		] );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertEquals(
			$cart_total,
			$refreshed_order->total_value->get_decimal(),
			'Order total should correctly include ticket price and fee.'
		);

		$this->assertEquals(
			$cart_subtotal,
			$refreshed_order->subtotal->get_decimal(),
			'Order subtotal should correctly include ticket price and fee.'
		);
	}

	/**
	 * @test
	 */
	public function it_should_calculate_fees_and_store_them_correctly_complex_math() {
		$post = static::factory()->post->create(
			[ 'post_title' => 'The Event' ],
		);
		$ticket_id_1 = $this->create_tc_ticket( $post, 11.28 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 22.56 );
		$ticket_id_3 = $this->create_tc_ticket( $post, 33.84 );
		$ticket_id_4 = $this->create_tc_ticket( $post, 45.12 );
		$ticket_id_5 = $this->create_tc_ticket( $post, 56.40 );

		$fee_for_all_1 = $this->create_fee_for_all( [ 'raw_amount' => 14.78, 'sub_type' => 'percent' ] );
		$fee_for_all_2 = $this->create_fee_for_all( [ 'raw_amount' => 3.67, 'sub_type' => 'flat' ] );

		$fee_per_ticket_1 = $this->create_fee_for_ticket( $ticket_id_1, [ 'raw_amount' => 1.23, 'sub_type' => 'percent' ] );
		$this->add_fee_to_ticket( $fee_per_ticket_1, $ticket_id_3 );
		$this->add_fee_to_ticket( $fee_per_ticket_1, $ticket_id_5 );

		$fee_per_ticket_2 = $this->create_fee_for_ticket( $ticket_id_2, [ 'raw_amount' => 2.34, 'sub_type' => 'flat' ] );
		$this->add_fee_to_ticket( $fee_per_ticket_2, $ticket_id_3 );

		$fee_per_ticket_3 = $this->create_fee_for_ticket( $ticket_id_3, [ 'raw_amount' => 3.45, 'sub_type' => 'percent' ] );

		// Math time!
		// Ticket 1: $11.28 + 14.78% + 3.67 + 1.23% = $11.28 + $1.67 + $3.67 + $0.14 = $16.76
		// Ticket 2: $22.56 + 14.78% + 3.67 + 2.34 = $22.56 + $3.33 + $3.67 + $2.34 = $31.90
		// Ticket 3: $33.84 + 14.78% + 3.67 + 1.23% + 2.34 + 3.45% = $33.84 + $5.00 + $3.67 + $0.42 + $2.34 + $1.17 = $46.44
		// Ticket 4: $45.12 + 14.78% + 3.67 = $45.12 + $6.67 + $3.67 = $55.46
		// Ticket 5: $56.40 + 14.78% + 3.67 + 1.23% = $56.40 + $8.34 + $3.67 + $0.69 = $69.10
		// Calculated each ticket's price with fees applied.
		// Now lets create an cart with different quantities of each ticket.

		$this->make_controller()->register();

		$cart = tribe( Commerce_Cart::class );

		$cart->add_ticket( $ticket_id_1, 2 );
		$cart->add_ticket( $ticket_id_2, 3 );
		$cart->add_ticket( $ticket_id_3, 4 );
		$cart->add_ticket( $ticket_id_4, 5 );
		$cart->add_ticket( $ticket_id_5, 6 );

		$cart_total    = $cart->get_cart_total();
		$cart_subtotal = $cart->get_cart_subtotal();

		$this->set_fn_return( 'wp_create_nonce', '1029384756' );
		$this->assertMatchesHtmlSnapshot(
			preg_replace(
				'#<link rel=(.*)/>#',
				'',
				str_replace(
					[ $post, $ticket_id_1, $ticket_id_2, $ticket_id_3, $ticket_id_4, $ticket_id_5 ],
					[ '{POST_ID}', '{TICKET_ID_1}', '{TICKET_ID_2}', '{TICKET_ID_3}', '{TICKET_ID_4}', '{TICKET_ID_5}' ],
					tribe( Checkout_Shortcode::class )->get_html()
				)
			)
		);

		$cart->clear_cart();

		$this->assertEquals(
			2 * 11.28 + 3 * 22.56 + 4 * 33.84 + 5 * 45.12 + 6 * 56.40, // 789.60
			$cart_subtotal,
			'Cart subtotal should correctly include ticket price.'
		);

		// Assert the total value matches the expected total.
		$this->assertEquals(
			(float) number_format(2 * 16.76 + 3 * 31.90 + 4 * 46.44 + 5 * 55.46 + 6 * 69.10, 2, '.', '' ), // 1006.88
			$cart_total,
			'Cart total should correctly include ticket price and fee.'
		);

		$order = $this->create_order( [
			$ticket_id_1 => 2,
			$ticket_id_2 => 3,
			$ticket_id_3 => 4,
			$ticket_id_4 => 5,
			$ticket_id_5 => 6,
		] );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertEquals(
			$cart_total,
			$refreshed_order->total_value->get_decimal(),
			'Order total should correctly include ticket price and fee.'
		);

		$this->assertEquals(
			$cart_subtotal,
			$refreshed_order->subtotal->get_decimal(),
			'Order subtotal should correctly include ticket price and fee.'
		);
	}

	/**
	 * @test
	 * @dataProvider cart_totals_data_provider
	 */
	public function it_should_display_fee_section(
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

		if ( 'all' !== $fee_application ) {
			// Associate the fee with the event.
			$this->create_fee_relationship( $fee, $ticket, get_post_type( $ticket ) );
		}

		$this->make_controller()->register();
		$cart = tribe( Cart::class );
		$cart->add_item( $ticket, $quantity );

		$this->assertEquals( $quantity * $expected_total->get(), $cart->get_cart_total() );
		$this->set_fn_return( 'wp_create_nonce', '1029384756' );
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
			'expected_total_adjustment' => 50.00,
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
			'expected_total_adjustment' => 100 * 10.00, // 10 flat fees of $10 each for 10 tickets
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
			'expected_total_adjustment' => 50.00 + ( 230 * 0.10 ), // $5 flat + 10% of $230
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
			'expected_total_adjustment' => 10000000.00, // Add $1,000,000 to total
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
			'expected_total_adjustment' => 230 + 500, // 100% of $230 plus $50 flat fee
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

		// Step 2: Create the order.
		$order = $this->create_order( [ $ticket_id => 10 ] );

		// Step 3: Calculate the expected total.
		// $expected_total = $order->subtotal->get_decimal();
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
