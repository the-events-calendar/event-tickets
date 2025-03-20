<?php

declare( strict_types=1 );

namespace TEC\Tickets\Tests\Order_Modifiers_Integration\Checkout\Gateway\PayPal;

use PHPUnit\Framework\Assert;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Cart as Commerce_Cart;
use TEC\Tickets\Commerce\Gateways\Manager;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway as PayPalGateway;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\PayPal\Coupons;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Coupon;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Coupon_Creator;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Fee_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\PayPal_REST_Override;

/**
 * Class Coupons_Test
 *
 * @since TBD
 */
class Coupons_Test extends Controller_Test_Case {

	use Coupon_Creator;
	use Fee_Creator;
	use PayPal_REST_Override;
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

	/**
	 * @test
	 */
	public function it_should_modify_paypal_data_with_coupons_and_fees() {
		$this->make_controller()->register();

		/**
		 * @var int    $ticket_id
		 * @var Coupon $coupon
		 */
		[ $ticket_id, $coupon ] = $this->create_the_things();

		$fee_for_all    = $this->create_fee_for_all( [ 'raw_amount' => 10, 'sub_type' => 'percent' ] );
		$fee_per_ticket = $this->create_fee_for_ticket( $ticket_id, [ 'raw_amount' => 2, 'sub_type' => 'percent' ] );
		$this->add_fee_to_ticket( $fee_per_ticket, $ticket_id );

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
		Assert::assertCount( 3, $purchase_unit['items'], 'There should be a ticketand 2 fees in the unit data' );
		foreach ( $purchase_unit['items'] as $item ) {
			Assert::assertEquals( 2, $item['quantity'], 'The quantity should be 2' );
		}

		Assert::assertArrayHasKey( 'breakdown', $purchase_unit['amount'], 'The amount should have a breakdown of items.' );
		Assert::assertArrayHasKey( 'item_total', $purchase_unit['amount']['breakdown'], 'The breakdown should have an item total' );
		Assert::assertArrayHasKey( 'discount', $purchase_unit['amount']['breakdown'], 'The breakdown should have a discount' );

		/*
		 * Make sure each item is correct.
		 *
		 * The fee is calculated against an individual item, rather than the total quantity
		 * of items. So the fee for 10% of the item total should be 1.13, which is then
		 * multiplied by 2 to get 2.26.
		 *
		 * The fee for 2% of the item total should be 0.23, which is then multiplied by 2 to
		 * get 0.46. Compare this with 2% of the total item total, which would be 0.45.
		 */

		// The item total should be 11.28 * 2 = 22.56 (as a string).
		Assert::assertEquals( '22.56', $purchase_unit['items'][0]['item_total']['value'] );

		// The fee for all should be 10% of the item total. 11.28 * .1 = 1.13, * 2 = 2.26 (as a string).
		Assert::assertEquals( '2.26', $purchase_unit['items'][2]['item_total']['value'] );

		// The fee per ticket should be 2% of the item total. 11.28 * .02 = 0.23, * 2 = .46 (as a string).
		Assert::assertEquals( '0.46', $purchase_unit['items'][1]['item_total']['value'] );

		// The item total should be 22.56 + 2.26 + .46 = 25.28 (as a string).
		Assert::assertEquals( '25.28', $purchase_unit['amount']['breakdown']['item_total']['value'] );

		// The discount should be 17.3% * 2 * 11.28 = 3.90 (as a string).
		Assert::assertEquals( '3.90', $purchase_unit['amount']['breakdown']['discount']['value'] );

		// The total value should match the item total minus the discount. 25.28 - 3.90 = 21.38 (as a string).
		Assert::assertEquals( '21.38', $purchase_unit['amount']['value'], 'The total value should equal item_total minus discount' );
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
}
