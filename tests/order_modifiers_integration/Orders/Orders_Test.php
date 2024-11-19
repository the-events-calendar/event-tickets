<?php

namespace TEC\Tickets\Commerce\Order_Modifiers;

use Codeception\TestCase\WPTestCase;
use TEC\Common\Contracts\Container;
use TEC\Tickets\Commerce\Cart\Unmanaged_Cart;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway as PayPalGateway;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway as StripeGateway;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\PayPal\Fees as PayPal_Fees;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\Stripe\Fees as Stripe_Fees;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Fee;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier_Relationships as Relationships_Model;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifier_Relationship as Relationship_Repository;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers_Meta as Repository;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Float_Value;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Reservations_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use WP_Post;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;

class Orders_Test extends WPTestCase {
	use Ticket_Maker;
	use Attendee_Maker;
	use With_Tickets_Commerce;
	use Reservations_Maker;
	use SnapshotAssertions;
	use Order_Maker;

	/** @var ?Unmanaged_Cart */
	protected ?Unmanaged_Cart $cart;

	/** @var ?Repository */
	protected ?Repository $repository;

	/** @var ?Relationship_Repository */
	protected ?Relationship_Repository $relationship_repository;

	/** @var Stripe_Fees */
	protected Stripe_Fees $stripe_fees;

	/** @var PayPal_Fees */
	protected PayPal_Fees $paypal_fees;

	/**
	 * @before
	 */
	public function set_up() {
		$this->cart ??= new Unmanaged_Cart();
		$this->cart->clear();
		$this->repository              ??= new Repository();
		$this->relationship_repository ??= new Relationship_Repository();
		$this->stripe_fees               = tribe( Container::class )->get( Stripe_Fees::class );
		$this->paypal_fees               = tribe( Container::class )->get( PayPal_Fees::class );
		Stripe_Fees::reset_fees_and_subtotal_static();
		PayPal_Fees::reset_fees_and_subtotal_static();
	}

	/**
	 * @after
	 * @return void
	 */
	public function breakdown() {
		Stripe_Fees::reset_fees_and_subtotal_static();
		PayPal_Fees::reset_fees_and_subtotal_static();
	}

	/**
	 * @test
	 * @dataProvider order_totals_data_provider
	 * Ensures the order totals are calculated correctly for tickets and fees using the Stripe gateway.
	 */
	public function it_calculates_order_totals_with_stripe_gateway(
		Float_Value $ticket_price,
		Float_Value $fee_raw_amount,
		string $fee_application,
		Float_Value $expected_total,
		Float_Value $expected_subtotal
	): void {
		$this->run_order_totals_test( $ticket_price, $fee_raw_amount, $fee_application, $expected_total, $expected_subtotal, StripeGateway::class );
	}

	/**
	 * @test
	 * @dataProvider order_totals_data_provider
	 * Ensures the order totals are calculated correctly for tickets and fees using the PayPal gateway.
	 */
	public function it_calculates_order_totals_with_paypal_gateway(
		Float_Value $ticket_price,
		Float_Value $fee_raw_amount,
		string $fee_application,
		Float_Value $expected_total,
		Float_Value $expected_subtotal
	): void {
		$this->run_order_totals_test( $ticket_price, $fee_raw_amount, $fee_application, $expected_total, $expected_subtotal, PayPalGateway::class );
	}

	/**
	 * Runs the order totals test for a given gateway.
	 *
	 * @param Float_Value $ticket_price
	 * @param Float_Value $fee_raw_amount
	 * @param string      $fee_application
	 * @param Float_Value $expected_total
	 * @param Float_Value $expected_subtotal
	 * @param string      $gateway_class
	 *
	 * @return void
	 */
	protected function run_order_totals_test(
		Float_Value $ticket_price,
		Float_Value $fee_raw_amount,
		string $fee_application,
		Float_Value $expected_total,
		Float_Value $expected_subtotal,
		string $gateway_class
	): void {
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
		$this->relationship_repository->insert(
			new Relationships_Model(
				[
					'modifier_id' => $fee->id,
					'post_id'     => $ticket,
					'post_type'   => 'post',
				]
			)
		);

		// Set up overrides with the specified gateway.
		$overrides['gateway'] = tribe( $gateway_class );

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
	 * @param array $args The arguments to use when creating the fee.
	 *
	 * @return Fee The created fee.
	 * @todo - Move to trait
	 *       Create a fee with the provided arguments.
	 *
	 */
	protected function create_fee( array $args = [] ): Fee {
		$args = array_merge(
			[
				'sub_type'     => 'flat',
				'raw_amount'   => Float_Value::from_number( 5 ),
				'slug'         => 'test-fee',
				'display_name' => 'test fee',
				'status'       => 'active',
				'start_time'   => null,
				'end_time'     => null,
			],
			$args
		);

		return Fee::create( $args );
	}

	/**
	 * @param Fee   $fee        The fee to set the application for.
	 * @param mixed $applied_to The value to set the fee application to.
	 *
	 * @todo - Move to trait
	 *       Set the fee application to the provided value.
	 *
	 */
	protected function set_fee_application( Fee $fee, $applied_to ) {
		$this->repository->upsert_meta(
			new Order_Modifier_Meta(
				[
					'order_modifier_id' => $fee->id,
					'meta_key'          => 'fee_applied_to',
					'meta_value'        => $applied_to, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'priority'          => 0,
				]
			)
		);
	}
}
