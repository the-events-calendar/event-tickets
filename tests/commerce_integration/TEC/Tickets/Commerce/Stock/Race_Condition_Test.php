<?php
/**
 * Tests for race condition prevention in ticket stock management.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Stock
 */

namespace TEC\Tickets\Commerce\Stock;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Stock_Validator;
use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * Class Race_Condition_Test.
 *
 * Tests that the stock validation prevents overselling when multiple users
 * attempt to purchase the same limited-stock ticket simultaneously.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Stock
 */
class Race_Condition_Test extends WPTestCase {

	use Ticket_Maker;
	use Order_Maker;

	/**
	 * Tests that when two users checkout simultaneously, only the first completes successfully.
	 *
	 * This simulates a race condition where:
	 * 1. Two users add the last available ticket to their cart.
	 * 2. User 1 completes checkout successfully.
	 * 3. User 2 attempts checkout but should fail due to insufficient stock.
	 *
	 * @test
	 */
	public function should_prevent_overselling_when_two_users_checkout_simultaneously() {
		$maker    = new Event();
		$event_id = $maker->create();

		// Create a ticket with only 1 available.
		$ticket_id = $this->create_tc_ticket( $event_id, 10, [
			'tribe-ticket' => [
				'capacity' => 1,
			],
		] );

		// Verify initial stock.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_id );
		$this->assertEquals( 1, $ticket->available(), 'There should be 1 ticket available initially' );

		// Simulate User 1: Add ticket to cart, validate, and purchase.
		$cart = tribe( Cart::class );
		$cart->get_repository()->upsert_item( $ticket_id, 1 );

		$stock_validator   = tribe( Stock_Validator::class );
		$validation_result = $stock_validator->validate_cart_stock_with_lock( $cart );
		$this->assertTrue( $validation_result, 'User 1 validation should succeed' );

		// User 1 completes the order.
		$order_1 = $this->create_order( [ $ticket_id => 1 ] );
		$this->assertInstanceOf( \WP_Post::class, $order_1, 'User 1 order should be created successfully' );

		// Verify stock is now 0.
		clean_post_cache( $ticket_id );
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_id );
		$this->assertEquals( 0, $ticket->available(), 'There should be 0 tickets available after User 1 purchase' );

		// Simulate User 2: Add the same ticket to a fresh cart and attempt validation.
		$cart->get_repository()->upsert_item( $ticket_id, 1 );

		$validation_result = $stock_validator->validate_cart_stock_with_lock( $cart );
		$this->assertWPError( $validation_result, 'User 2 validation should fail with WP_Error' );
		$this->assertEquals( 'tec-tc-insufficient-stock', $validation_result->get_error_code() );

		// Verify the error message is user-friendly.
		$error_message = $validation_result->get_error_message();
		$this->assertStringContainsString( 'sold out', $error_message, 'Error message should indicate tickets are sold out' );

		// Clean up cart.
		$cart->clear_cart();
	}

	/**
	 * Tests that validation works correctly with multiple tickets in cart.
	 *
	 * @test
	 */
	public function should_validate_multiple_tickets_in_cart_during_race_condition() {
		$maker    = new Event();
		$event_id = $maker->create();

		// Create two tickets with limited stock.
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10, [
			'tribe-ticket' => [
				'capacity' => 2,
			],
		] );

		$ticket_b_id = $this->create_tc_ticket( $event_id, 20, [
			'tribe-ticket' => [
				'capacity' => 1,
			],
		] );

		$cart = tribe( Cart::class );

		// User 1 adds both tickets to cart and completes order.
		$order_1 = $this->create_order( [
			$ticket_a_id => 1,
			$ticket_b_id => 1,
		] );

		$this->assertInstanceOf( \WP_Post::class, $order_1, 'User 1 order should be created' );

		// User 2 attempts to add both tickets to cart and validate.
		clean_post_cache( $ticket_a_id );
		clean_post_cache( $ticket_b_id );

		$cart->get_repository()->upsert_item( $ticket_a_id, 1 );
		$cart->get_repository()->upsert_item( $ticket_b_id, 1 );

		$stock_validator   = tribe( Stock_Validator::class );
		$validation_result = $stock_validator->validate_cart_stock_with_lock( $cart );
		$this->assertWPError( $validation_result, 'User 2 validation should fail' );

		// Verify ticket A still has 1 available but ticket B is sold out.
		$ticket_a = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		$this->assertEquals( 1, $ticket_a->available(), 'Ticket A should have 1 available' );
		$this->assertEquals( 0, $ticket_b->available(), 'Ticket B should be sold out' );

		// Clean up.
		$cart->clear_cart();
	}

	/**
	 * Tests that validation fails appropriately when requesting more than available.
	 *
	 * @test
	 */
	public function should_fail_when_requesting_more_than_available() {
		$maker    = new Event();
		$event_id = $maker->create();

		// Create ticket with 1 available.
		$ticket_id = $this->create_tc_ticket( $event_id, 10, [
			'tribe-ticket' => [
				'capacity' => 1,
			],
		] );

		// User attempts to purchase 2 tickets when only 1 is available.
		$cart = tribe( Cart::class );
		$cart->get_repository()->upsert_item( $ticket_id, 2 );

		$stock_validator   = tribe( Stock_Validator::class );
		$validation_result = $stock_validator->validate_cart_stock_with_lock( $cart );

		$this->assertWPError( $validation_result, 'Validation should fail when requesting more than available' );
		$this->assertEquals( 'tec-tc-insufficient-stock', $validation_result->get_error_code() );

		// Verify error message mentions the quantity discrepancy.
		$error_message = $validation_result->get_error_message();
		$this->assertStringContainsString( 'You requested 2', $error_message, 'Error should mention requested quantity' );
		$this->assertStringContainsString( 'only 1 available', $error_message, 'Error should mention available quantity' );

		// Clean up.
		$cart->clear_cart();
	}

	/**
	 * Tests that unlimited capacity tickets are never blocked.
	 *
	 * @test
	 */
	public function should_allow_unlimited_capacity_tickets_during_race_condition() {
		$maker    = new Event();
		$event_id = $maker->create();

		// Create ticket with unlimited capacity.
		$ticket_id = $this->create_tc_ticket( $event_id, 10, [
			'tribe-ticket' => [
				'capacity' => -1,
			],
		] );

		$cart            = tribe( Cart::class );
		$stock_validator = tribe( Stock_Validator::class );

		// User 1 validates with 5 tickets.
		$cart->get_repository()->upsert_item( $ticket_id, 5 );
		$validation_1 = $stock_validator->validate_cart_stock_with_lock( $cart );
		$this->assertTrue( $validation_1, 'Unlimited ticket validation 1 should succeed' );

		// User 1 completes order.
		$order_1 = $this->create_order( [ $ticket_id => 5 ] );
		$this->assertInstanceOf( \WP_Post::class, $order_1, 'Unlimited ticket order 1 should be created' );

		// User 2 validates with 10 tickets.
		$cart->get_repository()->upsert_item( $ticket_id, 10 );
		$validation_2 = $stock_validator->validate_cart_stock_with_lock( $cart );
		$this->assertTrue( $validation_2, 'Unlimited ticket validation 2 should succeed' );

		// User 2 completes order.
		$order_2 = $this->create_order( [ $ticket_id => 10 ] );
		$this->assertInstanceOf( \WP_Post::class, $order_2, 'Unlimited ticket order 2 should be created' );

		// Clean up.
		$cart->clear_cart();
	}

	/**
	 * Tests that stock validation occurs at the right status transition.
	 *
	 * @test
	 */
	public function should_validate_stock_during_order_status_transition() {
		$maker    = new Event();
		$event_id = $maker->create();

		// Create ticket with 1 available.
		$ticket_id = $this->create_tc_ticket( $event_id, 10, [
			'tribe-ticket' => [
				'capacity' => 1,
			],
		] );

		// User 1 completes purchase.
		$cart_1 = new Cart();
		$cart_1->get_repository()->upsert_item( $ticket_id, 1 );

		$order_1 = $this->create_order_from_cart();
		$cart_1->clear_cart();

		$this->assertInstanceOf( \WP_Post::class, $order_1, 'Order should be created' );

		// Verify ticket is now sold out.
		clean_post_cache( $ticket_id );
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_id );
		$this->assertEquals( 0, $ticket->available(), 'Ticket should be sold out' );

		// Create a second order without status transitions (simulating direct DB insert).
		$cart_2 = new Cart();
		$cart_2->get_repository()->upsert_item( $ticket_id, 1 );

		$order_2 = $this->create_order_without_transitions();
		$cart_2->clear_cart();

		// Attempting to transition to Pending should fail due to stock validation.
		$orders          = tribe( \TEC\Tickets\Commerce\Order::class );
		$can_transition  = $orders->can_transition_to( tribe( \TEC\Tickets\Commerce\Status\Pending::class ), $order_2->ID );

		$this->assertFalse( $can_transition, 'Transition should fail when stock is insufficient' );
	}
}

