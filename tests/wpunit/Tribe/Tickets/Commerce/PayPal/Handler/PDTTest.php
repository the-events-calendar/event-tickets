<?php

namespace Tribe\Tickets\Commerce\PayPal\Handler;

use Codeception\TestCase\WPTestCase;
use Faker\Factory;
use Generator;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Commerce__PayPal__Custom_Argument as Custom_Argument;
use Tribe__Tickets__Commerce__PayPal__Handler__PDT as PDT;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Commerce__PayPal__Stati as Stati;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * A PDT return only issues usable tickets for the payment status PayPal reports as Completed.
 */
class PDTTest extends WPTestCase {

	use PayPal_Ticket_Maker;

	/**
	 * @var array<string,string|string[]> The `$_GET` superglobal before the test.
	 */
	private $get_backup;

	/**
	 * @var int The number of emails `wp_mail()` was asked to send.
	 */
	private $sent_emails = 0;

	/**
	 * @before
	 */
	public function set_up_tribe_commerce(): void {
		$this->get_backup = $_GET;

		add_filter( 'tribe_tickets_post_types', static function () {
			return [ 'post' ];
		} );
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', static function ( $modules ) {
			$modules[ PayPal::class ] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );
		tribe_singleton( 'tickets.data_api', new Data_API() );

		add_filter( 'tribe_exit', static function () {
			return '__return_true';
		} );
		add_filter( 'wp_redirect', '__return_false' );
		add_filter( 'pre_wp_mail', function () {
			++$this->sent_emails;

			return true;
		} );
	}

	/**
	 * @after
	 */
	public function restore_get(): void {
		$_GET = $this->get_backup;
	}

	/**
	 * @return Generator<string,array{0: string}> The payment status PayPal reports.
	 */
	public function non_completed_payment_status_provider(): Generator {
		yield 'pending' => [ 'Pending' ];
		yield 'denied' => [ 'Denied' ];
		yield 'failed' => [ 'Failed' ];
		yield 'lowercase pending' => [ 'pending' ];
	}

	/**
	 * @test
	 * @dataProvider non_completed_payment_status_provider
	 */
	public function it_should_not_complete_attendees_or_send_tickets_for_a_non_completed_payment( string $payment_status ): void {
		[ $ticket, $txn_id, $quantity ] = $this->return_from_paypal_with( $payment_status );

		$attendees = tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $txn_id );

		$this->assertSame( array_fill( 0, $quantity, strtolower( $payment_status ) ), array_column( $attendees, 'order_status' ) );
		$this->assertSame( array_fill( 0, $quantity, 0 ), array_column( $attendees, 'ticket_sent' ) );
		$this->assertSame( 0, $this->sent_emails );
		$this->assertSame( 0, $this->refresh( $ticket )->qty_sold() );
	}

	/**
	 * @test
	 */
	public function it_should_complete_attendees_and_send_tickets_for_a_completed_payment(): void {
		[ $ticket, $txn_id, $quantity ] = $this->return_from_paypal_with( 'Completed' );

		$attendees = tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $txn_id );

		$this->assertCount( $quantity, $attendees );
		$this->assertSame( array_fill( 0, $quantity, 'completed' ), array_column( $attendees, 'order_status' ) );
		$this->assertSame( 1, $this->sent_emails, 'One email carries every ticket of the order.' );
		$this->assertSame( $quantity, $this->refresh( $ticket )->qty_sold() );
	}

	/**
	 * @test
	 */
	public function it_should_not_complete_attendees_when_the_payment_status_is_missing(): void {
		[ $ticket, $txn_id, $quantity ] = $this->return_from_paypal_with( null );

		$attendees = tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $txn_id );

		$this->assertSame( array_fill( 0, $quantity, Stati::$undefined ), array_column( $attendees, 'order_status' ) );
		$this->assertSame( 0, $this->sent_emails );
		$this->assertSame( 0, $this->refresh( $ticket )->qty_sold() );
	}

	/**
	 * Simulates the buyer landing back on the site from PayPal with a `tx` that PayPal's
	 * PDT lookup answers with SUCCESS and the given payment status.
	 *
	 * @param string|null $payment_status The `payment_status` PayPal reports, `null` to omit it.
	 *
	 * @return array{0: Ticket_Object, 1: string, 2: int} The ticket, the PayPal transaction ID and the quantity bought.
	 */
	private function return_from_paypal_with( ?string $payment_status ): array {
		$faker     = Factory::create();
		$post_id   = $this->factory()->post->create();
		$ticket_id = $this->create_paypal_ticket( $post_id, 10 );
		$txn_id    = strtoupper( $faker->bothify( '#?#?#?#?#?#?#?#?#' ) );
		$quantity  = $faker->numberBetween( 1, 3 );
		$fields    = [
			'payment_status' => $payment_status,
			'txn_id'         => $txn_id,
			'first_name'     => $faker->firstName(),
			'last_name'      => $faker->lastName(),
			'payer_email'    => $faker->safeEmail(),
			'custom'         => Custom_Argument::encode( [ 'tribe_handler' => 'tpp', 'user_id' => 0 ] ),
			'item_number1'   => "{$post_id}:{$ticket_id}",
			'quantity1'      => $quantity,
		];
		$body      = 'SUCCESS';
		foreach ( array_filter( $fields, 'is_scalar' ) as $key => $value ) {
			$body .= "\n{$key}=" . rawurlencode( $value );
		}

		add_filter( 'pre_http_request', static function () use ( $body ) {
			return [
				'headers'  => [],
				'body'     => $body,
				'response' => [ 'code' => 200, 'message' => 'OK' ],
				'cookies'  => [],
				'filename' => null,
			];
		} );

		$_GET['tx'] = $txn_id;

		( new PDT() )->check_response();

		return [ tribe( 'tickets.commerce.paypal' )->get_ticket( $post_id, $ticket_id ), $txn_id, $quantity ];
	}

	private function refresh( Ticket_Object $ticket ): Ticket_Object {
		return tribe( 'tickets.commerce.paypal' )->get_ticket( $ticket->get_event_id(), $ticket->ID );
	}
}
