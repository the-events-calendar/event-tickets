<?php
/**
 * Tests for the Free Gateway Order Endpoint.
 *
 * @since 5.30.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Free\REST
 */

namespace TEC\Tickets\Commerce\Gateways\Free\REST;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Ticket;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use WP_Query;
use WP_REST_Request;

/**
 * Class Order_EndpointTest.
 *
 * @since 5.30.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Free\REST
 */
class Order_EndpointTest extends WPTestCase {
	use Ticket_Maker;
	use With_Tickets_Commerce;

	/**
	 * Ensure cart is clean before each test.
	 *
	 * @before
	 */
	public function ensure_clean_cart() {
		$cart = tribe( Cart::class );
		$cart->clear_cart();
	}

	/**
	 * Clean up cart after each test.
	 *
	 * @after
	 */
	public function cleanup_cart() {
		$cart = tribe( Cart::class );
		$cart->clear_cart();
	}

	/**
	 * @test
	 * @covers Order_Endpoint::handle_create_order
	 */
	public function should_accept_cart_with_only_free_tickets() {
		// Create an "event" with only free tickets.
		$event_id       = self::factory()->post->create( [ 'post_title' => 'Free Event' ] );
		$free_ticket_id = $this->create_tc_ticket( $event_id, 0 );

		// Add free ticket to cart.
		$cart = tribe( Cart::class );
		$cart->add_ticket( $free_ticket_id, 3 );

		// Verify cart has items.
		$items = $cart->get_repository()->get_items_in_cart();
		$this->assertNotEmpty( $items, 'Cart should have items' );

		// Create the REST request.
		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/commerce/free/order' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'purchaser' => [
						'name'  => 'Test User',
						'email' => 'test@example.com',
					],
				]
			)
		);

		// Dispatch the request through the REST API.
		$response = rest_do_request( $request );

		// WITH THE FIX: Free tickets should still be accepted
		$this->assertFalse( $response->is_error(), 'Response should not be an error' );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertNotEmpty( $data['id'] );
		$this->assertNotEmpty( $data['redirect_url'] );
	}

	/**
	 * @test
	 * @covers Order_Endpoint::handle_create_order
	 */
	public function should_reject_cart_with_paid_tickets() {
		// Create an "event" and a paid ticket.
		$event_id  = self::factory()->post->create( [ 'post_title' => 'Paid Event' ] );
		$ticket_id = $this->create_tc_ticket( $event_id, 25.00 );

		// Add paid ticket to cart.
		$cart = tribe( Cart::class );
		$cart->add_ticket( $ticket_id, 2 );

		// Verify cart has items and total is correct.
		$items = $cart->get_repository()->get_items_in_cart();
		$this->assertNotEmpty( $items, 'Cart should have items' );
		$this->assertEquals( 50.0, $cart->get_cart_total(), 'Cart total should be 50.0' );

		// Create the REST request.
		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/commerce/free/order' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'purchaser' => [
						'name'  => 'Test User',
						'email' => 'test@example.com',
					],
				]
			)
		);

		// Dispatch the request through the REST API.
		$response = rest_do_request( $request );

		// WITH THE FIX: This should return an error for paid cart
		$this->assertTrue( $response->is_error(), 'Response should be an error for paid cart' );
		$this->assertEquals( 400, $response->get_status() );
		$error = $response->as_error();
		$this->assertEquals( 'tec_tickets_commerce_free_order_invalid_cart', $error->get_error_code() );
		$this->assertStringContainsString( 'payment', strtolower( $error->get_error_message() ) );
	}

	/**
	 * @test
	 * @covers Order_Endpoint::handle_create_order
	 */
	public function should_reject_partially_paid_cart() {
		// Create an "event" with a paid ticket and a free ticket.
		$event_id       = self::factory()->post->create( [ 'post_title' => 'Mixed Event' ] );
		$paid_ticket_id = $this->create_tc_ticket( $event_id, 10.00 );
		$free_ticket_id = $this->create_tc_ticket( $event_id, 0 );

		// Add both tickets to cart.
		$cart = tribe( Cart::class );
		$cart->add_ticket( $paid_ticket_id, 1 );
		$cart->add_ticket( $free_ticket_id, 5 );

		// Verify cart has items and total is correct.
		$items = $cart->get_repository()->get_items_in_cart();
		$this->assertNotEmpty( $items, 'Cart should have items' );
		$this->assertEquals( 10.0, $cart->get_cart_total(), 'Cart total should be 10.0' );

		// Create the REST request.
		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/commerce/free/order' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'purchaser' => [
						'name'  => 'Test User',
						'email' => 'test@example.com',
					],
				]
			)
		);

		// Dispatch the request through the REST API.
		$response = rest_do_request( $request );

		// WITH THE FIX: This should return an error for partially paid cart
		$this->assertTrue( $response->is_error(), 'Response should be an error for partially paid cart' );
		$this->assertEquals( 400, $response->get_status() );
		$error = $response->as_error();
		$this->assertEquals( 'tec_tickets_commerce_free_order_invalid_cart', $error->get_error_code() );
	}

	/**
	 * @test
	 * @covers Order_Endpoint::handle_create_order
	 */
	public function should_reject_cart_offset_by_a_negative_priced_ticket() {
		$price              = 10.00;
		$event_id           = self::factory()->post->create( [ 'post_title' => 'Offset Event' ] );
		$paid_ticket_id     = $this->create_tc_ticket( $event_id, $price );
		$negative_ticket_id = $this->create_tc_ticket( $event_id, $price );

		// Written straight to meta: no save path stores a negative price, but the endpoint must not rely on that.
		update_post_meta( $negative_ticket_id, Ticket::$price_meta_key, -$price );

		$cart = tribe( Cart::class );
		$cart->add_ticket( $paid_ticket_id, 1 );
		$cart->add_ticket( $negative_ticket_id, 1 );

		$this->assertEquals( 0.0, $cart->get_cart_total(), 'The negative ticket should offset the paid one.' );

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/commerce/free/order' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'purchaser' => [
						'name'  => 'Test User',
						'email' => 'test@example.com',
					],
				]
			)
		);

		$response = rest_do_request( $request );

		$this->assertTrue( $response->is_error(), 'Response should be an error for a cart offset by a negative price' );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'tec_tickets_commerce_free_order_invalid_cart', $response->as_error()->get_error_code() );
	}

	/**
	 * @test
	 * @covers Order_Endpoint::handle_create_order
	 */
	public function should_reject_empty_cart() {
		// Create an empty cart.
		$cart = tribe( Cart::class );
		$cart->clear_cart();

		// Verify cart is empty.
		$items = $cart->get_repository()->get_items_in_cart();
		$this->assertEmpty( $items, 'Cart should be empty' );

		// Create the REST request.
		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/commerce/free/order' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'purchaser' => [
						'name'  => 'Test User',
						'email' => 'test@example.com',
					],
				]
			)
		);

		// Dispatch the request through the REST API.
		$response = rest_do_request( $request );

		// WITH THE FIX: Empty carts should be rejected
		$this->assertTrue( $response->is_error(), 'Response should be an error for empty cart' );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * @test
	 * @covers Order_Endpoint::handle_create_order
	 */
	public function should_reject_purchaser_name_with_angle_brackets() {
		$event_id       = self::factory()->post->create( [ 'post_title' => 'Free Event' ] );
		$free_ticket_id = $this->create_tc_ticket( $event_id, 0 );

		tribe( Cart::class )->add_ticket( $free_ticket_id, 1 );

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/commerce/free/order' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'purchaser' => [
						'name'  => 'Bob Visitor< b >',
						'email' => 'new.visitor@example.test',
					],
				]
			)
		);

		$response = rest_do_request( $request );

		$this->assertTrue( $response->is_error(), 'A purchaser name containing markup should be rejected' );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'invalid-purchaser-info', $response->as_error()->get_error_code() );
	}

	/**
	 * @test
	 * @covers Order_Endpoint::handle_create_order
	 */
	public function should_reject_a_guest_order_when_purchase_requires_login() {
		$event_id  = self::factory()->post->create( [ 'post_title' => 'Members Only Event' ] );
		$ticket_id = $this->create_tc_ticket( $event_id, 0, [ 'tribe-ticket' => [ 'mode' => 'own', 'capacity' => 1 ] ] );
		tribe( Cart::class )->add_ticket( $ticket_id, 1 );
		$this->require_purchase_login( true );
		wp_set_current_user( 0 );
		$orders_before = $this->order_count();

		$response = rest_do_request( $this->free_order_request() );

		$this->assertTrue( $response->is_error() );
		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'tec_tickets_commerce_login_required', $response->as_error()->get_error_code() );
		$this->assertSame( $orders_before, $this->order_count() );
		$this->assertEquals( 1, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * @test
	 * @covers Order_Endpoint::handle_create_order
	 */
	public function should_accept_a_logged_in_order_when_purchase_requires_login() {
		$event_id  = self::factory()->post->create( [ 'post_title' => 'Members Only Event' ] );
		$ticket_id = $this->create_tc_ticket( $event_id, 0 );
		tribe( Cart::class )->add_ticket( $ticket_id, 1 );
		$this->require_purchase_login( true );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$orders_before = $this->order_count();

		$response = rest_do_request( $this->free_order_request() );

		$this->assertFalse( $response->is_error() );
		$this->assertTrue( $response->get_data()['success'] );
		$this->assertSame( $orders_before + 1, $this->order_count() );
	}

	/**
	 * Builds a free-order request with a valid purchaser.
	 *
	 * @return WP_REST_Request The request.
	 */
	private function free_order_request(): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/commerce/free/order' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'purchaser' => [
						'name'  => 'Test User',
						'email' => 'test@example.com',
					],
				]
			)
		);

		return $request;
	}

	/**
	 * Counts the Tickets Commerce orders in any status.
	 *
	 * @return int The number of orders.
	 */
	private function order_count(): int {
		return ( new WP_Query(
			[
				'post_type'      => Order::POSTTYPE,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
			]
		) )->found_posts;
	}

	/**
	 * Turns the "Require users to log in before they purchase tickets" setting on or off for this test.
	 *
	 * A filter rather than the option, because the settings cache outlives the test's database rollback.
	 *
	 * @param bool $required Whether purchase requires login.
	 */
	private function require_purchase_login( bool $required ): void {
		add_filter(
			'tribe_get_option_ticket-authentication-requirements',
			static fn() => $required ? [ 'event-tickets_all' ] : []
		);
	}
}
