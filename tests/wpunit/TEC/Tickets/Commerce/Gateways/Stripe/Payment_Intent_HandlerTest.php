<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Coupon;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Coupon_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use WP_Post;

/**
 * Class Payment_Intent_HandlerTest
 *
 * @since TBD
 */
class Payment_Intent_HandlerTest extends \Codeception\TestCase\WPTestCase {

	use Coupon_Creator;
	use Ticket_Maker;
	use With_Tickets_Commerce;
	use With_Uopz;

	/**
	 * @var WP_Post
	 */
	protected $event;

	/**
	 * @var int
	 */
	protected $ticket_id_1;

	/**
	 * @var int
	 */
	protected $ticket_id_2;

	/**
	 * @var Coupon
	 */
	protected $coupon;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create a test event and tickets.
		$this->event = static::factory()->post->create( [ 'post_title' => 'Test Event' ] );
		$this->ticket_id_1 = $this->create_tc_ticket( $this->event, 10 ); // $10 ticket.
		$this->ticket_id_2 = $this->create_tc_ticket( $this->event, 15 ); // $15 ticket.

		// Create a 20% discount coupon.
		$this->coupon = $this->create_coupon(
			[
				'raw_amount' => 20,
				'sub_type'   => 'percent',
				'slug'       => 'test-coupon',
				'display_name' => 'Test Coupon',
			]
		);
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		// Clear the cart after each test to ensure isolation.
		$cart = tribe( Cart::class );
		$cart->get_repository()->clear();

		parent::tearDown();
	}

	/**
	 * Test that Payment_Intent::create_from_cart correctly calculates amount with coupons applied.
	 * This tests the core fix - ensuring the cart total (with coupons) is used instead of recalculating from individual items.
	 *
	 * @test
	 */
	public function it_should_create_payment_intent_with_correct_amount_and_coupons() {
		// Set up cart with ticket and coupon.
		$cart = tribe( Cart::class );
		$cart->add_ticket( $this->ticket_id_1, 1 ); // Add $10 ticket.
		$this->coupon->add_to_cart( $cart->get_repository() ); // Add 20% discount.

		// Verify the cart total includes the discount.
		$cart_total = $cart->get_cart_total();
		$this->assertEquals( 8.0, $cart_total, 'Cart total should be $8.00 with 20% discount' );

		// Mock Requests::post to capture the arguments passed to Stripe.
		$captured_args = null;
		$this->set_fn_return( 'TEC\Tickets\Commerce\Gateways\Stripe\Requests::post', function( $url, $query_args, $args ) use ( &$captured_args ) {
			$captured_args = $args;
			return [ 'id' => 'pi_test_123', 'amount' => $args['amount'] ];
		} );

		// Call create_from_cart.
		$result = Payment_Intent::create_from_cart( $cart );

		// Assert that the result was created.
		$this->assertNotEmpty( $result, 'Payment Intent should be created' );

		// Assert that the correct amount was passed to Stripe.
		$this->assertNotNull( $captured_args, 'Requests::post should have been called' );
		$this->assertEquals( '800', $captured_args['amount'], 'Stripe should receive 800 cents ($8.00) for discounted cart' );
	}

	/**
	 * Test that Payment_Intent::create_from_cart correctly calculates amount without coupons.
	 *
	 * @test
	 */
	public function it_should_create_payment_intent_with_correct_amount_without_coupons() {
		// Set up cart with ticket only (no coupons).
		$cart = tribe( Cart::class );
		$cart->add_ticket( $this->ticket_id_1, 1 ); // Add $10 ticket.

		// Verify the cart total is the original amount.
		$cart_total = $cart->get_cart_total();
		$this->assertEquals( 10.0, $cart_total, 'Cart total should be $10.00 without discounts' );

		// Mock Requests::post to capture the arguments passed to Stripe.
		$captured_args = null;
		$this->set_fn_return( 'TEC\Tickets\Commerce\Gateways\Stripe\Requests::post', function( $url, $query_args, $args ) use ( &$captured_args ) {
			$captured_args = $args;
			return [ 'id' => 'pi_test_123', 'amount' => $args['amount'] ];
		} );

		// Call create_from_cart.
		$result = Payment_Intent::create_from_cart( $cart );

		// Assert that the result was created.
		$this->assertNotEmpty( $result, 'Payment Intent should be created' );

		// Assert that the correct amount was passed to Stripe.
		$this->assertNotNull( $captured_args, 'Requests::post should have been called' );
		$this->assertEquals( '1000', $captured_args['amount'], 'Stripe should receive 1000 cents ($10.00) for non-discounted cart' );
	}

	/**
	 * Test that Payment_Intent::create_from_cart works with multiple tickets and coupons.
	 *
	 * @test
	 */
	public function it_should_create_payment_intent_with_correct_amount_multiple_tickets_and_coupons() {
		// Set up cart with multiple tickets and coupon.
		$cart = tribe( Cart::class );
		$cart->add_ticket( $this->ticket_id_1, 2 ); // Add 2x $10 tickets = $20.
		$cart->add_ticket( $this->ticket_id_2, 1 ); // Add 1x $15 ticket = $15.
		// Total: $35, with 20% discount = $28.
		$this->coupon->add_to_cart( $cart->get_repository() );

		// Verify the cart total includes the discount.
		$cart_total = $cart->get_cart_total();
		$this->assertEquals( 28.0, $cart_total, 'Cart total should be $28.00 with 20% discount on $35' );

		// Mock Requests::post to capture the arguments passed to Stripe.
		$captured_args = null;
		$this->set_fn_return( 'TEC\Tickets\Commerce\Gateways\Stripe\Requests::post', function( $url, $query_args, $args ) use ( &$captured_args ) {
			$captured_args = $args;
			return [ 'id' => 'pi_test_123', 'amount' => $args['amount'] ];
		} );

		// Call create_from_cart.
		$result = Payment_Intent::create_from_cart( $cart );

		// Assert that the result was created.
		$this->assertNotEmpty( $result, 'Payment Intent should be created' );

		// Assert that the correct amount was passed to Stripe.
		$this->assertNotNull( $captured_args, 'Requests::post should have been called' );
		$this->assertEquals( '2800', $captured_args['amount'], 'Stripe should receive 2800 cents ($28.00) for discounted multi-ticket cart' );
	}

	/**
	 * Test that Payment_Intent::create_from_cart handles empty cart correctly.
	 *
	 * @test
	 */
	public function it_should_handle_empty_cart_correctly() {
		// Set up empty cart.
		$cart = tribe( Cart::class );

		// Verify the cart total is zero.
		$cart_total = $cart->get_cart_total();
		$this->assertEquals( 0.0, $cart_total, 'Cart total should be $0.00 for empty cart' );

		// Mock Requests::post to ensure it's not called.
		$requests_called = false;
		$this->set_fn_return( 'TEC\Tickets\Commerce\Gateways\Stripe\Requests::post', function() use ( &$requests_called ) {
			$requests_called = true;
			return [ 'id' => 'pi_test_123' ];
		} );

		// Call create_from_cart.
		$result = Payment_Intent::create_from_cart( $cart );

		// Assert that no Payment Intent was created and Requests::post was not called.
		$this->assertEmpty( $result, 'No Payment Intent should be created for empty cart' );
		$this->assertFalse( $requests_called, 'Requests::post should not be called for empty cart' );
	}

	/**
	 * Test that Payment_Intent::create_from_cart applies filters correctly.
	 *
	 * @test
	 */
	public function it_should_apply_filter_correctly() {
		// Set up cart with ticket.
		$cart = tribe( Cart::class );
		$cart->add_ticket( $this->ticket_id_1, 1 ); // Add $10 ticket.

		// Add a filter to modify the value.
		add_filter( 'tec_tickets_commerce_stripe_create_from_cart', function( $value ) {
			// Return a modified value (e.g., add a fee).
			return Value::create( $value->get_decimal() + 1.0 ); // Add $1.00.
		} );

		// Mock Requests::post to capture the arguments passed to Stripe.
		$captured_args = null;
		$this->set_fn_return( 'TEC\Tickets\Commerce\Gateways\Stripe\Requests::post', function( $url, $query_args, $args ) use ( &$captured_args ) {
			$captured_args = $args;
			return [ 'id' => 'pi_test_123', 'amount' => $args['amount'] ];
		} );

		// Call create_from_cart.
		$result = Payment_Intent::create_from_cart( $cart );

		// Assert that the result was created.
		$this->assertNotEmpty( $result, 'Payment Intent should be created' );

		// Assert that the correct amount was passed to Stripe (original $10.00 + $1.00 filter = $11.00).
		$this->assertNotNull( $captured_args, 'Requests::post should have been called' );
		$this->assertEquals( '1100', $captured_args['amount'], 'Stripe should receive 1100 cents ($11.00) after filter applied' );

		// Clean up the filter.
		remove_all_filters( 'tec_tickets_commerce_stripe_create_from_cart' );
	}
}
