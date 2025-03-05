<?php

declare( strict_types=1 );

namespace TEC\Tickets\Tests\Order_Modifiers_Integration\Checkout;


use PHPUnit\Framework\Assert;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Cart as Commerce_Cart;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Coupons;
use TEC\Tickets\Commerce\Shortcodes\Checkout_Shortcode;
use TEC\Tickets\Commerce\Traits\Type;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Coupon_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Reservations_Maker;
use Tribe\Tickets\Test\Traits\With_No_Object_Storage;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe\Tests\Tickets\Traits\Tribe_URL;

class Coupons_Test extends Controller_Test_Case {

	use Attendee_Maker;
	use Coupon_Creator;
	use Order_Maker;
	use Reservations_Maker;
	use Series_Pass_Factory;
	use SnapshotAssertions;
	use Ticket_Maker;
	use Tribe_URL;
	use Type;
	use With_No_Object_Storage;
	use With_Tickets_Commerce;
	use With_Uopz;

	protected string $controller_class = Coupons::class;

	/**
	 * @test
	 */
	public function it_should_not_store_objects() {
		$this->make_controller()->register();

		$post      = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post, 10 );
		$coupon    = $this->create_coupon();

		$order = $this->create_order(
			[
				$ticket_id  => 1,
				$coupon->id => [
					'quantity' => 1,
					'extras'   => [ 'type' => 'coupon' ],
				],
			]
		);

		$this->assert_no_object_stored( get_post_meta( $order->ID ) );
	}

	/**
	 * @test
	 */
	public function it_should_calculate_the_order_total_correctly() {
		// Register the controller.
		$this->make_controller()->register();

		// Set up our test objects.
		$post      = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post, 10 );
		$coupon_1  = $this->create_coupon();
		$coupon_2  = $this->create_coupon( [ 'sub_type' => 'flat', 'raw_amount' => 2 ] );

		$order = $this->create_order(
			[
				$ticket_id    => 1,
				$coupon_1->id => [
					'id'       => $this->get_unique_type_id( $coupon_1->id, 'coupon' ),
					'quantity' => 1,
					'extras'   => [ 'type' => 'coupon' ],
				],
			]
		);

		/** @var Value $total */
		$total = $order->total_value;

		Assert::assertEquals( 9.0, $total->get_float() );
		Assert::assertEquals( 1, count( $order->items ) );
		Assert::assertEquals( 1, count( $order->coupons ) );

		$order_2 = $this->create_order(
			[
				$ticket_id    => 1,
				$coupon_2->id => [
					'id'       => $this->get_unique_type_id( $coupon_2->id, 'coupon' ),
					'quantity' => 1,
					'extras'   => [ 'type' => 'coupon' ],
				],
			]
		);

		/** @var Value $total */
		$total = $order_2->total_value;

		Assert::assertEquals( 8.0, $total->get_float() );
		Assert::assertEquals( 1, count( $order_2->items ) );
		Assert::assertEquals( 1, count( $order_2->coupons ) );
	}

	/**
	 * @test
	 */
	public function it_should_calculate_fees_and_store_them_correctly_simple_math() {
		$post        = static::factory()->post->create( [ 'post_title' => 'The Coupon Event' ] );
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );
		$ticket_id_3 = $this->create_tc_ticket( $post, 30 );
		$ticket_id_4 = $this->create_tc_ticket( $post, 40 );
		$ticket_id_5 = $this->create_tc_ticket( $post, 50 );

		// 10% off coupon.
		$coupon_1 = $this->create_coupon(
			[
				'raw_amount' => 10,
				'sub_type'   => 'percent',
			]
		);

		// $3 off coupon.
		$coupon_2 = $this->create_coupon(
			[
				'raw_amount' => 3,
				'sub_type'   => 'flat',
			]
		);

		// Basic checks to ensure the coupon is calculating values correctly.
		Assert::assertEquals( -9.0, $coupon_1->get_discount_amount( 90 ), '10% of 90 should be 9' );
		Assert::assertEquals( -80.0, $coupon_1->get_discount_amount( 800 ), '10% of 800 should be 80' );

		Assert::assertEquals( -3.0, $coupon_2->get_discount_amount( 90 ), '3 off 90 should be 3' );
		Assert::assertEquals( -3.0, $coupon_2->get_discount_amount( 800 ), '3 off 800 should be 3' );

		// Register the controller.
		$this->make_controller()->register();

		// Get the cart and start adding tickets.
		/** @var Commerce_Cart $cart */
		$cart = tribe( Commerce_Cart::class );
		$cart->add_ticket( $ticket_id_1, 2 );
		$cart->add_ticket( $ticket_id_2, 3 );
		$cart->add_ticket( $ticket_id_3, 4 );
		$cart->add_ticket( $ticket_id_4, 5 );
		$cart->add_ticket( $ticket_id_5, 6 );

		// Grab the total and subtotal.
		$cart_subtotal = $cart->get_cart_subtotal();
		$cart_total    = $cart->get_cart_total();

		// With only tickets in the cart and no coupons applied, the total and subtotal should be the same.
		Assert::assertEquals( $cart_subtotal, $cart_total );

		// Cart subtotal should be (10 * 2) + (20 * 3) + (30 * 4) + (40 * 5) + (50 * 6) = 20 + 60 + 120 + 200 + 300 = 700.
		Assert::assertEquals( 700.0, $cart_subtotal );

		$order1 = $this->create_order_from_cart( $cart );

		// Validate that the order has the correct amounts.
		Assert::assertCount( 5, $order1->items, 'Order should have 5 different tickets' );
		Assert::assertObjectHasAttribute( 'coupons', $order1, 'Order object should have coupons property' );
		Assert::assertCount( 0, $order1->coupons, 'Coupons should be empty when no coupons added' );
		Assert::assertEquals( 700.0, $order1->subtotal->get_float() );
		Assert::assertEquals( 700.0, $order1->total_value->get_float() );

		// Let's add a coupon and create a new order.
		$coupon_1->add_to_cart( $cart->get_repository() );

		// Grab the total and subtotal.
		$cart_subtotal = $cart->get_cart_subtotal();
		$cart_total    = $cart->get_cart_total();

		// Cart subtotal should be (10 * 2) + (20 * 3) + (30 * 4) + (40 * 5) + (50 * 6) = 20 + 60 + 120 + 200 + 300 = 700.
		Assert::assertEquals( 700.0, $cart_subtotal );

		// Cart total should be 700 - 10% = 700 - 70 = 630.
		Assert::assertEquals( 630.0, $cart_total );

		$order2 = $this->create_order_from_cart( $cart );

		// Validate that the order has the correct amounts.
		Assert::assertCount( 5, $order2->items, 'Order should have 5 different tickets' );
		Assert::assertObjectHasAttribute( 'coupons', $order2, 'Order object should have coupons property' );
		Assert::assertCount( 1, $order2->coupons, 'Coupons should have 1 coupon' );
		Assert::assertEquals( 700.0, $order2->subtotal->get_float() );
		Assert::assertEquals( 630.0, $order2->total_value->get_float(), 'Order should be discounted by 10% ($70)' );

		// Inspect the cart HTML to ensure the coupon is displayed.
		$this->set_fn_return( 'wp_create_nonce', esc_attr( __METHOD__ ) );
		$this->assertMatchesHtmlSnapshot(
			ltrim(
				preg_replace(
					'#<link rel=(.*)/>#',
					'',
					str_replace(
						[ $post, $ticket_id_1, $ticket_id_2, $ticket_id_3, $ticket_id_4, $ticket_id_5, $coupon_1->id ],
						[ '{POST_ID}', '{TICKET_ID_1}', '{TICKET_ID_2}', '{TICKET_ID_3}', '{TICKET_ID_4}', '{TICKET_ID_5}', '{COUPON_ID_1}' ],
						tribe( Checkout_Shortcode::class )->get_html()
					)
				)
			)
		);
	}
}
