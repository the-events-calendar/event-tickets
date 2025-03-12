<?php

declare( strict_types=1 );

namespace TEC\Tickets\Tests\Order_Modifiers_Integration\Checkout;

use PHPUnit\Framework\Assert;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Cart as Commerce_Cart;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Coupons;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Coupons as Coupon_Trait;
use TEC\Tickets\Commerce\Shortcodes\Checkout_Shortcode;
use TEC\Tickets\Commerce\Status\Action_Required;
use TEC\Tickets\Commerce\Status\Approved;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Created;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Refunded;
use TEC\Tickets\Commerce\Status\Status_Handler;
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
	use Coupon_Trait;
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
	public function it_should_calculate_coupons_simple_math() {
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

		$order1 = $this->create_order_from_cart();

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

		$order2 = $this->create_order_from_cart();

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

	/**
	 * @test
	 */
	public function it_should_calculate_coupons_complex_math() {
		$post = static::factory()->post->create(
			[ 'post_title' => 'The Event' ],
		);

		// Create a bunch of tickets.
		$ticket_id_1 = $this->create_tc_ticket( $post, 11.28 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 22.56 );
		$ticket_id_3 = $this->create_tc_ticket( $post, 33.84 );
		$ticket_id_4 = $this->create_tc_ticket( $post, 45.12 );
		$ticket_id_5 = $this->create_tc_ticket( $post, 56.40 );

		// Create a 17.3% off coupon.
		$coupon_1 = $this->create_coupon(
			[
				'raw_amount' => 17.3,
				'sub_type'   => 'percent',
			]
		);

		// Create a $3.45 off coupon.
		$coupon_2 = $this->create_coupon(
			[
				'raw_amount' => 3.45,
				'sub_type'   => 'flat',
			]
		);

		// Basic checks to ensure the coupon is calculating values correctly.
		Assert::assertEquals( -1.95, $coupon_1->get_discount_amount( 11.28 ), '17.3% of 11.28 should be 1.95' );
		Assert::assertEquals( -3.90, $coupon_1->get_discount_amount( 22.56 ), '17.3% of 22.56 should be 3.90' );
		Assert::assertEquals( -5.85, $coupon_1->get_discount_amount( 33.84 ), '17.3% of 33.84 should be 5.85' );
		Assert::assertEquals( -7.81, $coupon_1->get_discount_amount( 45.12 ), '17.3% of 45.12 should be 7.81' );
		Assert::assertEquals( -9.76, $coupon_1->get_discount_amount( 56.40 ), '17.3% of 56.40 should be 9.76' );

		// All of the coupons for the flat rate should be the same.
		Assert::assertEquals( -3.45, $coupon_2->get_discount_amount( 11.28 ), '3.45 off 11.28 should be 3.45' );
		Assert::assertEquals( -3.45, $coupon_2->get_discount_amount( 22.56 ), '3.45 off 22.56 should be 3.45' );
		Assert::assertEquals( -3.45, $coupon_2->get_discount_amount( 33.84 ), '3.45 off 33.84 should be 3.45' );
		Assert::assertEquals( -3.45, $coupon_2->get_discount_amount( 45.12 ), '3.45 off 45.12 should be 3.45' );
		Assert::assertEquals( -3.45, $coupon_2->get_discount_amount( 56.40 ), '3.45 off 56.40 should be 3.45' );

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

		// Cart subtotal should be (11.28 * 2) + (22.56 * 3) + (33.84 * 4) + (45.12 * 5) + (56.40 * 6) = 22.56 + 67.68 + 135.36 + 225.60 + 338.40 = 789.60.
		Assert::assertEquals( 789.60, $cart_subtotal );

		$order1 = $this->create_order_from_cart();

		// Validate that the order has the correct amounts.
		Assert::assertCount( 5, $order1->items, 'Order should have 5 different tickets' );
		Assert::assertObjectHasAttribute( 'coupons', $order1, 'Order object should have coupons property' );
		Assert::assertCount( 0, $order1->coupons, 'Coupons should be empty when no coupons added' );
		Assert::assertEquals( 789.60, $order1->subtotal->get_float() );
		Assert::assertEquals( 789.60, $order1->total_value->get_float() );

		// Let's add a coupon and create a new order.
		$coupon_1->add_to_cart( $cart->get_repository() );

		// Grab the total and subtotal.
		$cart_subtotal = $cart->get_cart_subtotal();
		$cart_total    = $cart->get_cart_total();

		// Cart subtotal should be (11.28 * 2) + (22.56 * 3) + (33.84 * 4) + (45.12 * 5) + (56.40 * 6) = 22.56 + 67.68 + 135.36 + 225.60 + 338.40 = 789.60.
		Assert::assertEquals( 789.60, $cart_subtotal );

		// Cart total should be 789.60 - 17.3% = 789.60 - 136.60 = 653.00.
		Assert::assertEquals( 653.00, $cart_total, 'Order should be discounted by 17.3% ($136.60)' );

		$order2 = $this->create_order_from_cart();

		// Validate that the order has the correct amounts.
		Assert::assertCount( 5, $order2->items, 'Order should have 5 different tickets' );
		Assert::assertObjectHasAttribute( 'coupons', $order2, 'Order object should have coupons property' );
		Assert::assertCount( 1, $order2->coupons, 'Coupons should have 1 coupon' );
		Assert::assertEquals( 789.60, $order2->subtotal->get_float() );
		Assert::assertEquals( 653.00, $order2->total_value->get_float(), 'Order should be discounted by 17.3% ($136.00)' );
	}

	/**
	 * @test
	 */
	public function it_should_update_coupon_usage() {
		$post = static::factory()->post->create(
			[ 'post_title' => 'The Event' ],
		);

		// Create a ticket.
		$ticket_id = $this->create_tc_ticket( $post, 11.28 );

		// Create a 17.3% off coupon.
		$coupon = $this->create_coupon(
			[
				'raw_amount' => 17.3,
				'sub_type'   => 'percent',
			]
		);

		// Set the usage limit to 2.
		$limit = 5;
		$repo  = tribe( Order_Modifiers_Meta::class );
		$repo->upsert_meta(
			new Order_Modifier_Meta(
				[
					'order_modifier_id' => $coupon->id,
					'meta_key'          => 'coupons_available',
					'meta_value'        => $limit,
				]
			)
		);

		// Ensure the usage limit is set correctly.
		Assert::assertEquals( $limit, $this->get_coupon_usage_limit( $coupon->id ) );
		Assert::assertEquals( 0, $this->get_coupon_uses( $coupon->id ) );

		// Basic checks to ensure the coupon is calculating values correctly.
		Assert::assertEquals( -1.95, $coupon->get_discount_amount( 11.28 ), '17.3% of 11.28 should be 1.95' );

		// Register the controller.
		$this->make_controller()->register();

		// Get the cart and start adding tickets.
		/** @var Commerce_Cart $cart */
		$cart = tribe( Commerce_Cart::class );
		$cart->add_ticket( $ticket_id, 2 );

		// Grab the total and subtotal.
		$cart_subtotal = $cart->get_cart_subtotal();
		$cart_total    = $cart->get_cart_total();

		// With only tickets in the cart and no coupons applied, the total and subtotal should be the same.
		Assert::assertEquals( $cart_subtotal, $cart_total );

		// Add the order to the cart, create an order, and ensure the coupon is used.
		$coupon->add_to_cart( $cart->get_repository() );
		$order = $this->create_order_from_cart();

		// The limit should be the same, and the usage should have increased.
		Assert::assertEquals( $limit, $this->get_coupon_usage_limit( $coupon->id ) );
		Assert::assertEquals( 1, $this->get_coupon_uses( $coupon->id ) );

		// Transition the order status back to pending and then to completed.
		$orders = tribe( Order::class );
		$orders->modify_status( $order->ID, Pending::SLUG );
		$orders->modify_status( $order->ID, Completed::SLUG );
		Assert::assertEquals(
			1,
			$this->get_coupon_uses( $coupon->id ),
			'Order status should not affect coupon usage'
		);
	}

	/**
	 * @test
	 */
	public function it_should_handle_status_transitions_without_duplicate_usages() {
		$post = static::factory()->post->create(
			[ 'post_title' => 'The Event' ],
		);

		// Create a ticket.
		$ticket_id = $this->create_tc_ticket( $post, 11.28 );

		// Create a 17.3% off coupon.
		$coupon = $this->create_coupon(
			[
				'raw_amount' => 17.3,
				'sub_type'   => 'percent',
			]
		);

		// Set the usage limit to 2.
		$limit = 5;
		$repo  = tribe( Order_Modifiers_Meta::class );
		$repo->upsert_meta(
			new Order_Modifier_Meta(
				[
					'order_modifier_id' => $coupon->id,
					'meta_key'          => 'coupons_available',
					'meta_value'        => $limit,
				]
			)
		);

		// Ensure the usage limit is set correctly.
		Assert::assertEquals( $limit, $this->get_coupon_usage_limit( $coupon->id ) );
		Assert::assertEquals( 0, $this->get_coupon_uses( $coupon->id ) );

		// Register the controller.
		$this->make_controller()->register();

		// Get the cart and add ticket and coupon.
		/** @var Commerce_Cart $cart */
		$cart = tribe( Commerce_Cart::class );
		$cart->add_ticket( $ticket_id, 2 );
		$coupon->add_to_cart( $cart->get_repository() );

		$order = tec_tc_get_order( $this->create_order_without_transitions()->ID );
		$orders = tribe( Order::class );
		$status_handler = tribe( Status_Handler::class );

		// Status should start as created.
		Assert::assertEquals( Created::SLUG, $status_handler->get_by_wp_slug( $order->post_status )::SLUG );
		Assert::assertEquals( $limit, $this->get_coupon_usage_limit( $coupon->id ) );
		Assert::assertEquals( 0, $this->get_coupon_uses( $coupon->id ) );

		// Transition the order status to pending. At this point, the coupon should be marked as used.
		$orders->modify_status( $order->ID, Pending::SLUG );
		$order = tec_tc_get_order( $order->ID );
		Assert::assertEquals( Pending::SLUG, $status_handler->get_by_wp_slug( $order->post_status )::SLUG );
		Assert::assertEquals( $limit, $this->get_coupon_usage_limit( $coupon->id ) );
		Assert::assertEquals( 1, $this->get_coupon_uses( $coupon->id ) );

		// Transition the order status to Approved. No change in the coupon usage.
		$orders->modify_status( $order->ID, Approved::SLUG );
		$order = tec_tc_get_order( $order->ID );
		Assert::assertEquals( Approved::SLUG, $status_handler->get_by_wp_slug( $order->post_status )::SLUG );
		Assert::assertEquals( $limit, $this->get_coupon_usage_limit( $coupon->id ) );
		Assert::assertEquals( 1, $this->get_coupon_uses( $coupon->id ) );

		// Transition to Action required. No change in the coupon usage.
		$orders->modify_status( $order->ID, Action_Required::SLUG );
		$order = tec_tc_get_order( $order->ID );
		Assert::assertEquals( Action_Required::SLUG, $status_handler->get_by_wp_slug( $order->post_status )::SLUG );
		Assert::assertEquals( $limit, $this->get_coupon_usage_limit( $coupon->id ) );
		Assert::assertEquals( 1, $this->get_coupon_uses( $coupon->id ) );

		// Transition to Completed. No change in the coupon usage.
		$orders->modify_status( $order->ID, Completed::SLUG );
		$order = tec_tc_get_order( $order->ID );
		Assert::assertEquals( Completed::SLUG, $status_handler->get_by_wp_slug( $order->post_status )::SLUG );
		Assert::assertEquals( $limit, $this->get_coupon_usage_limit( $coupon->id ) );
		Assert::assertEquals( 1, $this->get_coupon_uses( $coupon->id ) );

		// Transition to Refunded. Coupon usage should be decremented.
		$orders->modify_status( $order->ID, Refunded::SLUG );
		$order = tec_tc_get_order( $order->ID );
		Assert::assertEquals( Refunded::SLUG, $status_handler->get_by_wp_slug( $order->post_status )::SLUG );
		Assert::assertEquals( $limit, $this->get_coupon_usage_limit( $coupon->id ) );
		Assert::assertEquals( 0, $this->get_coupon_uses( $coupon->id ) );
	}
}
