<?php

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce\Cart;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway;
use WP_Post;

class Order_Test extends WPTestCase {
	use Ticket_Maker;

	public function test_it_does_not_create_multiple_orders_for_single_cart() {
		$post = self::factory()->post->create();
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

		$cart = tribe( Cart::class );
		$hash = $cart->get_cart_hash( true );

		$this->assertTrue( ! empty( $hash ) && is_string( $hash ) );

		// Careful!!! Each next order includes previous order's items as well since we are not clearing the cart in between!!
		$order_1 = $this->create_order_from_cart( [ $ticket_id_1 => 1 ] );
		$order_2 = $this->create_order_from_cart( [ $ticket_id_2 => 1 ] );
		$order_3 = $this->create_order_from_cart( [ $ticket_id_1 => 2, $ticket_id_2 => 2 ] );

		$this->assertInstanceof( WP_Post::class, $order_1 );
		$this->assertInstanceof( WP_Post::class, $order_2 );
		$this->assertInstanceof( WP_Post::class, $order_3 );

		// They should be the same order!
		$this->assertEquals( $order_1->ID, $order_2->ID );
		$this->assertEquals( $order_1->ID, $order_3->ID );

		// They should not have the same totals!
		$this->assertEquals( 10, $order_1->total_value->get_decimal() );
		$this->assertEquals( 30, $order_2->total_value->get_decimal() );
		$this->assertEquals( 90, $order_3->total_value->get_decimal() );

		$cart->clear_cart();
		$order_4 = $this->create_order_from_cart( [ $ticket_id_1 => 1 ] );
		$cart->clear_cart();
		$order_5 = $this->create_order_from_cart( [ $ticket_id_2 => 1 ] );
		$cart->clear_cart();
		$order_6 = $this->create_order_from_cart( [ $ticket_id_1 => 2, $ticket_id_2 => 2 ] );
		$cart->clear_cart();

		$this->assertInstanceof( WP_Post::class, $order_4 );
		$this->assertInstanceof( WP_Post::class, $order_5 );
		$this->assertInstanceof( WP_Post::class, $order_6 );

		// They should NOT be the same order!
		$this->assertTrue( $order_4->ID !== $order_5->ID );
		$this->assertTrue( $order_4->ID !== $order_6->ID );
		$this->assertTrue( $order_5->ID !== $order_6->ID );

		// They should have the same totals though.
		$this->assertEquals( 10, $order_4->total_value->get_decimal() );
		$this->assertEquals( 20, $order_5->total_value->get_decimal() );
		$this->assertEquals( 60, $order_6->total_value->get_decimal() );
	}

	protected function create_order_from_cart( array $items, array $overrides = [] ) {
		foreach ( $items as $id => $quantity ) {
			tribe( Cart::class )->get_repository()->add_item( $id, $quantity );
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
}
