<?php

namespace TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\PayPal;

use PHPUnit\Framework\Assert;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Commerce\Cart as Commerce_Cart;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway as PayPalGateway;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Fees as BaseFees;
use TEC\Tickets\Commerce\Values\Float_Value;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Fee_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\PayPal_REST_Override;
use Tribe\Tickets\Test\Traits\Reservations_Maker;
use Tribe\Tickets\Test\Traits\With_No_Object_Storage;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use WP_Post;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;

class PayPal_Fees_Test extends Controller_Test_Case {

	use Attendee_Maker;
	use Fee_Creator;
	use Order_Maker;
	use PayPal_REST_Override;
	use Reservations_Maker;
	use Series_Pass_Factory;
	use SnapshotAssertions;
	use Ticket_Maker;
	use With_No_Object_Storage;
	use With_Tickets_Commerce;

	protected string $controller_class = Fees::class;

	protected string $gateway_class = PayPalGateway::class;

	/**
	 * @after
	 */
	public function breakdown() {
		$this->test_services->get( $this->controller_class )->reset_fees_and_subtotal();
	}

	/**
	 * @test
	 */
	public function it_should_not_store_objects() {
		$post = static::factory()->post->create();
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );
		$ticket_id_3 = $this->create_tc_ticket( $post, 30 );

		$fee_for_all_1 = $this->create_fee_for_all( [ 'raw_amount' => 10, 'sub_type' => 'percent' ] );

		$fee_per_ticket_1 = $this->create_fee_for_ticket( $ticket_id_1, [ 'raw_amount' => 2, 'sub_type' => 'percent' ] );
		$this->add_fee_to_ticket( $fee_per_ticket_1, $ticket_id_3 );

		$fee_per_ticket_2 = $this->create_fee_for_ticket( $ticket_id_2, [ 'raw_amount' => 2.5, 'sub_type' => 'flat' ] );
		$this->add_fee_to_ticket( $fee_per_ticket_2, $ticket_id_3 );

		$this->make_controller()->register();

		$overrides['gateway'] = tribe( $this->gateway_class );

		$order = $this->create_order( [
			$ticket_id_1 => 2,
			$ticket_id_2 => 3,
			$ticket_id_3 => 4,
		], $overrides );

		$this->assert_no_object_stored( get_post_meta( $order->ID ) );
	}

	/**
	 * @test
	 */
	public function it_should_calculate_fees_and_store_them_correctly_with_series() {
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);

		$series_pass_id = $this->create_tc_series_pass( $series_id, 60 )->ID;

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
		$this->add_fee_to_ticket( $fee_per_ticket_2, $series_pass_id );

		$fee_per_ticket_3 = $this->create_fee_for_ticket( $ticket_id_3, [ 'raw_amount' => 5, 'sub_type' => 'percent' ] );

		// Math time!
		// Ticket 1: 10 + 10% + 3 + 2% = 10 + 1 + 3 + 0.2 = 14.20 // 6 fees
		// Ticket 2: 20 + 10% + 3 + 2.5 = 20 + 2 + 3 + 2.5 = 27.50 // 9 fees
		// Ticket 3: 30 + 10% + 3 + 2% + 2.5 + 5% = 30 + 3 + 3 + 0.6 + 2.5 + 1.5 = 40.60 // 20 fees
		// Ticket 4: 40 + 10% + 3 = 40 + 4 + 3 = 47 // 10 fees
		// Ticket 5: 50 + 10% + 3 + 2% = 50 + 5 + 3 + 1 = 59 // 18 fees
		// Series  : 60 + 10% + 3 + 2.5 = 60 + 6 + 3 + 2.5 = 71.50 // 21 fees
		// Calculated each ticket's price with fees applied.
		// Now lets create an cart with different quantities of each ticket.

		$this->test_services->register( BaseFees::class );
		$this->make_controller()->register();

		$cart = tribe( Commerce_Cart::class );

		$cart->add_ticket( $ticket_id_1, 2 );
		$cart->add_ticket( $ticket_id_2, 3 );
		$cart->add_ticket( $ticket_id_3, 4 );
		$cart->add_ticket( $ticket_id_4, 5 );
		$cart->add_ticket( $ticket_id_5, 6 );
		$cart->add_ticket( $series_pass_id, 7 );

		$cart_total    = $cart->get_cart_total();
		$cart_subtotal = $cart->get_cart_subtotal();

		$cart->clear_cart();

		$this->assertEquals(
			2 * 10 + 3 * 20 + 4 * 30 + 5 * 40 + 6 * 50 + 7 * 60, // 1120
			$cart_subtotal,
			'Cart subtotal should correctly include only ticket price.'
		);

		// Assert the total value matches the expected total.
		$this->assertEquals(
			(float) number_format(2 * 14.2 + 3 * 27.5 + 4 * 40.6 + 5 * 47 + 6 * 59 + 7 * 71.50, 2, '.', '' ), // 1362.80
			$cart_total,
			'Cart total should correctly include ticket price and fee.'
		);

		$overrides['gateway'] = tribe( $this->gateway_class );

		$order = $this->create_order( [
			$ticket_id_1    => 2,
			$ticket_id_2    => 3,
			$ticket_id_3    => 4,
			$ticket_id_4    => 5,
			$ticket_id_5    => 6,
			$series_pass_id => 7,
		], $overrides );

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

		// Math time!
		// Ticket 1: 10 + 10% + 3 + 2% = 10 + 1 + 3 + 0.2 = 14.20 // 6 fees
		// Ticket 2: 20 + 10% + 3 + 2.5 = 20 + 2 + 3 + 2.5 = 27.50 // 9 fees
		// Ticket 3: 30 + 10% + 3 + 2% + 2.5 + 5% = 30 + 3 + 3 + 0.6 + 2.5 + 1.5 = 40.60 // 20 fees
		// Ticket 4: 40 + 10% + 3 = 40 + 4 + 3 = 47 // 10 fees
		// Ticket 5: 50 + 10% + 3 + 2% = 50 + 5 + 3 + 1 = 59 // 18 fees
		// Calculated each ticket's price with fees applied.
		// Now lets create an cart with different quantities of each ticket.

		$this->test_services->register( BaseFees::class );
		$this->make_controller()->register();

		$cart = tribe( Commerce_Cart::class );

		$cart->add_ticket( $ticket_id_1, 2 );
		$cart->add_ticket( $ticket_id_2, 3 );
		$cart->add_ticket( $ticket_id_3, 4 );
		$cart->add_ticket( $ticket_id_4, 5 );
		$cart->add_ticket( $ticket_id_5, 6 );

		$cart_total    = $cart->get_cart_total();
		$cart_subtotal = $cart->get_cart_subtotal();

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

		$overrides['gateway'] = tribe( $this->gateway_class );

		$order = $this->create_order( [
			$ticket_id_1 => 2,
			$ticket_id_2 => 3,
			$ticket_id_3 => 4,
			$ticket_id_4 => 5,
			$ticket_id_5 => 6,
		], $overrides );

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
		$post = static::factory()->post->create();
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

		$this->test_services->register( BaseFees::class );
		$this->make_controller()->register();

		$cart = tribe( Commerce_Cart::class );

		$cart->add_ticket( $ticket_id_1, 2 );
		$cart->add_ticket( $ticket_id_2, 3 );
		$cart->add_ticket( $ticket_id_3, 4 );
		$cart->add_ticket( $ticket_id_4, 5 );
		$cart->add_ticket( $ticket_id_5, 6 );

		$cart_total    = $cart->get_cart_total();
		$cart_subtotal = $cart->get_cart_subtotal();

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

		$overrides['gateway'] = tribe( $this->gateway_class );
		$order = $this->create_order( [
			$ticket_id_1 => 2,
			$ticket_id_2 => 3,
			$ticket_id_3 => 4,
			$ticket_id_4 => 5,
			$ticket_id_5 => 6,
		], $overrides );

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
	 * @dataProvider order_totals_data_provider
	 * Ensures the order totals are calculated correctly for tickets and fees using the Stripe gateway.
	 */
	public function it_calculates_order_totals_with_paypal_gateway(
		Float_Value $ticket_price,
		Float_Value $fee_raw_amount,
		string $fee_application,
		Float_Value $expected_total,
		Float_Value $expected_subtotal
	): void {
		$this->make_controller()->register();
		// Create an event post.
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
			// Associate the fee with the ticket.
			$this->create_fee_relationship( $fee, $ticket, get_post_type( $ticket ) );
		}

		// Set up overrides with the specified gateway.
		$overrides['gateway'] = tribe( $this->gateway_class );

		// Create an order with 1 ticket.
		$order = $this->create_order( [ $ticket => 1 ], $overrides );

		// Assertions: Ensure the order totals are calculated correctly.
		$this->assertInstanceOf( WP_Post::class, $order, 'Order should be a valid WP_Post object.' );
		$this->assertEquals( 'tec_tc_order', $order->post_type, 'Order post type should be tec_tc_order.' );

		// Assert the total value matches the expected total.
		$this->assertEquals(
			$expected_total->get(),
			$order->total_value->get_float(),
			'Order total_value should correctly include ticket price and fee.'
		);

		// Assert the subtotal matches the expected subtotal.
		$this->assertEquals(
			$expected_subtotal->get(),
			$order->subtotal->get_float(),
			'Order subtotal should only include the ticket price.'
		);
	}

	/**
	 * Data provider for testing order totals with various inputs.
	 *
	 * @return \Generator
	 */
	public function order_totals_data_provider(): \Generator {
		yield 'Ticket $10, Fee $5, Application All' => [
			'ticket_price'      => Float_Value::from_number( 10 ),
			'fee_raw_amount'    => Float_Value::from_number( 5 ),
			'fee_application'   => 'all',
			'expected_total'    => Float_Value::from_number( 15 ),
			'expected_subtotal' => Float_Value::from_number( 10 ),
		];

		yield 'Ticket $20, Fee $3, Application All' => [
			'ticket_price'      => Float_Value::from_number( 20 ),
			'fee_raw_amount'    => Float_Value::from_number( 3 ),
			'fee_application'   => 'all',
			'expected_total'    => Float_Value::from_number( 23 ),
			'expected_subtotal' => Float_Value::from_number( 20 ),
		];

		yield 'Ticket $15, Fee $2, Application Per' => [
			'ticket_price'      => Float_Value::from_number( 15 ),
			'fee_raw_amount'    => Float_Value::from_number( 2 ),
			'fee_application'   => 'per',
			'expected_total'    => Float_Value::from_number( 17 ),
			'expected_subtotal' => Float_Value::from_number( 15 ),
		];

		yield 'Ticket $50, Fee $10, Application Per' => [
			'ticket_price'      => Float_Value::from_number( 50 ),
			'fee_raw_amount'    => Float_Value::from_number( 10 ),
			'fee_application'   => 'per',
			'expected_total'    => Float_Value::from_number( 60 ),
			'expected_subtotal' => Float_Value::from_number( 50 ),
		];
	}

	/**
	 * @test
	 */
	public function it_should_include_fees_with_paypal_order_items() {
		$this->make_controller()->register();
		$post = static::factory()->post->create();

		// Create a ticket and some fees.
		$ticket_id      = $this->create_tc_ticket( $post, 10 );
		$fee_for_all    = $this->create_fee_for_all( [ 'raw_amount' => 10, 'sub_type' => 'percent' ] );
		$fee_per_ticket = $this->create_fee_for_ticket( $ticket_id, [ 'raw_amount' => 2, 'sub_type' => 'percent' ] );
		$this->add_fee_to_ticket( $fee_per_ticket, $ticket_id );

		// Set up the cart and add the ticket to it.
		$cart = tribe( Commerce_Cart::class );
		$cart->add_ticket( $ticket_id, 2 );

		// Create the order using the REST API, because that's how it will work on a live site.
		[ $path, $query_args, $args ] = $this->create_order_via_rest();

		// Verify the data from the request.
		Assert::assertEquals( '/v2/checkout/orders', $path );
		Assert::assertEmpty( $query_args );
		Assert::assertTrue( isset( $args['body']['purchase_units'][0] ) );

		$purchase_unit = $args['body']['purchase_units'][0];
		Assert::assertCount( 3, $purchase_unit['items'], 'There should be a ticket and 2 fees in the unit data' );

		// Each item should have the same quantity.
		foreach ( $purchase_unit['items'] as $item ) {
			Assert::assertEquals( 2, $item['quantity'], 'The quantity should be 2' );
		}

		// Make sure we have the item breakdown.
		Assert::assertArrayHasKey( 'breakdown', $purchase_unit['amount'], 'The amount should have a breakdown of items.' );
		Assert::assertCount( 1, $purchase_unit['amount']['breakdown'], 'The amount should have a breakdown of items only.' );
		Assert::assertArrayHasKey( 'item_total', $purchase_unit['amount']['breakdown'], 'The breakdown should have an item total' );

		// The item total should be 10*2 + 10*2*.1 + 10*2*.02 = 20 + 2 + 0.4 = 22.40 (as a string).
		Assert::assertSame( '22.40', $purchase_unit['amount']['breakdown']['item_total']['value'] );
	}
}
