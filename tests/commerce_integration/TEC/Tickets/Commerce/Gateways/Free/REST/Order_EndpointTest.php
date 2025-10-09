<?php
/**
 * Tests for the Free Gateway Order Endpoint.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Free\REST
 */

namespace TEC\Tickets\Commerce\Gateways\Free\REST;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Cart;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use WP_REST_Request;

/**
 * Class Order_EndpointTest.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Free\REST
 */
class Order_EndpointTest extends WPTestCase {
	use Ticket_Maker;
	use With_Tickets_Commerce;

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
}
