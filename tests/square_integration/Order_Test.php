<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use RuntimeException;
use Tribe__Repository;
use TEC\Tickets\Commerce\Order as Commerce_Order;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_Post;

class Order_Test extends WPTestCase {
	use With_Uopz;
	use Order_Maker;
	use Ticket_Maker;

	/**
	 * The buyer's email, planted in every Square answer and event, then looked for in the log.
	 *
	 * @var string
	 */
	private string $buyer_email;

	/**
	 * Every `tribe_log` call made during the test, JSON encoded.
	 *
	 * @var string[]
	 */
	private array $logged = [];

	/**
	 * The Square API answers, keyed by the endpoint path they answer.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $square_answers = [];

	/**
	 * @before
	 */
	public function capture_logs_and_square_requests(): void {
		$this->buyer_email    = 'buyer-' . wp_generate_password( 12, false ) . '@example.org';
		$this->logged         = [];
		$this->square_answers = [];

		// The suite bootstrap turns warning logs into exceptions; these tests assert on failure paths.
		remove_action( 'tribe_log', $GLOBALS['tec_tickets_square_log_guard'], 10 );
		add_action( 'tribe_log', [ $this, 'record_log' ], 10, 3 );
		add_filter( 'pre_http_request', [ $this, 'answer_square' ], 10, 3 );
	}

	/**
	 * @after
	 */
	public function stop_capturing(): void {
		remove_filter( 'pre_http_request', [ $this, 'answer_square' ], 10 );
		remove_action( 'tribe_log', [ $this, 'record_log' ], 10 );
		add_action( 'tribe_log', $GLOBALS['tec_tickets_square_log_guard'], 10, 3 );
		tribe_cache()->reset();
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
	 * Answers Square API requests with the answer set for their endpoint, and any other with an empty body.
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

		$answer = [];

		foreach ( $this->square_answers as $path => $path_answer ) {
			if ( false !== strpos( $url, $path ) ) {
				$answer = $path_answer;
				break;
			}
		}

		return [
			'headers'  => [],
			'body'     => $answer ? wp_json_encode( $answer ) : '{}',
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
	public function it_should_not_log_the_event_data_of_an_order_webhook_with_no_state(): void {
		$order           = $this->create_square_order();
		$square_order_id = 'sq-order-' . wp_generate_password( 8, false );
		update_post_meta( $order->ID, Commerce_Order::$gateway_order_id_meta_key, $square_order_id );
		clean_post_cache( $order->ID );

		tribe_cache()[ 'tec_tickets_commerce_square_order_' . $square_order_id ] = [
			'order' => [
				'id'           => $square_order_id,
				'reference_id' => "{$order->ID}",
			],
		];

		tribe( Order::class )->upsert_local_from_square_order(
			$square_order_id,
			[
				'event_id' => 'event-' . wp_generate_password( 8, false ),
				'data'     => [ 'object' => [ 'order_updated' => [ 'note' => $this->buyer_email ] ] ],
			]
		);

		$this->assert_logged_without_buyer_data();
	}

	/**
	 * Square answers that make pushing a local order to Square fail.
	 *
	 * @return Generator<string, array{0: Closure}>
	 */
	public function failed_order_push_provider(): Generator {
		yield 'calculate fails' => [
			fn( string $email ): array => [ 'orders/calculate' => [ 'order' => [ 'fulfillments' => [ [ 'recipient' => [ 'email_address' => $email ] ] ] ] ] ],
		];

		yield 'upsert fails' => [
			fn( string $email ): array => [
				'orders/calculate' => [ 'order' => [ 'total_money' => [ 'amount' => 100 ] ] ],
				'orders'           => [ 'order' => [ 'fulfillments' => [ [ 'recipient' => [ 'email_address' => $email ] ] ] ] ],
			],
		];
	}

	/**
	 * @test
	 * @dataProvider failed_order_push_provider
	 */
	public function it_should_not_log_the_square_response_when_pushing_an_order_fails( Closure $answers ): void {
		$order                = $this->create_square_order();
		$this->square_answers = $answers( $this->buyer_email );

		try {
			tribe( Order::class )->upsert_square_from_local_order( $order );
		} catch ( RuntimeException $e ) {
			// The push throws on failure; the log is what is under test.
			unset( $e );
		}

		$this->assert_logged_without_buyer_data();
	}

	/**
	 * @test
	 */
	public function it_should_not_log_the_order_when_saving_the_square_order_id_fails(): void {
		$order                = $this->create_square_order();
		$this->square_answers = [
			'orders/calculate' => [ 'order' => [ 'total_money' => [ 'amount' => 1000 ] ] ],
			'orders'           => [
				'order' => [
					'id'           => 'sq-order-id',
					'version'      => 1,
					'fulfillments' => [ [ 'recipient' => [ 'email_address' => $this->buyer_email ] ] ],
				],
			],
		];
		// A failed save cannot be produced through the database without breaking the order itself.
		$this->set_class_fn_return( Tribe__Repository::class, 'save', [] );

		try {
			tribe( Order::class )->upsert_square_from_local_order( $order );
		} catch ( RuntimeException $e ) {
			// The push throws on failure; the log is what is under test.
			unset( $e );
		}

		$this->assert_logged_without_buyer_data();
		$this->assertStringNotContainsString( $order->purchaser_email, implode( "\n", $this->logged ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_log_the_square_response_when_a_refund_fails(): void {
		$square_order_id = 'sq-order-' . wp_generate_password( 8, false );

		tribe_cache()[ 'tec_tickets_commerce_square_order_' . $square_order_id ] = [
			'order' => [
				'id'      => $square_order_id,
				'tenders' => [
					[
						'id'           => 'tender-id',
						'amount_money' => [
							'amount'   => 100,
							'currency' => 'USD',
						],
					],
				],
			],
		];
		$this->square_answers = [ 'refunds' => [ 'payment' => [ 'buyer_email_address' => $this->buyer_email ] ] ];

		tribe( Order::class )->refund_remote_order( $square_order_id );

		$this->assert_logged_without_buyer_data();
	}

	/**
	 * Creates a Tickets Commerce order paid through Square.
	 *
	 * @return WP_Post The order.
	 */
	private function create_square_order(): WP_Post {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		return $this->create_order_through_square( [ $ticket_id => 1 ] );
	}

	/**
	 * Asserts something was logged, and none of it carries the buyer's data.
	 */
	private function assert_logged_without_buyer_data(): void {
		$this->assertNotEmpty( $this->logged, 'The failure should still be logged.' );
		$this->assertStringNotContainsString( $this->buyer_email, implode( "\n", $this->logged ) );
	}
}
