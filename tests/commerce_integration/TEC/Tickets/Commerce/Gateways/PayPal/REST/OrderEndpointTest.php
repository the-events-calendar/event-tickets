<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\REST;

use Faker\Factory;
use Faker\Generator;
use TEC\Common\Monolog\Logger;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Pending_Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Denied;
use TEC\Tickets\Commerce\Status\Pending;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class OrderEndpointTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var Generator
	 */
	private $faker;

	/**
	 * The PayPal order ID the stubbed PayPal answers for.
	 *
	 * @var string
	 */
	private string $paypal_order_id;

	/**
	 * Requests sent to PayPal, as "METHOD path".
	 *
	 * @var string[]
	 */
	private array $requests = [];

	/**
	 * @before
	 */
	public function set_up_paypal_order(): void {
		$this->faker           = Factory::create();
		$this->paypal_order_id = strtoupper( $this->faker->bothify( '##?##?##?##?##?##?' ) );
	}

	/**
	 * @dataProvider order_item_name_provider
	 */
	public function test_format_order_item_name( string $text, string $expected ): void {
		$order_endpoint = tribe( Order_Endpoint::class );
		$result         = $order_endpoint->format_order_item_name( $text );
		$this->assertSame( $expected, $result );
	}

	public function order_item_name_provider() {
		yield "Short text should not be truncated" => [ "Short text", "Short text" ];
		yield "Text with exact max length should not be truncated" => [
			str_repeat( "A", 127 ),
			str_repeat( "A", 127 )
		];
		yield "Long text without spaces should be truncated at max length minus ellipsis" => [
			str_repeat( "A", 137 ),
			substr( str_repeat( "A", 137 ), 0, 124 ) . '...'
		];
		yield "Long text with spaces should be truncated at the last space within limit" => [
			"This is a very long text that exceeds the maximum character length and needs to be truncated. We need to extend the text a little bit longer.",
			"This is a very long text that exceeds the maximum character length and needs to be truncated. We need to extend the text a..."
		];
		yield "Empty text should not be altered" => [ "", "" ];
	}

	/**
	 * Shapes of `payment_source.card.authentication_result`, as the PayPal sandbox returns them.
	 *
	 * @return array<string,array{0:array{liability_shift:string,three_d_secure:array{enrollment_status:string,authentication_status?:string}}|null,1:bool}>
	 */
	public function authentication_result_provider(): array {
		return [
			'bank verification failed'                     => [
				[
					'liability_shift' => 'NO',
					'three_d_secure'  => [
						'enrollment_status'     => 'Y',
						'authentication_status' => 'N',
					],
				],
				false,
			],
			'enrolled card with no authentication status'  => [
				[
					'liability_shift' => 'NO',
					'three_d_secure'  => [ 'enrollment_status' => 'Y' ],
				],
				false,
			],
			'authentication outcome unknown'               => [
				[
					'liability_shift' => 'UNKNOWN',
					'three_d_secure'  => [ 'enrollment_status' => 'Y' ],
				],
				false,
			],
			'issuer unavailable with an unknown liability' => [
				[
					'liability_shift' => 'UNKNOWN',
					'three_d_secure'  => [ 'enrollment_status' => 'U' ],
				],
				false,
			],
			'bank verified the buyer'                      => [
				[
					'liability_shift' => 'POSSIBLE',
					'three_d_secure'  => [
						'enrollment_status'     => 'Y',
						'authentication_status' => 'Y',
					],
				],
				true,
			],
			'card not enrolled in 3D Secure'               => [
				[
					'liability_shift' => 'NO',
					'three_d_secure'  => [ 'enrollment_status' => 'N' ],
				],
				true,
			],
			'issuer unavailable with no liability shift'   => [
				[
					'liability_shift' => 'NO',
					'three_d_secure'  => [ 'enrollment_status' => 'U' ],
				],
				true,
			],
			'no 3D Secure ran'                             => [ null, true ],
		];
	}

	/**
	 * @dataProvider authentication_result_provider
	 */
	public function test_update_only_captures_an_authenticated_card( ?array $authentication_result, bool $captures ): void {
		$order_id = $this->create_pending_order();
		$this->stub_paypal( $this->approved_order( $authentication_result ) );

		$response = $this->make_endpoint()->handle_update_order( $this->make_update_request() );

		$this->assert_capture_outcome( $order_id, $response, $captures );
	}

	/**
	 * @dataProvider authentication_result_provider
	 */
	public function test_recheck_only_captures_an_authenticated_card( ?array $authentication_result, bool $captures ): void {
		$order_id = $this->create_pending_order();
		$this->stub_paypal( $this->approved_order( $authentication_result ) );

		$response = $this->make_endpoint()->handle_recheck_order( $this->paypal_order_id, get_post( $order_id ) );

		$this->assert_capture_outcome( $order_id, $response, $captures );
	}

	/**
	 * A buyer who closes the bank's verification leaves no authentication result on the PayPal order,
	 * only the card form hears about it.
	 *
	 * @return array<string,array{0:string,1:bool}>
	 */
	public function card_form_liability_shift_provider(): array {
		return [
			'buyer skipped the bank verification' => [ 'NO', false ],
			'authentication outcome unknown'      => [ 'UNKNOWN', false ],
			'bank verified the buyer'             => [ 'POSSIBLE', true ],
		];
	}

	/**
	 * @dataProvider card_form_liability_shift_provider
	 */
	public function test_update_uses_the_card_form_liability_shift_when_paypal_recorded_none( string $liability_shift, bool $captures ): void {
		$order_id = $this->create_pending_order();
		$this->stub_paypal( $this->approved_order( null ) );

		$request = $this->make_update_request();
		$request->set_param( 'liability_shift', $liability_shift );

		$response = $this->make_endpoint()->handle_update_order( $request );

		$this->assert_capture_outcome( $order_id, $response, $captures );
	}

	public function test_update_prefers_the_paypal_authentication_result_over_the_card_form(): void {
		$order_id = $this->create_pending_order();
		$this->stub_paypal(
			$this->approved_order(
				[
					'liability_shift' => 'NO',
					'three_d_secure'  => [
						'enrollment_status'     => 'Y',
						'authentication_status' => 'N',
					],
				]
			)
		);

		$request = $this->make_update_request();
		$request->set_param( 'liability_shift', 'POSSIBLE' );

		$response = $this->make_endpoint()->handle_update_order( $request );

		$this->assert_capture_outcome( $order_id, $response, false );
	}

	/**
	 * @return array<string,array{0:array{name:string,message:string}|WP_Error}>
	 */
	public function unreadable_order_lookup_provider(): array {
		return [
			'PayPal could not be reached'   => [ new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ) ],
			'PayPal answered with an error' => [
				[
					'name'    => 'RESOURCE_NOT_FOUND',
					'message' => 'The specified resource does not exist.',
				],
			],
		];
	}

	/**
	 * @dataProvider unreadable_order_lookup_provider
	 *
	 * @param array{name:string,message:string}|WP_Error $lookup What the PayPal order lookup answered.
	 */
	public function test_update_does_not_capture_an_order_it_could_not_read( $lookup ): void {
		$order_id = $this->create_pending_order();
		$this->stub_paypal( $lookup );

		$response = $this->make_endpoint()->handle_update_order( $this->make_update_request() );

		$this->assertNotContains( 'POST ' . $this->paypal_order_id . '/capture', $this->requests );
		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 'tec-tc-gateway-paypal-unverified-order', $response->get_error_code() );
		$this->assertEquals( tribe( Pending::class )->get_wp_slug(), get_post_status( $order_id ) );
	}

	public function test_update_settles_an_order_paypal_already_captured(): void {
		$order_id = $this->create_pending_order();
		$this->stub_paypal( $this->completed_order( $this->approved_order( null ) ) );

		$response = $this->make_endpoint()->handle_update_order( $this->make_update_request() );

		$this->assertNotContains( 'POST ' . $this->paypal_order_id . '/capture', $this->requests );
		$this->assertTrue( $response->get_data()['success'] );
		$this->assertEquals( tribe( Completed::class )->get_wp_slug(), get_post_status( $order_id ) );
	}

	public function test_recheck_reports_an_unconfirmed_capture_when_the_order_cannot_be_denied(): void {
		$order_id = $this->create_pending_order();
		/* A concurrent request holding the order lock is what makes the move to Denied fail. */
		tribe( Order::class )->lock_order( $order_id );
		$this->stub_paypal(
			$this->approved_order(
				[
					'liability_shift' => 'NO',
					'three_d_secure'  => [ 'enrollment_status' => 'Y' ],
				]
			)
		);

		$response = $this->make_endpoint()->handle_recheck_order( $this->paypal_order_id, get_post( $order_id ) );

		$this->assertNotContains( 'POST ' . $this->paypal_order_id . '/capture', $this->requests );
		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 'tec-tc-gateway-paypal-unconfirmed-capture', $response->get_error_code() );
		$this->assertEquals( tribe( Pending::class )->get_wp_slug(), get_post_status( $order_id ) );
	}

	public function test_recheck_does_not_capture_an_order_that_was_denied(): void {
		$order_id = $this->create_pending_order();
		/* The buyer closed the bank's verification, so PayPal holds the order approved with no authentication result. */
		$this->stub_paypal( $this->approved_order( null ) );
		$request = $this->make_update_request();
		$request->set_param( 'liability_shift', 'NO' );
		$this->make_endpoint()->handle_update_order( $request );

		$response = $this->make_endpoint()->handle_recheck_order( $this->paypal_order_id, get_post( $order_id ) );

		$this->assertNotContains( 'POST ' . $this->paypal_order_id . '/capture', $this->requests );
		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 'tec-tc-gateway-paypal-capture-declined', $response->get_error_code() );
		$this->assertEquals( tribe( Denied::class )->get_wp_slug(), get_post_status( $order_id ) );
	}

	/**
	 * @param int                       $order_id The Tickets Commerce order ID.
	 * @param WP_Error|WP_REST_Response $response What the endpoint answered.
	 * @param bool                      $captures Whether the card should have been captured.
	 */
	private function assert_capture_outcome( int $order_id, $response, bool $captures ): void {
		$capture_request = 'POST ' . $this->paypal_order_id . '/capture';

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
	 * @param array{liability_shift:string,three_d_secure:array{enrollment_status:string,authentication_status?:string}}|null $authentication_result
	 *
	 * @return array{id:string,status:string,payment_source:array{card:array{last_digits:string,authentication_result?:array{liability_shift:string,three_d_secure:array{enrollment_status:string,authentication_status?:string}}}},purchase_units:array<int,array{reference_id:string}>}
	 */
	private function approved_order( ?array $authentication_result ): array {
		$card = [ 'last_digits' => $this->faker->numerify( '####' ) ];

		if ( null !== $authentication_result ) {
			$card['authentication_result'] = $authentication_result;
		}

		return [
			'id'             => $this->paypal_order_id,
			'status'         => 'APPROVED',
			'payment_source' => [ 'card' => $card ],
			'purchase_units' => [ [ 'reference_id' => 'default' ] ],
		];
	}

	/**
	 * @param array{id:string,status:string,payment_source:array{card:array{last_digits:string,authentication_result?:array{liability_shift:string,three_d_secure:array{enrollment_status:string,authentication_status?:string}}}},purchase_units:array<int,array{reference_id:string}>} $approved The approved order.
	 *
	 * @return array{id:string,status:string,payment_source:array{card:array{last_digits:string,authentication_result?:array{liability_shift:string,three_d_secure:array{enrollment_status:string,authentication_status?:string}}}},purchase_units:array<int,array{payments:array{captures:array<int,array{id:string,status:string,final_capture:bool,update_time:string}>}}>} The order with one completed capture.
	 */
	private function completed_order( array $approved ): array {
		$approved['status']         = 'COMPLETED';
		$approved['purchase_units'] = [
			[
				'payments' => [
					'captures' => [
						[
							'id'            => strtoupper( $this->faker->bothify( '##?##?##?##?##?##?' ) ),
							'status'        => 'COMPLETED',
							'final_capture' => true,
							'update_time'   => $this->faker->dateTime()->format( 'c' ),
						],
					],
				],
			],
		];

		return $approved;
	}

	/**
	 * Answers the PayPal order lookup with `$lookup` and a capture with the order completed.
	 *
	 * @param array|WP_Error $lookup What the order lookup answers.
	 */
	private function stub_paypal( $lookup ): void {
		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) use ( $lookup ) {
				if ( false === strpos( $url, '/v2/checkout/orders/' ) ) {
					return $pre;
				}

				$method           = strtoupper( $args['method'] ?? 'GET' );
				$path             = substr( $url, strpos( $url, '/v2/checkout/orders/' ) + strlen( '/v2/checkout/orders/' ) );
				$this->requests[] = $method . ' ' . $path;

				$body = 'POST' === $method ? $this->completed_order( $this->approved_order( null ) ) : $lookup;

				if ( is_wp_error( $body ) ) {
					return $body;
				}

				return [
					'headers'  => [],
					'body'     => wp_json_encode( $body ),
					'response' => [
						'code'    => isset( $body['name'] ) ? 404 : 200,
						'message' => 'OK',
					],
					'cookies'  => [],
					'filename' => null,
				];
			},
			10,
			3
		);
	}

	private function make_update_request(): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/tec-tickets/v1/commerce/paypal/order/' . $this->paypal_order_id );
		$request->set_param( 'order_id', $this->paypal_order_id );
		$request->set_param( 'advanced_payment', true );

		return $request;
	}

	private function create_pending_order(): int {
		$order_id = static::factory()->post->create(
			[
				'post_type'   => Order::POSTTYPE,
				'post_status' => tribe( Pending::class )->get_wp_slug(),
				'post_title'  => $this->faker->sentence( 3 ),
			]
		);

		update_post_meta( $order_id, Order::$gateway_order_id_meta_key, $this->paypal_order_id );

		return $order_id;
	}

	private function make_endpoint(): Order_Endpoint {
		return new Order_Endpoint( new Pending_Order( tribe( Cart::class ), new Logger( 'test' ) ), tribe( Cart::class ) );
	}
}
