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
use WP_Post;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Commerce\Cart\Unmanaged_Cart as Cart;
use TEC\Tickets\Commerce\Shortcodes\Checkout_Shortcode;

class Fees_Test extends Controller_Test_Case {
	use Ticket_Maker;
	use Attendee_Maker;
	use With_Tickets_Commerce;
	use Reservations_Maker;
	use SnapshotAssertions;
	use Order_Maker;
	use Fee_Creator;

	protected string $controller_class = Fees::class;

	/**
	 * @test
	 * @dataProvider cart_totals_data_provider
	 */
	public function it_should_calculate_fees(
		Float_Value $ticket_price,
		Float_Value $fee_raw_amount,
		string $fee_application,
		Float_Value $expected_total
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

		// Assert the total value matches the expected total.
		$this->assertEquals(
			$expected_total->get(),
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
		string $fee_application,
		Float_Value $expected_total
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

		// Assert the total value matches the expected total.
		$this->assertMatchesHtmlSnapshot( tribe( Checkout_Shortcode::class )->get_html() );
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
		];

		yield 'Ticket $20, Fee $3, Application All' => [
			'ticket_price'      => Float_Value::from_number( 20 ),
			'fee_raw_amount'    => Float_Value::from_number( 3 ),
			'fee_application'   => 'all',
			'expected_total'    => Float_Value::from_number( 23 ),
		];

		yield 'Ticket $15, Fee $2, Application Per' => [
			'ticket_price'      => Float_Value::from_number( 15 ),
			'fee_raw_amount'    => Float_Value::from_number( 2 ),
			'fee_application'   => 'per',
			'expected_total'    => Float_Value::from_number( 17 ),
		];

		yield 'Ticket $50, Fee $10, Application Per' => [
			'ticket_price'      => Float_Value::from_number( 50 ),
			'fee_raw_amount'    => Float_Value::from_number( 10 ),
			'fee_application'   => 'per',
			'expected_total'    => Float_Value::from_number( 60 ),
		];
	}
}
