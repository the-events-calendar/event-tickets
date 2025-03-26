<?php

namespace TEC\Tickets\Commerce\Order_Modifiers\API;

use Closure;
use Generator;
use PHPUnit\Framework\Assert;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Cart\Abstract_Cart;
use TEC\Tickets\Commerce\Gateways\Stripe\Payment_Intent;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Coupon;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Coupons as CouponsRepository;
use TEC\Tickets\Commerce\Values\Currency_Value;
use Tribe\Tests\Traits\With_Clock_Mock;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Coupon_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_Error;
use WP_REST_Request as Request;
use WP_REST_Response as Response;
use WP_REST_Server as Server;

class Coupons_Test extends Controller_Test_Case {

	use Coupon_Creator;
	use Ticket_Maker;
	use With_Clock_Mock;
	use With_Uopz;

	protected string $controller_class = Coupons::class;

	/**
	 * @dataProvider rest_endpoints_data_provider
	 * @test
	 */
	public function it_should_provide_expected_responses( Closure $fixture, ?Closure $post_checks = null ) {
		$this->make_controller()->register();
		[ $path, $should_fail, $method, $data, $code ] = $fixture();
		$result = $this->assert_endpoint( $path, $method, $should_fail, $data, $code );

		if ( null !== $post_checks ) {
			$post_checks( $result );
		}
	}

	public function rest_endpoints_data_provider(): Generator {
		yield 'coupons archive -> unauthorized' => [
			function () {
				$this->create_data();

				return [
					'/coupons',
					true,
					'GET',
					[],
					401,
				];
			},
		];

		$coupon_15_percent = function ( $reset = false ): Coupon {
			static $coupon = null;
			if ( null === $coupon || $reset ) {
				$coupon = $this->create_coupon( [ 'raw_amount' => 15 ] );
			}

			return $coupon;
		};

		yield 'apply coupon -> valid response' => [
			function () use ( $coupon_15_percent ) {
				$coupon_15_percent( true );
				$cart = $this->set_up_cart_with_ticket();
				Assert::assertCount( 0, $cart->get_items_in_cart( false, 'coupon' ) );

				// Set up the fake payment intent update handler.
				$this->set_class_fn_return( Payment_Intent::class, 'update', true );

				return [
					'/coupons/apply',
					false,
					'POST',
					[
						'coupon'            => $coupon_15_percent()->slug,
						'cart_hash'         => $cart->get_hash(),
						'payment_intent_id' => 'fake-payment-intent-id',
					],
					200,
				];
			},
			function ( Response $response ) use ( $coupon_15_percent ) {
				// Check that the coupon was applied to the cart.
				/** @var Abstract_Cart $cart */
				$cart = tribe( Cart::class )->get_repository();

				Assert::assertCount( 2, $cart->get_items_in_cart( false, 'all' ) );
				Assert::assertCount( 1, $cart->get_items_in_cart( false, 'coupon' ) );

				// Check that the response has the correct data.
				$data = $response->get_data();
				Assert::assertArrayHasKey( 'success', $data );
				Assert::assertArrayHasKey( 'discount', $data );
				Assert::assertArrayHasKey( 'label', $data );
				Assert::assertArrayHasKey( 'message', $data );
				Assert::assertArrayHasKey( 'cartAmount', $data );
				Assert::assertArrayHasKey( 'doReload', $data );

				// Check that the data has been generated correctly.
				Assert::assertTrue( $data['success'] );
				Assert::assertSame( '- &#x24;1.50', $data['discount'] );
				Assert::assertSame( $coupon_15_percent()->slug, $data['label'] );
				Assert::assertSame(
					esc_html(
						sprintf(
							'Coupon "%s" applied successfully.',
							$coupon_15_percent()->slug,
						)
					),
					$data['message']
				);
				Assert::assertSame(
					Currency_Value::create_from_float( $cart->get_cart_total() )->get(),
					$data['cartAmount']
				);
			},
		];

		yield 'Remove coupon – valid response' => [
			function () use ( $coupon_15_percent ) {
				$coupon_15_percent( true );
				$cart = $this->set_up_cart_with_ticket();
				$coupon_15_percent()->add_to_cart( $cart );
				Assert::assertCount( 1, $cart->get_items_in_cart( false, 'coupon' ) );

				// Set up the fake payment intent update handler.
				$this->set_class_fn_return( Payment_Intent::class, 'update', true );

				return [
					'/coupons/remove',
					false,
					'POST',
					[
						'coupon'            => $coupon_15_percent()->slug,
						'cart_hash'         => $cart->get_hash(),
						'payment_intent_id' => 'fake-payment-intent-id',
					],
					200,
				];
			},
			function ( Response $response ) use ( $coupon_15_percent ) {
				// Check that the coupon was applied to the cart.
				/** @var Abstract_Cart $cart */
				$cart = tribe( Cart::class )->get_repository();

				Assert::assertCount( 1, $cart->get_items_in_cart( false, 'all' ) );
				Assert::assertCount( 0, $cart->get_items_in_cart( false, 'coupon' ) );

				// Check that the response has the correct data.
				$data = $response->get_data();
				Assert::assertArrayHasKey( 'success', $data );
				Assert::assertArrayHasKey( 'message', $data );
				Assert::assertArrayHasKey( 'cartAmount', $data );
				Assert::assertArrayHasKey( 'doReload', $data );

				// Check that the data has been generated correctly.
				Assert::assertTrue( $data['success'] );
				Assert::assertSame(
					esc_html(
						sprintf(
							'Coupon "%s" removed successfully.',
							$coupon_15_percent()->slug,
						)
					),
					$data['message']
				);
				Assert::assertSame(
					Currency_Value::create_from_float( $cart->get_cart_total() )->get(),
					$data['cartAmount']
				);
			},
		];

		yield 'It should not error with no payment intent' => [
			function () use ( $coupon_15_percent ) {
				$coupon_15_percent( true );
				$cart = $this->set_up_cart_with_ticket();
				Assert::assertCount( 0, $cart->get_items_in_cart( false, 'coupon' ) );

				$coupon_15_percent()->add_to_cart( $cart );
				Assert::assertCount( 1, $cart->get_items_in_cart( false, 'coupon' ) );

				return [
					'/coupons/apply',
					false,
					'POST',
					[
						'coupon'    => $coupon_15_percent()->slug,
						'cart_hash' => $cart->get_hash(),
					],
					200,
				];
			},
		];
	}

	protected function create_data() {
		// Create 10 coupons that are flat rate discounts.
		$coupons = $this->create_coupons(
			10,
			[
				'sub_type'   => 'flat',
				'raw_amount' => 1,
			]
		);

		// Create 10 more coupons that are percentage discounts.
		$coupons = array_merge( $coupons, $this->create_coupons( 10 ) );

		Assert::assertSame( 20, count( $coupons ) );

		// Sanity check: Ensure that querying the DB shows the same number.
		$repo   = tribe( CouponsRepository::class );
		$result = $repo->get_modifiers(
			[
				'limit' => -1,
			],
			false
		);

		Assert::assertSame( 20, count( $result ) );

		return $coupons;
	}

	protected function assert_endpoint(
		string $path,
		string $method = Server::READABLE,
		bool $should_fail = false,
		array $data = [],
		int $response_code = 400
	) {
		$response = $this->do_rest_api_request( $path, $method, $data );

		if ( $should_fail ) {
			Assert::assertTrue( $response->is_error(), "Expected an error response for path: {$path}" );
			Assert::assertInstanceof( WP_Error::class, $response->as_error() );
			Assert::assertSame( $response_code, $response->get_status() );

			return $response;
		}

		Assert::assertGreaterThanOrEqual( 200, $response->get_status(), 'A response code should be returned >= 200' );
		Assert::assertSame( $response_code, $response->get_status(), "A {$response_code} response code should be returned" );
		Assert::assertFalse( $response->is_error(), "Expected a successful response for path: {$path}" );
		Assert::assertNull( $response->as_error() );

		return $response;
	}

	protected function do_rest_api_request(
		$path,
		$method = Server::READABLE,
		array $data = []
	): Response {
		// Set up the request object.
		$request = new Request( $method, "/tribe/tickets/v1{$path}" );
		$request->set_param( 'context', 'view' );

		if ( ! empty( $data ) ) {
			$request->set_body_params( $data );
		}

		return rest_do_request( $request );
	}

	protected function set_up_cart_with_ticket() {
		// Create an event.
		$event_id = self::factory()->post->create( [ 'post_title' => 'The Event' ] );

		// Create a ticket.
		$ticket = $this->create_tc_ticket( $event_id, 10 );

		// Set up the cart with the ticket.
		$commerce_cart = tribe( Cart::class );
		$commerce_cart->set_cart_hash( 'fake-cart-hash' );

		/** @var Abstract_Cart $cart */
		$cart = $commerce_cart->get_repository();

		// Ensure we have the same cart object by checking the hash.
		Assert::assertSame( 'fake-cart-hash', $cart->get_hash() );

		$cart->upsert_item( $ticket, 1 );

		Assert::assertCount( 1, $cart->get_items_in_cart( false, 'all' ) );

		return $cart;
	}
}
