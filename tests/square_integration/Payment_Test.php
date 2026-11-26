<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Payment_Test extends WPTestCase {
	use Order_Maker;
	use Ticket_Maker;

	/**
	 * Every `tribe_log` call made during the test, JSON encoded.
	 *
	 * @var string[]
	 */
	private array $logged = [];

	/**
	 * The buyer's email Square answers with.
	 *
	 * @var string
	 */
	private string $buyer_email;

	/**
	 * @before
	 */
	public function capture_logs_and_square_requests(): void {
		$this->buyer_email = 'buyer-' . wp_generate_password( 12, false ) . '@example.org';
		$this->logged      = [];

		add_action( 'tribe_log', [ $this, 'record_log' ], 10, 3 );
		add_filter( 'pre_http_request', [ $this, 'answer_square' ], 10, 3 );
	}

	/**
	 * @after
	 */
	public function stop_capturing(): void {
		remove_filter( 'pre_http_request', [ $this, 'answer_square' ], 10 );
		remove_action( 'tribe_log', [ $this, 'record_log' ], 10 );
	}

	/**
	 * Records a log call.
	 *
	 * @param string $level   The log level.
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 */
	public function record_log( $level, $message, $context = [] ): void {
		$this->logged[] = wp_json_encode( [ $level, $message, $context ], JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Answers the Square payments endpoint with a payment that carries the buyer's details, and any other Square URL with an empty body.
	 *
	 * @param false|array $preempt Whether to preempt the request.
	 * @param array       $args    The request arguments.
	 * @param string      $url     The request URL.
	 *
	 * @return false|array
	 */
	public function answer_square( $preempt, $args, $url ) {
		if ( false === strpos( $url, 'squareup.com' ) ) {
			return $preempt;
		}

		return [
			'headers'  => [],
			'body'     => false === strpos( $url, '/v2/payments' ) ? '{}' : wp_json_encode(
				[
					'payment' => [
						'id'                  => 'payment-id',
						'status'              => 'COMPLETED',
						'buyer_email_address' => $this->buyer_email,
					],
				]
			),
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'cookies'  => [],
			'filename' => null,
		];
	}

	/**
	 * @test
	 */
	public function it_should_log_a_created_payment_without_the_buyer_data(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );
		$order     = $this->create_order_through_square( [ $ticket_id => 1 ] );

		$payment = Payment::create_from_order( 'cnon:card-nonce-ok', $order, 'sq-order-id' );

		$this->assertSame( 'payment-id', $payment['id'] );
		$this->assertStringContainsString( 'payment-id', implode( "\n", $this->logged ), 'The payment should still be logged.' );
		$this->assertStringNotContainsString( $this->buyer_email, implode( "\n", $this->logged ) );
	}
}
