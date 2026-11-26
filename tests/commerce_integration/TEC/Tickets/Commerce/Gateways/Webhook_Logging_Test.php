<?php

namespace TEC\Tickets\Commerce\Gateways;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Gateways\PayPal\Merchant as PayPal_Merchant;
use TEC\Tickets\Commerce\Gateways\PayPal\REST\Webhook_Endpoint as PayPal_Webhook_Endpoint;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Events as PayPal_Events;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Handler as PayPal_Handler;
use TEC\Tickets\Commerce\Gateways\Stripe\Webhooks\Handler as Stripe_Handler;
use TEC\Tickets\Commerce\Order;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_REST_Request;
use WP_REST_Response;

class Webhook_Logging_Test extends WPTestCase {
	use With_Uopz;
	use Order_Maker;
	use Ticket_Maker;

	/**
	 * The buyer's email, planted in every webhook event, then looked for in the log.
	 *
	 * @var string
	 */
	private string $buyer_email;

	/**
	 * Every entry the legacy logger was handed during the test.
	 *
	 * @var string[]
	 */
	private array $logged = [];

	/**
	 * The PayPal payment the PayPal API answers with.
	 *
	 * @var array<string, string>
	 */
	private array $paypal_payment = [];

	/**
	 * @before
	 */
	public function capture_logs(): void {
		$this->buyer_email    = 'buyer-' . wp_generate_password( 12, false ) . '@example.org';
		$this->logged         = [];
		$this->paypal_payment = [];

		// Every entry passes through this filter whatever the logging level; false keeps WP-CLI out of it.
		add_filter( 'tribe_common_log_to_wpcli', [ $this, 'record_log' ], 10, 2 );
		add_filter( 'pre_http_request', [ $this, 'answer_paypal' ], 10, 3 );
	}

	/**
	 * @after
	 */
	public function stop_capturing(): void {
		remove_filter( 'tribe_common_log_to_wpcli', [ $this, 'record_log' ], 10 );
		remove_filter( 'pre_http_request', [ $this, 'answer_paypal' ], 10 );
	}

	/**
	 * Records an entry handed to the legacy logger.
	 *
	 * @param bool   $log_to_wpcli Whether to log to WP-CLI.
	 * @param string $entry        The log entry.
	 *
	 * @return bool Always false.
	 */
	public function record_log( $log_to_wpcli, $entry ): bool {
		$this->logged[] = $entry;

		return false;
	}

	/**
	 * Answers PayPal API requests with the payment the test set.
	 *
	 * @param false|array $preempt Whether to preempt the request.
	 * @param array       $args    The request arguments.
	 * @param string      $url     The request URL.
	 *
	 * @return false|array
	 */
	public function answer_paypal( $preempt, $args, $url ) {
		if ( ! $this->paypal_payment || false === strpos( $url, 'paypal' ) ) {
			return $preempt;
		}

		return [
			'headers'  => [],
			'body'     => wp_json_encode( $this->paypal_payment ),
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
	public function it_should_not_log_the_body_of_a_paypal_event_of_an_unknown_type(): void {
		tribe( PayPal_Handler::class )->process_event( $this->build_paypal_event( 'CUSTOMER.UNKNOWN' ) );

		$this->assert_logged_without_buyer_data();
	}

	/**
	 * @test
	 */
	public function it_should_not_log_the_body_of_a_paypal_event_for_an_order_already_on_its_status(): void {
		$post_id    = static::factory()->post->create();
		$ticket_id  = $this->create_tc_ticket( $post_id, 10 );
		$order      = $this->create_order( [ $ticket_id => 1 ] );
		$payment_id = 'paypal-payment-' . wp_generate_password( 8, false );
		update_post_meta( $order->ID, Order::$gateway_order_id_meta_key, $payment_id );
		clean_post_cache( $order->ID );
		$this->paypal_payment = [ 'id' => $payment_id ];

		tribe( PayPal_Handler::class )->process_event( $this->build_paypal_event( PayPal_Events::PAYMENT_CAPTURE_COMPLETED ) );

		$this->assert_logged_without_buyer_data();
	}

	/**
	 * @test
	 */
	public function it_should_not_log_the_body_of_a_paypal_webhook_of_an_unknown_type(): void {
		// The merchant check calls PayPal; the route behaviour after it is what is under test.
		$this->set_class_fn_return( PayPal_Merchant::class, 'is_active', true );
		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/commerce/paypal/webhook' );
		$request->set_body_params( $this->build_paypal_event( 'CUSTOMER.UNKNOWN' ) );

		tribe( PayPal_Webhook_Endpoint::class )->handle_request( $request );

		$this->assert_logged_without_buyer_data();
	}

	/**
	 * @test
	 */
	public function it_should_not_log_the_body_of_a_stripe_event_of_an_unknown_type(): void {
		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/commerce/stripe/webhook' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'id'     => 'evt_' . wp_generate_password( 8, false ),
					'object' => 'event',
					'type'   => 'customer.unknown',
					'data'   => [ 'object' => [ 'email' => $this->buyer_email ] ],
				]
			)
		);

		Stripe_Handler::process_webhook_response( $request, new WP_REST_Response() );

		$this->assert_logged_without_buyer_data();
	}

	/**
	 * Builds a PayPal webhook event that carries the buyer's email.
	 *
	 * @param string $event_type The event type.
	 *
	 * @return array{id: string, event_type: string, resource: array{payer: array{email_address: string}, links: list<array{rel: string, method: string, href: string, url: string}>}} The event.
	 */
	private function build_paypal_event( string $event_type ): array {
		return [
			'id'         => 'WH-' . wp_generate_password( 8, false ),
			'event_type' => $event_type,
			'resource'   => [
				'payer' => [ 'email_address' => $this->buyer_email ],
				'links' => [
					[
						'rel'    => 'parent_payment',
						'method' => 'GET',
						'href'   => 'https://api-m.paypal.com/v2/payments/captures/parent',
						'url'    => 'https://api-m.paypal.com/v2/payments/captures/parent',
					],
				],
			],
		];
	}

	/**
	 * Asserts something was logged, and none of it carries the buyer's data.
	 */
	private function assert_logged_without_buyer_data(): void {
		$this->assertNotEmpty( $this->logged, 'The event should still be logged.' );
		$this->assertStringNotContainsString( $this->buyer_email, implode( "\n", $this->logged ) );
	}
}
