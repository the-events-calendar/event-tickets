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
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Status\Action_Required;

class Order_Test extends WPTestCase {
	use Ticket_Maker;
	use With_Uopz;
	use Order_Maker;

	protected static array $clean_callbacks = [];

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
		$order_1 = $this->create_order_from_cart( [ $ticket_id_1 => 1 ] );
		$this->assertSame( 'abcdefghijklmnop', $cart->get_cart_hash() );

		$order_2 = $this->create_order_from_cart( [ $ticket_id_2 => 1 ] );
		$this->assertSame( 'abcdefghijklmnop', $cart->get_cart_hash() );

		// This will update the quantity of both tickets in the cart.
		$order_3 = $this->create_order_from_cart( [ $ticket_id_1 => 2, $ticket_id_2 => 2 ] );
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
		$order_4 = $this->create_order_from_cart( [ $ticket_id_1 => 1 ] );
		$this->assertSame( 'abcdefghijklmnop-1', $cart->get_cart_hash() );
		$cart->clear_cart();
		$cart->get_cart_hash( true );
		$order_5 = $this->create_order_from_cart( [ $ticket_id_2 => 1 ] );
		$this->assertSame( 'abcdefghijklmnop-2', $cart->get_cart_hash() );
		$cart->clear_cart();
		$cart->get_cart_hash( true );
		$order_6 = $this->create_order_from_cart( [ $ticket_id_1 => 2, $ticket_id_2 => 2 ] );
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

		$order = $this->create_order_from_cart( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ] );
		tribe( Cart::class )->clear_cart();

		$this->assertFalse( tribe( Order::class )->is_checkout_completed( $order->ID ) );

		$this->assertTrue( tribe( Order::class )->checkout_completed( $order->ID ) );

		$this->assertTrue( tribe( Order::class )->is_checkout_completed( $order->ID ) );
	}

	public function test_orders_are_not_updated_while_locked() {
		$post = self::factory()->post->create(
			[
				'post_type' => 'page',
			]
		);
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

		$order = $this->create_order_from_cart( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ] );
		tribe( Cart::class )->clear_cart();

		$this->assertFalse( tribe( Order::class )->is_order_locked( $order->ID ) );

		$result = tribe( Order::class )->modify_status( $order->ID, Completed::SLUG );

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

	protected function create_order_from_cart( array $items, array $overrides = [] ) {
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
	 * @after
	 */
	public function clear_commited_transactions() {
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
