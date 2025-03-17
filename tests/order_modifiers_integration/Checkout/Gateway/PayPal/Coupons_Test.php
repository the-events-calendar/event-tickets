<?php

declare( strict_types=1 );

namespace TEC\Tickets\Tests\Order_Modifiers_Integration\Checkout\Gateway\PayPal;

use PHPUnit\Framework\Assert;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Cart as Commerce_Cart;
use TEC\Tickets\Commerce\Gateways\Manager;
use TEC\Tickets\Commerce\Gateways\PayPal\Client;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway as PayPalGateway;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\PayPal\Coupons;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Coupon;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Coupon_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_REST_Request as Request;

/**
 * Class Coupons_Test
 *
 * @since TBD
 */
class Coupons_Test extends Controller_Test_Case {

	use Coupon_Creator;
	use Ticket_Maker;
	use With_Uopz;

	protected string $controller_class = Coupons::class;

	/**
	 * @before
	 */
	public function set_gateway_to_paypal() {
		$this->set_class_fn_return( Manager::class, 'get_current_gateway', PayPalGateway::get_key() );
	}

	/**
	 * @test
	 */
	public function it_should_not_modify_data_with_no_coupons() {
		$this->make_controller()->register();

		/** @var int $ticket_id */
		[ $ticket_id ] = $this->create_the_things();

		// Get the cart and start adding tickets.
		/** @var Commerce_Cart $cart */
		$cart = tribe( Commerce_Cart::class );
		$cart->add_ticket( $ticket_id, 2 );

		// Create the order using the REST API, because that's how it will work on a live site.
		[ $path, $query_args, $args ] = $this->create_order_via_rest();

		// Verify the data from the request.
		Assert::assertEquals( '/v2/checkout/orders', $path );
		Assert::assertEmpty( $query_args );
		Assert::assertTrue( isset( $args['body']['purchase_units'][0] ) );

		$purchase_unit = $args['body']['purchase_units'][0];
		Assert::assertCount( 1, $purchase_unit['items'], 'There should be a single item in the unit data' );
		Assert::assertEquals( 2, $purchase_unit['items'][0]['quantity'], 'The quantity should be 2' );
		Assert::assertArrayHasKey( 'breakdown', $purchase_unit['amount'], 'The amount should have a breakdown of items.' );
		Assert::assertArrayHasKey( 'item_total', $purchase_unit['amount']['breakdown'], 'The breakdown should have an item total' );
		Assert::assertArrayNotHasKey( 'discount', $purchase_unit['amount']['breakdown'], 'The breakdown should not have a discount' );

		// The item total should be 11.28 * 2 = 22.56 (as a string).
		Assert::assertEquals( '22.56', $purchase_unit['amount']['breakdown']['item_total']['value'] );
	}

	/**
	 * @test
	 */
	public function it_should_modify_paypal_unit_data() {
		$this->make_controller()->register();

		/**
		 * @var int    $ticket_id
		 * @var Coupon $coupon
		 */
		[ $ticket_id, $coupon ] = $this->create_the_things();

		// Add the ticket and coupon to the cart.
		/** @var Commerce_Cart $cart */
		$cart = tribe( Commerce_Cart::class );
		$cart->add_ticket( $ticket_id, 2 );
		$coupon->add_to_cart( $cart->get_repository() );

		// Create the order using the REST API, because that's how it will work on a live site.
		[ $path, $query_args, $args ] = $this->create_order_via_rest();

		// Verify the data from the request.
		Assert::assertEquals( '/v2/checkout/orders', $path );
		Assert::assertEmpty( $query_args );
		Assert::assertTrue( isset( $args['body']['purchase_units'][0] ) );

		$purchase_unit = $args['body']['purchase_units'][0];
		Assert::assertCount( 1, $purchase_unit['items'], 'There should be a single item in the unit data' );
		Assert::assertEquals( 2, $purchase_unit['items'][0]['quantity'], 'The quantity should be 2' );
		Assert::assertArrayHasKey( 'breakdown', $purchase_unit['amount'], 'The amount should have a breakdown of items.' );
		Assert::assertArrayHasKey( 'item_total', $purchase_unit['amount']['breakdown'], 'The breakdown should have an item total' );
		Assert::assertArrayHasKey( 'discount', $purchase_unit['amount']['breakdown'], 'The breakdown should have a discount' );

		// The item total should be 11.28 * 2 = 22.56 (as a string).
		Assert::assertEquals( '22.56', $purchase_unit['amount']['breakdown']['item_total']['value'] );

		// The discount should be 17.3% * 2 * 11.28 = 3.90 (as a string).
		Assert::assertEquals( '3.90', $purchase_unit['amount']['breakdown']['discount']['value'] );

		// The total value should match the item total minus the discount. 22.56 - 3.90 = 18.66 (as a string).
		Assert::assertEquals( '18.66', $purchase_unit['amount']['value'], 'The total value should equal item_total minus discount' );
	}

	protected function create_the_things(): array {
		$post = static::factory()->post->create( [ 'post_title' => 'The Event' ] );

		// Create a ticket.
		$ticket_id = $this->create_tc_ticket( $post, 11.28 );

		// Create a 17.3% off coupon.
		$coupon = $this->create_coupon(
			[
				'raw_amount' => 17.3,
				'sub_type'   => 'percent',
			]
		);

		return [ $ticket_id, $coupon ];
	}

	protected function create_order_via_rest(): array {
		$request = new Request( 'POST', '/tribe/tickets/v1/commerce/paypal/order' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			json_encode(
				[
					'purchaser' => [
						'name'  => 'John Doe',
						'email' => 'johndoe@example.com',
					],
				]
			)
		);

		// Set up a function to replace Client::post(), and to return the data passed to it.
		$fake_id = 'PAY-1234567890';
		$this->set_class_fn_return(
			Client::class,
			'post',
			static function () use ( &$spy_data, $fake_id ) {
				$spy_data = func_get_args();

				return [
					'id'          => $fake_id,
					'create_time' => '2200-01-01T00:00:00Z',
				];
			},
			true
		);

		$response = rest_do_request( $request );
		Assert::assertEquals( 200, $response->get_status() );
		Assert::assertEquals( $fake_id, $response->get_data()['id'] );

		return $spy_data;
	}
}
