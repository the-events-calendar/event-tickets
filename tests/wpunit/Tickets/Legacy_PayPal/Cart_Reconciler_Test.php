<?php

namespace TEC\Tickets\Legacy_PayPal;

use Closure;
use Codeception\TestCase\WPTestCase;
use Faker\Factory;
use Generator;
use Tribe\Tickets\Test\Commerce\PayPal\Transaction_Maker;
use Tribe__Tickets__Commerce__PayPal__Custom_Argument as Custom_Argument;
use Tribe__Tickets__Commerce__PayPal__Stati as Stati;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * A PayPal payment, returned through PDT or notified through IPN, is only fulfilled when it matches what the
 * checkout sent to PayPal.
 */
class Cart_Reconciler_Test extends WPTestCase {
	use Transaction_Maker;

	/**
	 * @var array<int,array<string,mixed>> The context of each warning logged.
	 */
	private array $logged_warnings = [];

	/**
	 * @before
	 */
	public function record_warnings(): void {
		$this->logged_warnings = [];

		add_action(
			'tribe_log',
			function ( $level, $source, $context ) {
				if ( 'warning' === $level ) {
					$this->logged_warnings[] = $context;
				}
			},
			10,
			3
		);
	}

	/**
	 * @return Generator<string,array{0: string, 1: Closure}> How PayPal reports the payment and the tampered fields.
	 */
	public function tampered_payment_provider(): Generator {
		$tamperings = [
			'token payment'              => static fn( Ticket_Object $ticket, int $quantity ) => [ 'mc_gross' => '0.01', 'mc_gross_1' => '0.01' ],
			'item paid below its amount' => static fn( Ticket_Object $ticket, int $quantity ) => [ 'mc_gross_1' => '0.01' ],
			'quantity raised'            => static fn( Ticket_Object $ticket, int $quantity ) => [ 'quantity1' => $quantity + 1 ],
			'other currency'             => static fn( Ticket_Object $ticket, int $quantity ) => [ 'mc_currency' => 'JPY' ],
			'other merchant'             => static fn( Ticket_Object $ticket, int $quantity ) => [
				'receiver_email' => 'attacker@example.com',
				'business'       => 'attacker@example.com',
			],
			'negative total'             => static fn( Ticket_Object $ticket, int $quantity ) => [ 'mc_gross' => '-' . $ticket->price * $quantity ],
			'total is not a number'      => static fn( Ticket_Object $ticket, int $quantity ) => [ 'mc_gross' => 'free' ],
			'no invoice'                 => static fn( Ticket_Object $ticket, int $quantity ) => [
				'custom' => Custom_Argument::encode( [ 'user_id' => 0, 'tribe_handler' => 'tpp' ] ),
			],
			'invoice with no checkout'   => static fn( Ticket_Object $ticket, int $quantity ) => [
				'custom' => Custom_Argument::encode( [ 'user_id' => 0, 'tribe_handler' => 'tpp', 'invoice' => 'nocheckout12' ] ),
			],
		];

		foreach ( $tamperings as $name => $tamper ) {
			yield "{$name} returned through PDT" => [ 'return_from_paypal', $tamper ];
			yield "{$name} notified through IPN" => [ 'notify_from_paypal', $tamper ];
		}
	}

	/**
	 * @test
	 * @dataProvider tampered_payment_provider
	 */
	public function it_should_not_fulfil_a_payment_that_does_not_match_the_checkout( string $deliver, Closure $tamper ): void {
		[ $ticket, $quantity ] = $this->make_ticket_and_quantity();
		$invoice               = $this->check_out_with_paypal( $ticket->ID, $quantity );
		$transaction           = $this->paypal_transaction( $ticket, $quantity, $invoice, $tamper( $ticket, $quantity ) );

		$this->{$deliver}( $transaction );

		$this->assertSame( [], tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $transaction['txn_id'] ) );
		$this->assertSame( 0, $this->paypal_sent_emails );
		$this->assertSame( $transaction['txn_id'], $this->logged_warnings[0]['txn_id'] ?? null, 'The merchant must be able to find the payment.' );
	}

	/**
	 * @test
	 */
	public function it_should_tell_the_buyer_that_their_payment_was_not_matched(): void {
		[ $ticket, $quantity ] = $this->make_ticket_and_quantity();
		$invoice               = $this->check_out_with_paypal( $ticket->ID, $quantity );

		$this->return_from_paypal( $this->paypal_transaction( $ticket, $quantity, $invoice, [ 'mc_gross' => '0.01' ] ) );

		$this->assertSame( [ add_query_arg( 'tpp_error', 104, get_permalink( $ticket->get_event_id() ) ) ], $this->paypal_redirects );
	}

	/**
	 * @test
	 */
	public function it_should_ignore_a_return_from_paypal_for_another_plugin(): void {
		[ $ticket, $quantity ] = $this->make_ticket_and_quantity();
		$transaction           = $this->paypal_transaction(
			$ticket,
			$quantity,
			'',
			[ 'custom' => wp_json_encode( [ 'order_id' => 123 ] ) ]
		);

		$this->return_from_paypal( $transaction );

		$this->assertSame( [], $this->paypal_redirects );
		$this->assertSame( [], $this->logged_warnings );
	}

	/**
	 * @test
	 */
	public function it_should_not_fulfil_a_ticket_the_checkout_did_not_send(): void {
		[ $ticket, $quantity ] = $this->make_ticket_and_quantity();
		[ $other_ticket ]      = $this->make_ticket_and_quantity();
		$invoice               = $this->check_out_with_paypal( $ticket->ID, $quantity );
		$transaction           = $this->paypal_transaction( $other_ticket, $quantity, $invoice );

		$this->return_from_paypal( $transaction );

		$this->assertSame( [], tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $transaction['txn_id'] ) );
		$this->assertSame( 0, $this->stored_paypal_ticket( $other_ticket )->qty_sold() );
	}

	/**
	 * @test
	 */
	public function it_should_not_fulfil_a_second_payment_against_a_fulfilled_checkout(): void {
		[ $ticket, $quantity ] = $this->make_ticket_and_quantity();
		$invoice               = $this->check_out_with_paypal( $ticket->ID, $quantity );
		$first                 = $this->paypal_transaction( $ticket, $quantity, $invoice );
		$second                = $this->paypal_transaction( $ticket, $quantity, $invoice );

		$this->return_from_paypal( $first );
		$this->return_from_paypal( $second );

		$this->assertCount( $quantity, tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $first['txn_id'] ) );
		$this->assertSame( [], tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $second['txn_id'] ) );
	}

	/**
	 * @return Generator<string,array{0: string, 1: Closure}> How PayPal reports the payment and the fields that differ legitimately.
	 */
	public function matching_payment_provider(): Generator {
		$differences = [
			'exact amounts'                          => static fn( Ticket_Object $ticket, int $quantity ) => [],
			'merchant profile tax and shipping'      => static fn( Ticket_Object $ticket, int $quantity ) => [
				'mc_gross'    => $ticket->price * $quantity + 3.5,
				'mc_gross_1'  => $ticket->price * $quantity + 3.5,
				'tax'         => '2.50',
				'mc_shipping' => '1.00',
			],
			'primary email differs from the setting' => static fn( Ticket_Object $ticket, int $quantity ) => [ 'receiver_email' => 'primary@example.com' ],
		];

		foreach ( $differences as $name => $difference ) {
			yield "{$name} returned through PDT" => [ 'return_from_paypal', $difference ];
			yield "{$name} notified through IPN" => [ 'notify_from_paypal', $difference ];
		}
	}

	/**
	 * @test
	 * @dataProvider matching_payment_provider
	 */
	public function it_should_fulfil_a_payment_that_matches_the_checkout( string $deliver, Closure $difference ): void {
		[ $ticket, $quantity ] = $this->make_ticket_and_quantity();
		$invoice               = $this->check_out_with_paypal( $ticket->ID, $quantity );
		$transaction           = $this->paypal_transaction( $ticket, $quantity, $invoice, $difference( $ticket, $quantity ) );

		$this->{$deliver}( $transaction );

		$attendees = tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $transaction['txn_id'] );

		$this->assertSame( array_fill( 0, $quantity, Stati::$completed ), array_column( $attendees, 'order_status' ) );
		$this->assertSame( [], $this->logged_warnings );
	}

	/**
	 * @test
	 */
	public function it_should_fulfil_the_price_sent_to_paypal_after_the_price_changes(): void {
		[ $ticket, $quantity ] = $this->make_ticket_and_quantity();
		$invoice               = $this->check_out_with_paypal( $ticket->ID, $quantity );
		update_post_meta( $ticket->ID, '_price', $ticket->price + 10 );

		$transaction = $this->paypal_transaction( $ticket, $quantity, $invoice );
		$this->return_from_paypal( $transaction );

		$this->assertCount( $quantity, tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $transaction['txn_id'] ) );
	}

	/**
	 * @test
	 */
	public function it_should_fulfil_the_quantity_the_checkout_capped_at_the_remaining_stock(): void {
		$faker     = Factory::create();
		$capacity  = $faker->numberBetween( 1, 3 );
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_paypal_ticket(
			$post_id,
			$faker->numberBetween( 10, 50 ),
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::OWN_STOCK_MODE,
					'capacity' => $capacity,
				],
			]
		);
		$ticket    = tribe( 'tickets.commerce.paypal' )->get_ticket( $post_id, $ticket_id );
		$invoice   = $this->check_out_with_paypal( $ticket_id, $capacity + 2 );

		$transaction = $this->paypal_transaction( $ticket, $capacity, $invoice );
		$this->return_from_paypal( $transaction );

		$this->assertCount( $capacity, tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $transaction['txn_id'] ) );
	}

	/**
	 * @test
	 */
	public function it_should_fulfil_the_amount_a_cart_filter_sent_to_paypal(): void {
		[ $ticket, $quantity ] = $this->make_ticket_and_quantity();
		$discounted            = $ticket->price - 5;
		add_filter(
			'tribe_tickets_commerce_paypal_add_to_cart_args',
			static function ( array $args ) use ( $discounted ) {
				$args['amount'] = $discounted;

				return $args;
			}
		);
		$invoice = $this->check_out_with_paypal( $ticket->ID, $quantity );

		$transaction = $this->paypal_transaction(
			$ticket,
			$quantity,
			$invoice,
			[
				'mc_gross'   => $discounted * $quantity,
				'mc_gross_1' => $discounted * $quantity,
			]
		);
		$this->return_from_paypal( $transaction );

		$this->assertCount( $quantity, tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $transaction['txn_id'] ) );
	}

	/**
	 * @test
	 */
	public function it_should_show_the_success_page_when_the_buyer_returns_after_the_notification(): void {
		[ $ticket, $quantity ] = $this->make_ticket_and_quantity();
		$transaction           = $this->paypal_transaction( $ticket, $quantity, $this->check_out_with_paypal( $ticket->ID, $quantity ) );

		$this->notify_from_paypal( $transaction );
		$this->return_from_paypal( $transaction );
		$this->return_from_paypal( $transaction );

		$attendees = tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $transaction['txn_id'] );

		$this->assertSame( array_fill( 0, $quantity, Stati::$completed ), array_column( $attendees, 'order_status' ) );
		$this->assertSame( [], $this->logged_warnings );
		$this->assertSame( 1, $this->paypal_sent_emails );
		$this->assertSame( $quantity, $this->stored_paypal_ticket( $ticket )->qty_sold() );
	}

	/**
	 * @test
	 */
	public function it_should_complete_a_pending_payment_once_paypal_notifies_its_completion(): void {
		[ $ticket, $quantity ] = $this->make_ticket_and_quantity();
		$pending               = $this->paypal_transaction( $ticket, $quantity, $this->check_out_with_paypal( $ticket->ID, $quantity ), [ 'payment_status' => 'Pending' ] );

		$this->notify_from_paypal( $pending );
		$this->notify_from_paypal( array_merge( $pending, [ 'payment_status' => 'Completed' ] ) );

		$attendees = tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $pending['txn_id'] );

		$this->assertSame( array_fill( 0, $quantity, Stati::$completed ), array_column( $attendees, 'order_status' ) );
		$this->assertSame( [], $this->logged_warnings );
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
