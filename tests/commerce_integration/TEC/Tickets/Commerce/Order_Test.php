<?php

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce\Cart;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Tickets__Tickets as Tickets;
use WP_Post;
use Generator;
use Closure;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Approved;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Status\Action_Required;
use TEC\Tickets\Commerce\Status\Created;
use TEC\Tickets\Commerce\Status\Refunded;
use TEC\Tickets\Commerce\Status\Unsupported;
use Tribe\Tests\Traits\With_Clock_Mock;
use Tribe__Date_Utils as Dates;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;

class Order_Test extends WPTestCase {
	use Ticket_Maker;
	use With_Uopz;
	use Order_Maker;
	use With_Clock_Mock;

	protected static array $clean_callbacks = [];
	protected static array $back_up = [];

	public function test_it_does_not_create_multiple_orders_for_single_cart() {
		$post = self::factory()->post->create(
			[
				'post_type' => 'page',
			]
		);
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

		$cart = tribe( Cart::class );

		$this->set_fn_return( 'wp_generate_password', 'abcdefghijklmnop' );
		$hash = $cart->get_cart_hash( true );
		$this->assertSame( 'abcdefghijklmnop', $hash );

		// Each next order includes previous order's items as well since we are not clearing the cart in between.
		$order_1 = $this->create_order_from_cart_items( [ $ticket_id_1 => 1 ] );
		$this->assertSame( 'abcdefghijklmnop', $cart->get_cart_hash() );

		$order_2 = $this->create_order_from_cart_items( [ $ticket_id_2 => 1 ] );
		$this->assertSame( 'abcdefghijklmnop', $cart->get_cart_hash() );

		// This will update the quantity of both tickets in the cart.
		$order_3 = $this->create_order_from_cart_items( [ $ticket_id_1 => 2, $ticket_id_2 => 2 ] );
		$this->assertSame( 'abcdefghijklmnop', $cart->get_cart_hash() );

		// All of the orders should be an instance of the WP_Post class.
		$this->assertInstanceof( WP_Post::class, $order_1 );
		$this->assertInstanceof( WP_Post::class, $order_2 );
		$this->assertInstanceof( WP_Post::class, $order_3 );

		// They should be the same order.
		$this->assertEquals( $order_1->ID, $order_2->ID );
		$this->assertEquals( $order_1->ID, $order_3->ID );

		// The first order has 1 ticket of ID 1, which costs 10.
		$this->assertSame( 10.0, $order_1->total_value->get_decimal() );

		// The second order has 1 ticket of ID 2, which costs 20, plus the original ticket of ID 1.
		$this->assertSame( 30.0, $order_2->total_value->get_decimal() );

		// The third order has 2 tickets of ID 1, which costs 20, and 2 tickets of ID 2, which costs 40.
		$this->assertSame( 60.0, $order_3->total_value->get_decimal() );

		$times = 0;
		$this->set_fn_return( 'wp_generate_password', function () use ( &$times ) {
			$times++;
			return 'abcdefghijklmnop-' . $times;
		}, true );

		$cart->clear_cart();
		$cart->get_cart_hash( true );
		$order_4 = $this->create_order_from_cart_items( [ $ticket_id_1 => 1 ] );
		$this->assertSame( 'abcdefghijklmnop-1', $cart->get_cart_hash() );
		$cart->clear_cart();
		$cart->get_cart_hash( true );
		$order_5 = $this->create_order_from_cart_items( [ $ticket_id_2 => 1 ] );
		$this->assertSame( 'abcdefghijklmnop-2', $cart->get_cart_hash() );
		$cart->clear_cart();
		$cart->get_cart_hash( true );
		$order_6 = $this->create_order_from_cart_items( [ $ticket_id_1 => 2, $ticket_id_2 => 2 ] );
		$this->assertSame( 'abcdefghijklmnop-3', $cart->get_cart_hash() );
		$cart->clear_cart();

		$this->assertInstanceof( WP_Post::class, $order_4 );
		$this->assertInstanceof( WP_Post::class, $order_5 );
		$this->assertInstanceof( WP_Post::class, $order_6 );

		// They should NOT be the same order!
		$this->assertNotSame( $order_4->ID, $order_5->ID );
		$this->assertNotSame( $order_4->ID, $order_6->ID );
		$this->assertNotSame( $order_5->ID, $order_6->ID );

		$this->assertSame( 10.0, $order_4->total_value->get_decimal() );
		$this->assertSame( 20.0, $order_5->total_value->get_decimal() );
		$this->assertSame( 60.0, $order_6->total_value->get_decimal() );
	}

	public function test_attendees_are_not_created_multiple_times() {
		$post = self::factory()->post->create(
			[
				'post_type' => 'page',
			]
		);
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

		$order = $this->create_order( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ] );

		tribe( Order::class )->modify_status( $order->ID, Pending::SLUG );

		$attendees = tec_tc_attendees()->by( 'parent', $order->ID )->by( 'status', 'any' )->all();

		$this->assertCount( 3, $attendees );

		tribe( Order::class )->modify_status( $order->ID, Pending::SLUG );

		$attendees = tec_tc_attendees()->by( 'parent', $order->ID )->by( 'status', 'any' )->all();

		$this->assertCount( 3, $attendees );

		tribe( Order::class )->modify_status( $order->ID, Completed::SLUG );

		$attendees = tec_tc_attendees()->by( 'parent', $order->ID )->by( 'status', 'any' )->all();

		$this->assertCount( 3, $attendees );
	}

	public function test_checkout_completed_flag() {
		$post = self::factory()->post->create(
			[
				'post_type' => 'page',
			]
		);
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

		$order = $this->create_order_from_cart_items( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ] );
		tribe( Cart::class )->clear_cart();

		$this->assertFalse( tribe( Order::class )->is_checkout_completed( $order->ID ) );

		$this->assertTrue( tribe( Order::class )->checkout_completed( $order->ID ) );

		$this->assertTrue( tribe( Order::class )->is_checkout_completed( $order->ID ) );
	}

	public function test_on_checkout_screen_hold_flag() {
		$this->freeze_time( Dates::immutable( '2024-06-13 17:25:00' ) );
		$post = self::factory()->post->create(
			[
				'post_type' => 'page',
			]
		);
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

		$order = $this->create_order_from_cart_items( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ] );
		tribe( Cart::class )->clear_cart();

		$this->assertFalse( tribe( Order::class )->has_on_checkout_screen_hold( $order->ID ) );

		tribe( Order::class )->set_on_checkout_screen_hold( $order->ID );
		$this->assertTrue( tribe( Order::class )->has_on_checkout_screen_hold( $order->ID ) );

		$this->freeze_time( Dates::immutable( '2024-06-13 17:31:00' ) );
		$this->assertFalse( tribe( Order::class )->has_on_checkout_screen_hold( $order->ID ) );
	}

	public function test_orders_are_not_updated_while_locked() {
		$post = self::factory()->post->create(
			[
				'post_type' => 'page',
			]
		);
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

		$storage = [];
		add_action( 'tec_tickets_commerce_order_status_transition', function ( $new, $old, $post ) use ( &$storage ) {
			$storage[] = [ $new::SLUG, $old::SLUG, $post->ID ];
		}, 10, 3 );

		$this->assertEquals( 0, did_action( 'tec_tickets_commerce_order_status_transition' ) );
		$order = $this->create_order_from_cart_items( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ] );
		tribe( Cart::class )->clear_cart();

		$this->assertFalse( tribe( Order::class )->is_order_locked( $order->ID ) );

		$result = tribe( Order::class )->modify_status( $order->ID, Completed::SLUG );

		$this->assertEquals( 3, did_action( 'tec_tickets_commerce_order_status_transition' ) );

		$this->assertEquals( [ Created::SLUG, Unsupported::SLUG, $order->ID ], $storage['0'] );
		$this->assertEquals( [ Pending::SLUG, Created::SLUG, $order->ID ], $storage['1'] );
		$this->assertEquals( [ Completed::SLUG, Pending::SLUG, $order->ID ], $storage['2'] );

		$this->assertTrue( $result );

		tribe( Order::class )->lock_order( $order->ID );

		$this->assertTrue( tribe( Order::class )->is_order_locked( $order->ID ) );

		// Change the lock id so that the lock is not released.
		tribe( Order::class )->generate_lock_id( $order->ID );
		$result = tribe( Order::class )->modify_status( $order->ID, Pending::SLUG );

		$this->assertFalse( $result );

		tribe( Order::class )->unlock_order( $order->ID );

		$this->assertFalse( tribe( Order::class )->is_order_locked( $order->ID ) );

		$result = tribe( Order::class )->modify_status( $order->ID, Pending::SLUG );

		$this->assertTrue( $result );
	}

	/**
	 * @skip This will also need to be completed when stock/attendees are fixed and done!
	 */
	public function test_double_order_transition_does_not_count_sales_twice() {
		$post = self::factory()->post->create(
			[
				'post_type' => 'page',
			]
		);
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

		// During action-req transition the action increase sales is fired.
		$order = $this->create_order( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ], [ 'order_status' => Action_Required::SLUG ] );

		$ticket_obj_1 = Tickets::load_ticket_object( $ticket_id_1 );
		$ticket_obj_2 = Tickets::load_ticket_object( $ticket_id_2 );

		// The sales should be counted once.
		$this->assertSame( 1, $ticket_obj_1->qty_sold() );
		$this->assertSame( 2, $ticket_obj_2->qty_sold() );

		// During completed transition the action increase sales is fired again.
		tribe( Order::class )->modify_status( $order->ID, Completed::SLUG );

		// refresh objects.
		$ticket_obj_1 = Tickets::load_ticket_object( $ticket_id_1 );
		$ticket_obj_2 = Tickets::load_ticket_object( $ticket_id_2 );

		// The sales should not be counted twice.
		$this->assertSame( 1, $ticket_obj_1->qty_sold() );
		$this->assertSame( 2, $ticket_obj_2->qty_sold() );

		// manually modify item quantity - there is no API current for add_item to existing order.
		$items = ( (array) get_post_meta( $order->ID, '_tec_tc_order_items', true ) );
		if ( isset( $items[ $ticket_id_1 ] ) ) {
			$items[ $ticket_id_1 ]['quantity'] = $items[ $ticket_id_1 ]['quantity'] + 1;
		} else {
			$items['0']['quantity'] = $items['0']['quantity'] + 1;
		}

		if ( isset( $items[ $ticket_id_2 ] ) ) {
			$items[ $ticket_id_2 ]['quantity'] = $items[ $ticket_id_2 ]['quantity'] + 2;
		} else {
			$items['1']['quantity'] = $items['1']['quantity'] + 2;
		}

		update_post_meta( $order->ID, '_tec_tc_order_items', $items );

		// During action-req transition the action increase sales is fired again.
		tribe( Order::class )->modify_status( $order->ID, Action_Required::SLUG );

		// refresh objects.
		$ticket_obj_1 = Tickets::load_ticket_object( $ticket_id_1 );
		$ticket_obj_2 = Tickets::load_ticket_object( $ticket_id_2 );

		// The sales should be updated only for the new added tickets.
		$this->assertSame( 2, $ticket_obj_1->qty_sold() );
		$this->assertSame( 4, $ticket_obj_2->qty_sold() );

		// Back to completed after adding new tickets.
		tribe( Order::class )->modify_status( $order->ID, Completed::SLUG );

		// refresh objects.
		$ticket_obj_1 = Tickets::load_ticket_object( $ticket_id_1 );
		$ticket_obj_2 = Tickets::load_ticket_object( $ticket_id_2 );

		// The sales should not be counted again.
		$this->assertSame( 2, $ticket_obj_1->qty_sold() );
		$this->assertSame( 4, $ticket_obj_2->qty_sold() );
	}

	/**
	 * @skip This will need to be completed when stock/attendees are fixed and done!
	 */
	public function test_order_status_transitions_and_stats() {
		$post = self::factory()->post->create(
			[
				'post_type' => 'page',
			]
		);
		// Enable the global stock on the Event.
		update_post_meta( $post, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );
		// Set the Event global stock level to 50.
		update_post_meta( $post, Global_Stock::GLOBAL_STOCK_LEVEL, 50 );

		$ticket_a_id = $this->create_tc_ticket(
			$post,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 30,
				],
			]
		);
		$ticket_b_id = $this->create_tc_ticket(
			$post,
			20,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::GLOBAL_STOCK_MODE,
					'capacity' => 50,
				],
			]
		);
		$ticket_c_id = $this->create_tc_ticket(
			$post,
			30,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::OWN_STOCK_MODE,
					'capacity' => 40,
				],
			]
		);

		// Get the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $post, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $post, $ticket_b_id );
		$ticket_c = tribe( Module::class )->get_ticket( $post, $ticket_c_id );

		// Make sure both tickets are valid Ticket Object.
		$this->assertInstanceOf( Ticket_Object::class, $ticket_a );
		$this->assertInstanceOf( Ticket_Object::class, $ticket_b );
		$this->assertInstanceOf( Ticket_Object::class, $ticket_c );

		$this->assertEquals( 30, $ticket_a->capacity() );
		$this->assertEquals( 30, $ticket_a->stock() );
		$this->assertEquals( 30, $ticket_a->available() );
		$this->assertEquals( 30, $ticket_a->inventory() );

		$this->assertEquals( 50, $ticket_b->capacity() );
		$this->assertEquals( 50, $ticket_b->stock() );
		$this->assertEquals( 50, $ticket_b->available() );
		$this->assertEquals( 50, $ticket_b->inventory() );

		$this->assertEquals( 40, $ticket_c->capacity() );
		$this->assertEquals( 40, $ticket_c->stock() );
		$this->assertEquals( 40, $ticket_c->available() );
		$this->assertEquals( 40, $ticket_c->inventory() );

		$global_stock = new Global_Stock( $post );

		$this->assertTrue( $global_stock->is_enabled(), 'Global stock should be enabled.' );
		$this->assertEquals( 90, tribe_get_event_capacity( $post ), 'Total Event capacity should be 50' );
		$this->assertEquals( 50, $global_stock->get_stock_level(), 'Global stock should be 50' );

		// Create an Order for 5 on each Ticket.
		$order_id = $this->create_order_from_cart_items(
			[
				$ticket_a_id => 5,
				$ticket_b_id => 6,
				$ticket_c_id => 7,
			]
		)->ID;

		$attendee_by_ticket = function ( $ticket_id ) use ( $order_id ){
			return tec_tc_attendees()->by_args(
				[
					'post_parent' => $order_id,
					'ticket_id'   => $ticket_id,
				]
			)->get_ids();
		};

		$refreshed_order = tec_tc_get_order( $order_id );

		$this->assertEquals( Created::SLUG, str_replace( 'tec-tc-', '', $refreshed_order->post_status ), 'Order should be in Created status' );

		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );

		// Refresh the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $post, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $post, $ticket_b_id );
		$ticket_c = tribe( Module::class )->get_ticket( $post, $ticket_c_id );

		$this->assertCount( 0, $attendee_by_ticket( $ticket_a_id ) );
		$this->assertCount( 0, $attendee_by_ticket( $ticket_b_id ) );
		$this->assertCount( 0, $attendee_by_ticket( $ticket_c_id ) );

		$this->assertEquals( 30, $ticket_a->capacity() );
		$this->assertEquals( 30, $ticket_a->stock() );
		$this->assertEquals( 30, $ticket_a->available() );
		$this->assertEquals( 30, $ticket_a->inventory() );

		$this->assertEquals( 50, $ticket_b->capacity() );
		$this->assertEquals( 50, $ticket_b->stock() );
		$this->assertEquals( 50, $ticket_b->available() );
		$this->assertEquals( 50, $ticket_b->inventory() );

		$this->assertEquals( 40, $ticket_c->capacity() );
		$this->assertEquals( 40, $ticket_c->stock() );
		$this->assertEquals( 40, $ticket_c->available() );
		$this->assertEquals( 40, $ticket_c->inventory() );

		$this->assertEquals( 50, $global_stock->get_stock_level(), 'Global stock should be 50' );

		$orders = tribe( Order::class );

		// Transition to Pending.
		$orders->modify_status( $order_id, Pending::SLUG );

		$refreshed_order = tec_tc_get_order( $order_id );

		$global_stock = new Global_Stock( $post );

		$this->assertEquals( Pending::SLUG, str_replace( 'tec-tc-', '', $refreshed_order->post_status ) );

		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );

		// Refresh the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $post, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $post, $ticket_b_id );
		$ticket_c = tribe( Module::class )->get_ticket( $post, $ticket_c_id );

		$this->assertCount( 5, $attendee_by_ticket( $ticket_a_id ) );
		$this->assertCount( 6, $attendee_by_ticket( $ticket_b_id ) );
		$this->assertCount( 7, $attendee_by_ticket( $ticket_c_id ) );

		$this->assertEquals( 30, $ticket_a->capacity() );
		$this->assertEquals( 30 - 5, $ticket_a->stock() );
		$this->assertEquals( 30 - 5, $ticket_a->available() );
		$this->assertEquals( 30 - 5, $ticket_a->inventory() );

		$this->assertEquals( 50, $ticket_b->capacity() );
		$this->assertEquals( 50 - 11, $ticket_b->stock() );
		$this->assertEquals( 50 - 11, $ticket_b->available() );
		$this->assertEquals( 50 - 11, $ticket_b->inventory() );

		$this->assertEquals( 40, $ticket_c->capacity() );
		$this->assertEquals( 40 - 7, $ticket_c->stock() );
		$this->assertEquals( 40 - 7, $ticket_c->available() );
		$this->assertEquals( 40 - 7, $ticket_c->inventory() );

		$this->assertEquals( 50 - 11, $global_stock->get_stock_level(), 'Global stock should be 50-11 = 39' );

		// Transition to Approved.
		$orders->modify_status( $order_id, Approved::SLUG );

		$refreshed_order = tec_tc_get_order( $order_id );

		$this->assertEquals( Approved::SLUG, str_replace( 'tec-tc-', '', $refreshed_order->post_status ) );

		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );

		// Refresh the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $post, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $post, $ticket_b_id );
		$ticket_c = tribe( Module::class )->get_ticket( $post, $ticket_c_id );

		$this->assertCount( 5, $attendee_by_ticket( $ticket_a_id ) );
		$this->assertCount( 6, $attendee_by_ticket( $ticket_b_id ) );
		$this->assertCount( 7, $attendee_by_ticket( $ticket_c_id ) );

		$this->assertEquals( 30, $ticket_a->capacity() );
		$this->assertEquals( 30 - 5, $ticket_a->stock() );
		$this->assertEquals( 30 - 5, $ticket_a->available() );
		$this->assertEquals( 30 - 5, $ticket_a->inventory() );

		$this->assertEquals( 50, $ticket_b->capacity() );
		$this->assertEquals( 50 - 11, $ticket_b->stock() );
		$this->assertEquals( 50 - 11, $ticket_b->available() );
		$this->assertEquals( 50 - 11, $ticket_b->inventory() );

		$this->assertEquals( 40, $ticket_c->capacity() );
		$this->assertEquals( 40 - 7, $ticket_c->stock() );
		$this->assertEquals( 40 - 7, $ticket_c->available() );
		$this->assertEquals( 40 - 7, $ticket_c->inventory() );

		$this->assertEquals( 50 - 11, $global_stock->get_stock_level(), 'Global stock should be 50-11 = 39' );

		// Transition to Action Required.
		$orders->modify_status( $order_id, Action_Required::SLUG );

		$refreshed_order = tec_tc_get_order( $order_id );

		$this->assertEquals( Action_Required::SLUG, str_replace( 'tec-tc-', '', $refreshed_order->post_status ) );

		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );

		// Refresh the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $post, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $post, $ticket_b_id );
		$ticket_c = tribe( Module::class )->get_ticket( $post, $ticket_c_id );

		$this->assertCount( 5, $attendee_by_ticket( $ticket_a_id ) );
		$this->assertCount( 6, $attendee_by_ticket( $ticket_b_id ) );
		$this->assertCount( 7, $attendee_by_ticket( $ticket_c_id ) );

		$this->assertEquals( 30, $ticket_a->capacity() );
		$this->assertEquals( 30 - 5, $ticket_a->stock() );
		$this->assertEquals( 30 - 5, $ticket_a->available() );
		$this->assertEquals( 30 - 5, $ticket_a->inventory() );

		$this->assertEquals( 50, $ticket_b->capacity() );
		$this->assertEquals( 50 - 11, $ticket_b->stock() );
		$this->assertEquals( 50 - 11, $ticket_b->available() );
		$this->assertEquals( 50 - 11, $ticket_b->inventory() );

		$this->assertEquals( 40, $ticket_c->capacity() );
		$this->assertEquals( 40 - 7, $ticket_c->stock() );
		$this->assertEquals( 40 - 7, $ticket_c->available() );
		$this->assertEquals( 40 - 7, $ticket_c->inventory() );

		$this->assertEquals( 50 - 11, $global_stock->get_stock_level(), 'Global stock should be 50-11 = 39' );

		// Transition to Completed.
		$orders->modify_status( $order_id, Completed::SLUG );

		$refreshed_order = tec_tc_get_order( $order_id );

		$this->assertEquals( Completed::SLUG, str_replace( 'tec-tc-', '', $refreshed_order->post_status ) );

		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );

		// Refresh the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $post, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $post, $ticket_b_id );
		$ticket_c = tribe( Module::class )->get_ticket( $post, $ticket_c_id );

		$this->assertCount( 5, $attendee_by_ticket( $ticket_a_id ) );
		$this->assertCount( 6, $attendee_by_ticket( $ticket_b_id ) );
		$this->assertCount( 7, $attendee_by_ticket( $ticket_c_id ) );

		$this->assertEquals( 30, $ticket_a->capacity() );
		$this->assertEquals( 30 - 5, $ticket_a->stock() );
		$this->assertEquals( 30 - 5, $ticket_a->available() );
		$this->assertEquals( 30 - 5, $ticket_a->inventory() );

		$this->assertEquals( 50, $ticket_b->capacity() );
		$this->assertEquals( 50 - 11, $ticket_b->stock() );
		$this->assertEquals( 50 - 11, $ticket_b->available() );
		$this->assertEquals( 50 - 11, $ticket_b->inventory() );

		$this->assertEquals( 40, $ticket_c->capacity() );
		$this->assertEquals( 40 - 7, $ticket_c->stock() );
		$this->assertEquals( 40 - 7, $ticket_c->available() );
		$this->assertEquals( 40 - 7, $ticket_c->inventory() );

		$this->assertEquals( 50 - 11, $global_stock->get_stock_level(), 'Global stock should be 50-11 = 39' );

		// Transition to Refunded.
		$orders->modify_status( $order_id, Refunded::SLUG );

		$refreshed_order = tec_tc_get_order( $order_id );

		$this->assertEquals( Refunded::SLUG, str_replace( 'tec-tc-', '', $refreshed_order->post_status ) );

		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );

		// Refresh the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $post, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $post, $ticket_b_id );
		$ticket_c = tribe( Module::class )->get_ticket( $post, $ticket_c_id );

		$this->assertCount( 0, $attendee_by_ticket( $ticket_a_id ) );
		$this->assertCount( 0, $attendee_by_ticket( $ticket_b_id ) );
		$this->assertCount( 0, $attendee_by_ticket( $ticket_c_id ) );

		$this->assertEquals( 30, $ticket_a->capacity() );
		$this->assertEquals( 30, $ticket_a->stock() );
		$this->assertEquals( 30, $ticket_a->available() );
		$this->assertEquals( 30, $ticket_a->inventory() );

		$this->assertEquals( 50, $ticket_b->capacity() );
		$this->assertEquals( 50, $ticket_b->stock() );
		$this->assertEquals( 50, $ticket_b->available() );
		$this->assertEquals( 50, $ticket_b->inventory() );

		$this->assertEquals( 40, $ticket_c->capacity() );
		$this->assertEquals( 40, $ticket_c->stock() );
		$this->assertEquals( 40, $ticket_c->available() );
		$this->assertEquals( 40, $ticket_c->inventory() );

		$this->assertEquals( 50, $global_stock->get_stock_level(), 'Global stock should be 50' );
	}
	public function modify_status_provider(): Generator {
		yield 'already locked order' => [
			function () {
				tec_tickets_tests_fake_transactions_disable();
				$post = self::factory()->post->create(
					[
						'post_type' => 'page',
					]
				);
				$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
				$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

				$order = $this->create_order( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ], [ 'order_status' => Pending::SLUG ] );

				tribe( Order::class )->lock_order( $order->ID );

				$post_ids = array_merge( [ $order->ID, $ticket_id_1, $ticket_id_2, $post ], tribe_attendees()->where( 'event_id', $post )->get_ids() );

				self::$clean_callbacks[] = function () use ( $post_ids ) {
					foreach ( $post_ids as $post_id ) {
						wp_delete_post( $post_id, true );
					}
					tec_tickets_tests_fake_transactions_enable();
				};

				tec_tickets_tests_fake_transactions_disable();

				return [ $order->ID, Completed::SLUG, false ];
			},
		];

		yield 'could not find order status' => [
			function () {
				tec_tickets_tests_fake_transactions_disable();
				$post = self::factory()->post->create(
					[
						'post_type' => 'page',
					]
				);
				$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
				$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

				$order = $this->create_order( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ], [ 'order_status' => Pending::SLUG ] );

				// current request locked the order at the same time as another request - as a result next request would have diff lock_id.
				// modifying lock id to simulate this scenario.
				$callback = static fn() => DB::query( DB::prepare( "UPDATE %i SET post_content_filtered='12345678' where ID=%d", DB::prefix( 'posts' ), $order->ID ) );
				add_action( 'tec_tickets_commerce_order_locked', $callback );

				$post_ids = array_merge( [ $order->ID, $ticket_id_1, $ticket_id_2, $post ], tribe_attendees()->where( 'event_id', $post )->get_ids() );

				self::$clean_callbacks[] = function () use ( $post_ids, $callback ) {
					foreach ( $post_ids as $post_id ) {
						wp_delete_post( $post_id, true );
					}
					remove_action( 'tec_tickets_commerce_order_locked', $callback );
					tec_tickets_tests_fake_transactions_enable();
				};

				tec_tickets_tests_fake_transactions_disable();

				return [ $order->ID, Completed::SLUG, false ];
			},
		];

		yield 'could not transition order status' => [
			function () {
				tec_tickets_tests_fake_transactions_disable();
				$post = self::factory()->post->create(
					[
						'post_type' => 'page',
					]
				);
				$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
				$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

				$order = $this->create_order( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ], [ 'order_status' => Pending::SLUG ] );

				$post_ids = array_merge( [ $order->ID, $ticket_id_1, $ticket_id_2, $post ], tribe_attendees()->where( 'event_id', $post )->get_ids() );

				self::$clean_callbacks[] = function () use ( $post_ids ) {
					foreach ( $post_ids as $post_id ) {
						wp_delete_post( $post_id, true );
					}
					tec_tickets_tests_fake_transactions_enable();
				};

				tec_tickets_tests_fake_transactions_disable();

				return [ $order->ID, Pending::SLUG, false ];
			},
		];

		yield 'updated' => [
			function () {
				tec_tickets_tests_fake_transactions_disable();
				$post = self::factory()->post->create(
					[
						'post_type' => 'page',
					]
				);
				$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
				$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

				$order = $this->create_order( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ], [ 'order_status' => Pending::SLUG ] );

				$post_ids = array_merge( [ $order->ID, $ticket_id_1, $ticket_id_2, $post ], tribe_attendees()->where( 'event_id', $post )->get_ids() );

				self::$clean_callbacks[] = function () use ( $post_ids ) {
					foreach ( $post_ids as $post_id ) {
						wp_delete_post( $post_id, true );
					}
					tec_tickets_tests_fake_transactions_enable();
				};

				return [ $order->ID, Completed::SLUG, true ];
			},
		];
	}

	/**
	 * @dataProvider modify_status_provider
	 */
	public function test_modify_status( Closure $fixture ) {
		[ $order_id, $status, $expected ] = $fixture();

		$result = tribe( Order::class )->modify_status( $order_id, $status );

		$this->assertSame( $expected, $result );
	}

	protected function create_order_from_cart_items( array $items, array $overrides = [] ) {
		foreach ( $items as $id => $quantity ) {
			tribe( Cart::class )->get_repository()->upsert_item( $id, $quantity );
		}

		$default_purchaser = [
			'purchaser_user_id'    => 0,
			'purchaser_full_name'  => 'Test Purchaser',
			'purchaser_first_name' => 'Test',
			'purchaser_last_name'  => 'Purchaser',
			'purchaser_email'      => 'test-' . uniqid() . '@test.com',
		];

		$purchaser = wp_parse_args( $overrides, $default_purchaser );

		$feed_args_callback = function ( $args ) use ( $overrides ) {
			$args['post_date']     = $overrides['post_date'] ?? '';
			$args['post_date_gmt'] = $overrides['post_date_gmt'] ?? $args['post_date'];

			return $args;
		};

		add_filter( 'tec_tickets_commerce_order_create_args', $feed_args_callback );

		$orders = tribe( Order::class );
		$order  = $orders->create_from_cart( tribe( Gateway::class ), $purchaser );

		clean_post_cache( $order->ID );

		remove_filter( 'tec_tickets_commerce_order_create_args', $feed_args_callback );

		return $order;
	}

	/**
	 * @before
	 */
	public function reset_wp_actions(): void {
		global $wp_actions;

		self::$back_up = $wp_actions;
		$wp_actions = [];
	}

	/**
	 * @after
	 */
	public function clear_commited_transactions() {
		global $wp_actions;
		$wp_actions = self::$back_up;
		if ( empty( self::$clean_callbacks ) ) {
			return;
		}

		self::$clean_callbacks = array_reverse( self::$clean_callbacks );

		foreach ( self::$clean_callbacks as $callback ) {
			$callback();
		}

		self::$clean_callbacks = [];
	}
}
