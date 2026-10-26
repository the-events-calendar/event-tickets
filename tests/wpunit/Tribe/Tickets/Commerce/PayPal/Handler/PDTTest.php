<?php

namespace Tribe\Tickets\Commerce\PayPal\Handler;

use Codeception\TestCase\WPTestCase;
use Faker\Factory;
use Generator;
use Tribe\Tickets\Test\Commerce\PayPal\Transaction_Maker;
use Tribe__Tickets__Commerce__PayPal__Order as Order;
use Tribe__Tickets__Commerce__PayPal__Stati as Stati;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * A PDT return only issues usable tickets for the payment status PayPal reports as Completed.
 */
class PDTTest extends WPTestCase {
	use Transaction_Maker;

	/**
	 * @return Generator<string,array{0: string, 1: string}> The status PayPal reports and the one stored for it.
	 */
	public function non_completed_payment_status_provider(): Generator {
		yield 'pending' => [ 'Pending', Stati::$pending ];
		yield 'lowercase pending' => [ 'pending', Stati::$pending ];
		yield 'denied' => [ 'Denied', Stati::$denied ];
		yield 'status with no stored match' => [ 'Failed', Stati::$undefined ];
	}

	/**
	 * @test
	 * @dataProvider non_completed_payment_status_provider
	 */
	public function it_should_not_complete_attendees_or_send_tickets_for_a_non_completed_payment( string $paypal_status, string $stored_status ): void {
		[ $ticket, $quantity ] = $this->make_ticket_and_quantity();
		$transaction           = $this->paypal_transaction( $ticket, $quantity, $this->check_out_with_paypal( $ticket->ID, $quantity ), [ 'payment_status' => $paypal_status ] );

		$this->return_from_paypal( $transaction );

		$attendees = tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $transaction['txn_id'] );

		$this->assertSame( array_fill( 0, $quantity, $stored_status ), array_column( $attendees, 'order_status' ) );
		$this->assertSame( $stored_status, Order::from_order_id( $transaction['txn_id'] )->get_status() );
		$this->assertSame( array_fill( 0, $quantity, 0 ), array_column( $attendees, 'ticket_sent' ) );
		$this->assertSame( 0, $this->paypal_sent_emails );
		$this->assertSame( 0, $this->stored_paypal_ticket( $ticket )->qty_sold() );
	}

	/**
	 * @test
	 */
	public function it_should_count_a_pending_payment_as_pending_stock(): void {
		[ $ticket, $quantity ] = $this->make_ticket_and_quantity();

		$this->return_from_paypal( $this->paypal_transaction( $ticket, $quantity, $this->check_out_with_paypal( $ticket->ID, $quantity ), [ 'payment_status' => 'Pending' ] ) );

		$this->assertSame( $quantity, tribe( 'tickets.commerce.paypal' )->get_qty_pending( $ticket->ID, true ) );
	}

	/**
	 * @test
	 */
	public function it_should_complete_attendees_and_send_tickets_for_a_completed_payment(): void {
		[ $ticket, $quantity ] = $this->make_ticket_and_quantity();
		$transaction           = $this->paypal_transaction( $ticket, $quantity, $this->check_out_with_paypal( $ticket->ID, $quantity ) );

		$this->return_from_paypal( $transaction );

		$attendees = tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $transaction['txn_id'] );

		$this->assertSame( array_fill( 0, $quantity, Stati::$completed ), array_column( $attendees, 'order_status' ) );
		$this->assertSame( 1, $this->paypal_sent_emails, 'One email carries every ticket of the order.' );
		$this->assertSame( $quantity, $this->stored_paypal_ticket( $ticket )->qty_sold() );
	}

	/**
	 * @test
	 */
	public function it_should_complete_the_attendees_and_count_the_sales_once_a_pending_payment_completes(): void {
		[ $ticket, $quantity ] = $this->make_ticket_and_quantity();
		$pending               = $this->paypal_transaction( $ticket, $quantity, $this->check_out_with_paypal( $ticket->ID, $quantity ), [ 'payment_status' => 'Pending' ] );

		$this->return_from_paypal( $pending );
		$this->return_from_paypal( array_merge( $pending, [ 'payment_status' => 'Completed' ] ) );

		$attendees = tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $pending['txn_id'] );

		$this->assertSame( array_fill( 0, $quantity, Stati::$completed ), array_column( $attendees, 'order_status' ) );
		$this->assertSame( 1, $this->paypal_sent_emails );
		$this->assertSame( $quantity, $this->stored_paypal_ticket( $ticket )->qty_sold() );
		$this->assertSame( 0, tribe( 'tickets.commerce.paypal' )->get_qty_pending( $ticket->ID, true ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_complete_attendees_when_the_payment_status_is_missing(): void {
		[ $ticket, $quantity ] = $this->make_ticket_and_quantity();
		$transaction           = $this->paypal_transaction( $ticket, $quantity, $this->check_out_with_paypal( $ticket->ID, $quantity ), [ 'payment_status' => null ] );

		$this->return_from_paypal( $transaction );

		$attendees = tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $transaction['txn_id'] );

		$this->assertSame( array_fill( 0, $quantity, Stati::$undefined ), array_column( $attendees, 'order_status' ) );
		$this->assertSame( 0, $this->paypal_sent_emails );
		$this->assertSame( 0, $this->stored_paypal_ticket( $ticket )->qty_sold() );
	}

	/**
	 * @return array{0: Ticket_Object, 1: int} A ticket with stock to spare and the quantity to buy.
	 */
	private function make_ticket_and_quantity(): array {
		$faker     = Factory::create();
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_paypal_ticket( $post_id, $faker->numberBetween( 10, 50 ) );

		return [ tribe( 'tickets.commerce.paypal' )->get_ticket( $post_id, $ticket_id ), $faker->numberBetween( 1, 3 ) ];
	}
}
