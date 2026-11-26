<?php

declare( strict_types=1 );

namespace TEC\Tickets\Tests\Order_Modifiers_Integration\Checkout;

use PHPUnit\Framework\Assert;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Cart as Commerce_Cart;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Coupons;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Coupon as Coupon_Model;
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
use WP_REST_Request;
use WP_REST_Response;

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

	/**
	 * @test
	 */
	public function it_should_not_allow_negative_orders() {
		$this->make_controller()->register();
		$post = static::factory()->post->create(
			[ 'post_title' => 'The Event' ],
		);

		// Create a ticket.
		$ticket_id = $this->create_tc_ticket( $post, 11.28 );

		// Create a $12 off coupon.
		$coupon = $this->create_coupon(
			[
				'raw_amount' => 12,
				'sub_type'   => 'flat',
			]
		);

		// Get the cart and add ticket and coupon.
		/** @var Commerce_Cart $cart */
		$cart = tribe( Commerce_Cart::class );
		$cart->add_ticket( $ticket_id );
		$coupon->add_to_cart( $cart->get_repository() );

		// The cart shouldn't allow for negative values.
		Assert::assertEquals( 0, $cart->get_cart_total() );

		// Generate the order and ensure that it has been created correctly.
		$order = $this->create_order_from_cart();

		Assert::assertEquals( '0', $order->total, 'The order total should be zero' );
		Assert::assertEquals( 11.28, $order->subtotal->get_float(), 'The order subtotal should be 11.28' );
		Assert::assertEquals( 0, $order->total_value->get_float(), 'The order total value should be zero' );
	}

	/**
	 * @test
	 */
	public function it_should_change_coupon_value_to_match_maximum_discount() {
		$this->make_controller()->register();
		$post = static::factory()->post->create(
			[ 'post_title' => 'The Event' ],
		);

		// Create a ticket.
		$ticket_id = $this->create_tc_ticket( $post, 11.28 );

		// Create a $12 off coupon.
		$coupon = $this->create_coupon(
			[
				'raw_amount' => 12,
				'sub_type'   => 'flat',
			]
		);

		// Get the cart and add ticket and coupon.
		/** @var Commerce_Cart $cart */
		$cart = tribe( Commerce_Cart::class );
		$cart->add_ticket( $ticket_id );
		$coupon->add_to_cart( $cart->get_repository() );

		// The cart shouldn't allow for negative values.
		Assert::assertEquals( 0, $cart->get_cart_total() );

		// Get the calculated items from the cart.
		$items = $cart->get_repository()->get_calculated_items( 'all' );
		Assert::assertEquals( 2, count( $items ), 'There should be a ticket and a coupon in the cart' );

		// The coupon should have a value of 11.28.
		$cart_coupon = $cart->get_repository()->get_calculated_items( 'coupon' )[ $coupon->get_type_id() ];
		Assert::assertEquals( -11.28, $cart_coupon['sub_total']->get_float(), 'The coupon sub_total should be the maximum discount' );
		Assert::assertEquals( -11.28, $cart_coupon['price'], 'The coupon price should be the maximum discount' );

		// Generate the order and ensure that it has been created correctly.
		$order = $this->create_order_from_cart();
		Assert::assertEquals( '0', $order->total, 'The order total should be zero' );
		Assert::assertEquals( 0, $order->total_value->get_float(), 'The order total value should be zero' );
		Assert::assertEquals( 11.28, $order->subtotal->get_float(), 'The order subtotal should be 11.28' );
		Assert::assertNotEmpty( $order->coupons, 'The order should have coupons' );
		Assert::assertArrayHasKey( $coupon->get_type_id(), $order->coupons, 'The order should have the specific coupon' );
		Assert::assertEquals( -11.28, $order->coupons[ $coupon->get_type_id() ]['price'], 'The order coupon price should be the maximum discount' );
		Assert::assertEquals( -11.28, $order->coupons[ $coupon->get_type_id() ]['sub_total']->get_float(), 'The order coupon sub_total should be the maximum discount' );
	}

	/**
	 * @test
	 */
	public function it_should_keep_a_coupon_with_uses_remaining_in_the_cart_total(): void {
		$this->make_controller()->register();
		$coupon = $this->create_limited_flat_coupon( 100, 1 );
		$cart   = $this->set_up_cart_with_coupon( 100, $coupon );

		Assert::assertEquals( 0, $cart->get_cart_total() );
	}

	/**
	 * @test
	 */
	public function it_should_drop_a_coupon_with_no_uses_remaining_from_the_cart_total(): void {
		$this->make_controller()->register();
		$coupon = $this->create_limited_flat_coupon( 100, 1 );
		$cart   = $this->set_up_cart_with_coupon( 100, $coupon );

		$this->add_coupon_use( $coupon->id );

		Assert::assertEquals( 100, $cart->get_cart_total() );
		Assert::assertEmpty( $cart->get_repository()->get_calculated_items( 'coupon' ) );
	}

	/**
	 * @test
	 */
	public function it_should_drop_a_deactivated_coupon_from_the_cart_total(): void {
		$this->make_controller()->register();
		$coupon = $this->create_limited_flat_coupon( 100, 0 );
		$cart   = $this->set_up_cart_with_coupon( 100, $coupon );

		$coupon->status = 'inactive';
		$coupon->save();

		Assert::assertEquals( 100, $cart->get_cart_total() );
	}

	/**
	 * @test
	 */
	public function it_should_drop_an_expired_coupon_from_the_cart_total(): void {
		$this->make_controller()->register();
		$coupon = $this->create_limited_flat_coupon( 100, 0 );
		$cart   = $this->set_up_cart_with_coupon( 100, $coupon );

		$coupon->end_time = '2020-01-01 00:00:00';
		$coupon->save();

		Assert::assertEquals( 100, $cart->get_cart_total() );
	}

	/**
	 * @test
	 */
	public function it_should_keep_the_coupon_for_the_cart_whose_pending_order_took_its_last_use(): void {
		$this->make_controller()->register();
		$coupon = $this->create_limited_flat_coupon( 100, 1 );
		$cart   = $this->set_up_cart_with_coupon( 100, $coupon );

		$this->create_order_from_cart( [ 'order_status' => Pending::SLUG ] );
		Assert::assertSame( 1, $this->get_coupon_uses( $coupon->id ), 'The Pending order should hold the only use.' );

		// A later request, e.g. a checkout reload, reads the same cart again.
		$coupon->add_to_cart( $cart->get_repository() );

		Assert::assertEquals( 0, $cart->get_cart_total() );
	}

	/**
	 * @test
	 */
	public function it_should_complete_a_free_order_from_a_cart_holding_a_valid_coupon(): void {
		$this->make_controller()->register();
		$coupon = $this->create_limited_flat_coupon( 100, 1 );
		$this->set_up_cart_with_coupon( 100, $coupon );

		$response = $this->submit_free_order();

		Assert::assertSame( 200, $response->get_status() );
		Assert::assertTrue( $response->get_data()['success'] );
		Assert::assertSame( Completed::SLUG, str_replace( 'tec-tc-', '', get_post_status( $response->get_data()['id'] ) ) );
		Assert::assertSame( 1, $this->get_coupon_uses( $coupon->id ) );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_a_free_order_from_a_cart_holding_a_used_up_coupon(): void {
		$this->make_controller()->register();
		$coupon = $this->create_limited_flat_coupon( 100, 1 );
		$this->set_up_cart_with_coupon( 100, $coupon );

		// Another visitor redeems the only use after this cart applied the coupon.
		$this->add_coupon_use( $coupon->id );
		$orders_before = $this->tc_order_count();

		$response = $this->submit_free_order();

		Assert::assertSame( 400, $response->get_status() );
		Assert::assertSame( 'tec_tickets_commerce_free_order_invalid_cart', $response->as_error()->get_error_code() );
		Assert::assertSame( $orders_before, $this->tc_order_count() );
		Assert::assertSame( 1, $this->get_coupon_uses( $coupon->id ) );
	}

	/**
	 * Creates an active flat coupon with a usage limit.
	 *
	 * @param float $amount The flat discount.
	 * @param int   $limit  The usage limit, `0` for unlimited.
	 *
	 * @return Coupon_Model The coupon.
	 */
	private function create_limited_flat_coupon( float $amount, int $limit ): Coupon_Model {
		$coupon = $this->create_coupon(
			[
				'sub_type'   => 'flat',
				'raw_amount' => $amount,
			]
		);

		tribe( Order_Modifiers_Meta::class )->upsert_meta(
			new Order_Modifier_Meta(
				[
					'order_modifier_id' => $coupon->id,
					'meta_key'          => 'coupons_available',
					'meta_value'        => $limit,
				]
			)
		);

		return $coupon;
	}

	/**
	 * Puts one ticket and the coupon in the current cart.
	 *
	 * @param float        $price  The ticket price.
	 * @param Coupon_Model $coupon The coupon.
	 *
	 * @return Commerce_Cart The cart.
	 */
	private function set_up_cart_with_coupon( float $price, Coupon_Model $coupon ): Commerce_Cart {
		$post      = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post, $price );

		/** @var Commerce_Cart $cart */
		$cart = tribe( Commerce_Cart::class );
		$cart->clear_cart();
		$cart->add_ticket( $ticket_id );
		$coupon->add_to_cart( $cart->get_repository() );
		$cart->get_repository()->save();

		return $cart;
	}

	/**
	 * Submits the current cart to the Free gateway order endpoint.
	 *
	 * @return WP_REST_Response The response.
	 */
	private function submit_free_order(): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/commerce/free/order' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'purchaser' => [
						'name'  => 'Coupon Guest',
						'email' => 'coupon-guest@example.test',
					],
				]
			)
		);

		return rest_do_request( $request );
	}

	private function tc_order_count(): int {
		return count(
			get_posts(
				[
					'post_type'      => Order::POSTTYPE,
					'post_status'    => array_keys( get_post_stati() ),
					'fields'         => 'ids',
					'posts_per_page' => -1,
				]
			)
		);
	}
}
