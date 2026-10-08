<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\REST;

use Codeception\TestCase\WPTestCase;
use TEC\Common\Monolog\Logger;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Pending_Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Denied;
use TEC\Tickets\Commerce\Status\Pending;
use WP_Error;
use WP_REST_Request;

class Order_Endpoint_3DS_Test extends WPTestCase {

	private const PAYPAL_ORDER_ID = 'PAYPAL-3DS-ORDER';

	/**
	 * Requests sent to PayPal, as "METHOD url".
	 *
	 * @var string[]
	 */
	private array $requests = [];

	/**
	 * @after
	 */
	public function remove_paypal_stub(): void {
		remove_all_filters( 'pre_http_request' );
	}

	/**
	 * @return array<string,array{0:array{liability_shift:string,three_d_secure:array{enrollment_status:string,authentication_status?:string}}|null,1:bool}>
	 */
	public function authentication_result_provider(): array {
		return [
			'buyer cancelled the bank verification' => [
				[
					'liability_shift' => 'NO',
					'three_d_secure'  => [ 'enrollment_status' => 'Y' ],
				],
				false,
			],
			'bank verification failed'              => [
				[
					'liability_shift' => 'NO',
					'three_d_secure'  => [
						'enrollment_status'     => 'Y',
						'authentication_status' => 'N',
					],
				],
				false,
			],
			'authentication outcome unknown'        => [
				[
					'liability_shift' => 'UNKNOWN',
					'three_d_secure'  => [ 'enrollment_status' => 'Y' ],
				],
				false,
			],
			'bank verified the buyer'               => [
				[
					'liability_shift' => 'POSSIBLE',
					'three_d_secure'  => [
						'enrollment_status'     => 'Y',
						'authentication_status' => 'Y',
					],
				],
				true,
			],
			'card not enrolled in 3D Secure'        => [
				[
					'liability_shift' => 'NO',
					'three_d_secure'  => [ 'enrollment_status' => 'N' ],
				],
				true,
			],
			'no 3D Secure ran'                      => [ null, true ],
		];
	}

	/**
	 * @dataProvider authentication_result_provider
	 */
	public function test_update_only_captures_an_authenticated_card( ?array $authentication_result, bool $captures ): void {
		$order_id = $this->create_pending_order();
		$this->stub_paypal( $authentication_result );

		$request = new WP_REST_Request( 'POST', '/tec-tickets/v1/commerce/paypal/order/' . self::PAYPAL_ORDER_ID );
		$request->set_param( 'order_id', self::PAYPAL_ORDER_ID );
		$request->set_param( 'advanced_payment', true );

		$response = $this->make_endpoint()->handle_update_order( $request );

		$this->assert_capture_outcome( $order_id, $response, $captures );
	}

	/**
	 * @dataProvider authentication_result_provider
	 */
	public function test_recheck_only_captures_an_authenticated_card( ?array $authentication_result, bool $captures ): void {
		$order_id = $this->create_pending_order();
		$this->stub_paypal( $authentication_result );

		$response = $this->make_endpoint()->handle_recheck_order( self::PAYPAL_ORDER_ID, get_post( $order_id ) );

		$this->assert_capture_outcome( $order_id, $response, $captures );
	}

	private function assert_capture_outcome( int $order_id, $response, bool $captures ): void {
		$capture_request = 'POST ' . self::PAYPAL_ORDER_ID . '/capture';

		if ( $captures ) {
			$this->assertContains( $capture_request, $this->requests );
			$this->assertEquals( tribe( Completed::class )->get_wp_slug(), get_post_status( $order_id ) );

			return;
		}

		$this->assertNotContains( $capture_request, $this->requests );
		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 'tec-tc-gateway-paypal-failed-authentication', $response->get_error_code() );
		$this->assertEquals( tribe( Denied::class )->get_wp_slug(), get_post_status( $order_id ) );
	}

	/**
	 * Answers the PayPal order lookup with an approved card order and the capture with a completed one.
	 */
	private function stub_paypal( ?array $authentication_result ): void {
		$card = [ 'last_digits' => '1091' ];

		if ( null !== $authentication_result ) {
			$card['authentication_result'] = $authentication_result;
		}

		$approved = [
			'id'             => self::PAYPAL_ORDER_ID,
			'status'         => 'APPROVED',
			'payment_source' => [ 'card' => $card ],
			'purchase_units' => [ [ 'reference_id' => 'default' ] ],
		];

		$completed                   = $approved;
		$completed['status']         = 'COMPLETED';
		$completed['purchase_units'] = [
			[
				'payments' => [
					'captures' => [
						[
							'id'            => 'CAPTURE-1',
							'status'        => 'COMPLETED',
							'final_capture' => true,
							'update_time'   => gmdate( 'c' ),
						],
					],
				],
			],
		];

		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) use ( $approved, $completed ) {
				if ( false === strpos( $url, '/v2/checkout/orders/' ) ) {
					return $pre;
				}

				$method           = strtoupper( $args['method'] ?? 'GET' );
				$path             = substr( $url, strpos( $url, '/v2/checkout/orders/' ) + strlen( '/v2/checkout/orders/' ) );
				$this->requests[] = $method . ' ' . $path;

				return [
					'headers'  => [],
					'body'     => wp_json_encode( 'POST' === $method ? $completed : $approved ),
					'response' => [ 'code' => 200, 'message' => 'OK' ],
					'cookies'  => [],
					'filename' => null,
				];
			},
			10,
			3
		);
	}

	private function create_pending_order(): int {
		$order_id = wp_insert_post(
			[
				'post_type'   => Order::POSTTYPE,
				'post_status' => tribe( Pending::class )->get_wp_slug(),
				'post_title'  => 'PayPal 3DS order',
			]
		);

		update_post_meta( $order_id, Order::$gateway_order_id_meta_key, self::PAYPAL_ORDER_ID );

		return $order_id;
	}

	private function make_endpoint(): Order_Endpoint {
		return new Order_Endpoint( new Pending_Order( tribe( Cart::class ), new Logger( 'test' ) ), tribe( Cart::class ) );
	}
}
