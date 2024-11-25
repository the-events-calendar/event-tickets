<?php

namespace TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\PayPal;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway as PayPalGateway;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Float_Value;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Fee_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Reservations_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use WP_Post;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;

class Paypal_Fees_Test extends Controller_Test_Case {
	use Ticket_Maker;
	use Attendee_Maker;
	use With_Tickets_Commerce;
	use Reservations_Maker;
	use SnapshotAssertions;
	use Order_Maker;
	use Fee_Creator;

	protected string $controller_class = Fees::class;

	/**
	 * @after
	 */
	public function breakdown() {
		$this->make_controller()->reset_fees_and_subtotal();
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

		// Associate the fee with the event.
		$this->create_fee_relationship( $fee, $ticket, get_post_type( $ticket ) );

		// Set up overrides with the specified gateway.
		$overrides['gateway'] = tribe( PayPalGateway::class );

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
}
