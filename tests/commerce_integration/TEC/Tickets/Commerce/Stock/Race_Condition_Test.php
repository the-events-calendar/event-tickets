<?php
/**
 * Tests for race condition prevention in ticket stock management.
 *
 * @since 5.30.0
 *
 * @package TEC\Tickets\Commerce\Stock
 */

namespace TEC\Tickets\Commerce\Stock;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Manual\Gateway;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Denied;
use TEC\Tickets\Commerce\Status\Not_Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Voided;
use TEC\Tickets\Commerce\Stock_Validator;
use TEC\Tickets\Commerce\Ticket;
use Tribe__Tickets__Global_Stock as Global_Stock;
use WP_Error;
use WP_Post;
use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * Class Race_Condition_Test.
 *
 * Tests that the stock validation prevents overselling when multiple users
 * attempt to purchase the same limited-stock ticket simultaneously.
 *
 * @since 5.30.0
 *
 * @package TEC\Tickets\Commerce\Stock
 */
class Race_Condition_Test extends WPTestCase {

	use Ticket_Maker;
	use Order_Maker;

	/**
	 * The number of checkouts started by the test, so each has its own cart hash.
	 *
	 * @var int
	 */
	private int $checkouts = 0;

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
		$orders          = tribe( Order::class );
		$can_transition  = $orders->can_transition_to( tribe( Pending::class ), $order_2->ID );

		$this->assertFalse( $can_transition, 'Transition should fail when stock is insufficient' );
	}

	/**
	 * A concurrent checkout can take the last ticket after this request cached the ticket: the
	 * stock check of the transition must read the stored stock, not the cached ticket.
	 *
	 * @test
	 */
	public function should_refuse_the_stock_transition_when_the_stored_stock_ran_out_after_the_ticket_was_cached() {
		$event_id  = ( new Event() )->create();
		$ticket_id = $this->create_tc_ticket( $event_id, 10, [ 'tribe-ticket' => [ 'capacity' => 1 ] ] );

		$cart = new Cart();
		$cart->get_repository()->upsert_item( $ticket_id, 1 );
		$order = $this->create_order_without_transitions();
		$cart->clear_cart();

		// This request has cached the ticket with 1 in stock.
		$this->assertEquals( 1, tribe( Module::class )->get_ticket( $event_id, $ticket_id )->stock() );

		// Another checkout takes the last ticket; this request's caches do not see it.
		global $wpdb;
		$wpdb->update( $wpdb->postmeta, [ 'meta_value' => 0 ], [ 'post_id' => $ticket_id, 'meta_key' => '_stock' ] );

		$can_transition = tribe( Order::class )->can_transition_to( tribe( Pending::class ), $order->ID );

		$this->assertFalse( $can_transition );
	}

	/**
	 * The stock decrease must start from the stored stock and sales, or two checkouts write the same value.
	 *
	 * @test
	 */
	public function should_decrease_the_stored_stock_and_sales_when_the_cached_ticket_is_stale() {
		$event_id  = ( new Event() )->create();
		$ticket_id = $this->create_tc_ticket( $event_id, 10, [ 'tribe-ticket' => [ 'capacity' => 3 ] ] );

		// This request has cached the ticket with 3 in stock and no sales.
		$this->assertEquals( 3, tribe( Module::class )->get_ticket( $event_id, $ticket_id )->stock() );
		get_post_meta( $ticket_id, Ticket::$sales_meta_key, true );

		// Another checkout sells one.
		$this->write_from_another_request( $ticket_id, Ticket::$stock_meta_key, 2 );
		$this->write_from_another_request( $ticket_id, Ticket::$sales_meta_key, 1 );

		$order = $this->create_order( [ $ticket_id => 1 ] );

		$this->assertInstanceOf( WP_Post::class, $order );
		clean_post_cache( $ticket_id );
		$this->assertEquals( 1, get_post_meta( $ticket_id, Ticket::$stock_meta_key, true ) );
		$this->assertEquals( 2, get_post_meta( $ticket_id, Ticket::$sales_meta_key, true ) );
	}

	/**
	 * Shared-capacity tickets must be checked against the event's stored shared capacity.
	 *
	 * @test
	 */
	public function should_refuse_a_shared_capacity_transition_when_the_stored_shared_capacity_ran_out() {
		$event_id = ( new Event() )->create();
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 2 );
		$ticket_id = $this->create_tc_ticket(
			$event_id,
			10,
			[ 'tribe-ticket' => [ 'mode' => Global_Stock::GLOBAL_STOCK_MODE, 'capacity' => 2 ] ]
		);

		$cart = new Cart();
		$cart->get_repository()->upsert_item( $ticket_id, 1 );
		$order = $this->create_order_without_transitions();
		$cart->clear_cart();

		$this->assertEquals( 2, tribe( Module::class )->get_ticket( $event_id, $ticket_id )->stock() );

		// Other checkouts used the shared capacity up.
		$this->write_from_another_request( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 0 );

		$this->assertFalse( tribe( Order::class )->can_transition_to( tribe( Pending::class ), $order->ID ) );
	}

	/**
	 * Two shared-capacity tickets in one order must fit in the shared capacity together.
	 *
	 * @test
	 */
	public function should_refuse_an_order_whose_shared_capacity_tickets_together_exceed_the_shared_capacity() {
		$event_id = ( new Event() )->create();
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 2 );
		$ticket_a = $this->create_tc_ticket( $event_id, 10, [ 'tribe-ticket' => [ 'mode' => Global_Stock::GLOBAL_STOCK_MODE, 'capacity' => 2 ] ] );
		$ticket_b = $this->create_tc_ticket( $event_id, 10, [ 'tribe-ticket' => [ 'mode' => Global_Stock::GLOBAL_STOCK_MODE, 'capacity' => 2 ] ] );

		$cart = new Cart();
		$cart->get_repository()->upsert_item( $ticket_a, 2 );
		$cart->get_repository()->upsert_item( $ticket_b, 1 );

		$this->assertWPError( tribe( Stock_Validator::class )->validate_cart_stock_with_lock( $cart ) );
		$cart->clear_cart();
	}

	/**
	 * A required previous status that is refused must stop the whole status change.
	 *
	 * @test
	 */
	public function should_not_complete_an_order_whose_pending_transition_was_refused() {
		$event_id  = ( new Event() )->create();
		$ticket_id = $this->create_tc_ticket( $event_id, 10, [ 'tribe-ticket' => [ 'capacity' => 1 ] ] );

		$cart = new Cart();
		$cart->get_repository()->upsert_item( $ticket_id, 1 );
		$order = $this->create_order_without_transitions();
		$cart->clear_cart();

		$this->write_from_another_request( $ticket_id, Ticket::$stock_meta_key, 0 );

		$this->assertFalse( tribe( Order::class )->modify_status( $order->ID, Completed::SLUG ) );
		clean_post_cache( $order->ID );
		$this->assertNotEquals( tribe( Completed::class )->get_wp_slug(), get_post_status( $order->ID ) );
	}

	/**
	 * A live checkout holds its seats: a second buyer is refused before paying.
	 *
	 * @test
	 */
	public function should_refuse_a_checkout_when_another_live_checkout_holds_the_last_seat() {
		$event_id  = ( new Event() )->create();
		$ticket_id = $this->create_tc_ticket( $event_id, 10, [ 'tribe-ticket' => [ 'capacity' => 1 ] ] );

		$this->assertInstanceOf( WP_Post::class, $this->hold_checkout( $ticket_id, 1 ) );

		$second = $this->hold_checkout( $ticket_id, 1 );

		$this->assertWPError( $second );
		$this->assertEquals( 'tec-tc-insufficient-stock', $second->get_error_code() );
	}

	/**
	 * An abandoned checkout frees its seats once its hold expires.
	 *
	 * @test
	 */
	public function should_not_count_an_expired_hold_against_the_stock() {
		$event_id  = ( new Event() )->create();
		$ticket_id = $this->create_tc_ticket( $event_id, 10, [ 'tribe-ticket' => [ 'capacity' => 1 ] ] );

		$expired = static fn() => 0;
		add_filter( 'tec_tickets_commerce_order_on_checkout_screen_hold_timeout', $expired );
		$this->assertInstanceOf( WP_Post::class, $this->hold_checkout( $ticket_id, 1 ) );
		remove_filter( 'tec_tickets_commerce_order_on_checkout_screen_hold_timeout', $expired );

		$this->assertInstanceOf( WP_Post::class, $this->hold_checkout( $ticket_id, 1 ) );
	}

	/**
	 * The holder of the seat can complete; an order without a hold cannot take the held seat.
	 *
	 * @test
	 */
	public function should_let_the_holder_complete_and_refuse_and_log_an_order_that_takes_a_held_seat() {
		$event_id  = ( new Event() )->create();
		$ticket_id = $this->create_tc_ticket( $event_id, 10, [ 'tribe-ticket' => [ 'capacity' => 1 ] ] );

		$held = $this->hold_checkout( $ticket_id, 1 );
		$this->assertInstanceOf( WP_Post::class, $held );

		// A late payer whose hold expired, modelled as an order with no hold.
		$cart = new Cart();
		$cart->get_repository()->upsert_item( $ticket_id, 1 );
		$late = $this->create_order_without_transitions();
		$cart->clear_cart();

		$logged = did_action( 'tribe_log' );
		$this->assertFalse( tribe( Order::class )->modify_status( $late->ID, Pending::SLUG ) );
		$this->assertGreaterThan( $logged, did_action( 'tribe_log' ) );

		$this->assertTrue( tribe( Order::class )->modify_status( $held->ID, Pending::SLUG ) );
		clean_post_cache( $ticket_id );
		$this->assertEquals( 0, get_post_meta( $ticket_id, Ticket::$stock_meta_key, true ) );
	}

	/**
	 * Once an order decreases the stock, its hold is released: the seat is not counted twice.
	 *
	 * @test
	 */
	public function should_release_the_hold_when_the_order_decreases_the_stock() {
		$event_id  = ( new Event() )->create();
		$ticket_id = $this->create_tc_ticket( $event_id, 10, [ 'tribe-ticket' => [ 'capacity' => 2 ] ] );

		$held = $this->hold_checkout( $ticket_id, 1 );
		$this->assertTrue( tribe( Order::class )->modify_status( $held->ID, Pending::SLUG ) );

		$this->assertInstanceOf( WP_Post::class, $this->hold_checkout( $ticket_id, 1 ) );
	}

	/**
	 * A checkout that fails, is denied or is voided gives its held seat back at once, not when its hold expires.
	 *
	 * @test
	 * @dataProvider unpaid_end_statuses
	 */
	public function should_release_the_hold_when_the_order_can_no_longer_be_paid( string $status ) {
		$event_id  = ( new Event() )->create();
		$ticket_id = $this->create_tc_ticket( $event_id, 10, [ 'tribe-ticket' => [ 'capacity' => 1 ] ] );

		$held = $this->hold_checkout( $ticket_id, 1 );
		$this->assertInstanceOf( WP_Error::class, $this->hold_checkout( $ticket_id, 1 ) );

		$this->assertTrue( tribe( Order::class )->modify_status( $held->ID, $status ) );

		$this->assertInstanceOf( WP_Post::class, $this->hold_checkout( $ticket_id, 1 ) );
	}

	/**
	 * The statuses that end a checkout without a payment.
	 *
	 * @return array<string,array{0:string}>
	 */
	public function unpaid_end_statuses(): array {
		return [
			'denied'        => [ Denied::SLUG ],
			'not completed' => [ Not_Completed::SLUG ],
			'voided'        => [ Voided::SLUG ],
		];
	}

	/**
	 * MariaDB's snapshot isolation fails a transaction that writes a row another request changed: it is off before an order transaction.
	 *
	 * @test
	 */
	public function should_turn_snapshot_isolation_off_before_an_order_transaction() {
		global $wpdb;
		$variable = $wpdb->get_row( "SHOW SESSION VARIABLES LIKE 'innodb_snapshot_isolation'" );

		if ( null === $variable ) {
			$this->markTestSkipped( 'This database has no snapshot isolation setting.' );
		}

		$wpdb->query( 'SET SESSION innodb_snapshot_isolation = ON' );

		$event_id  = ( new Event() )->create();
		$ticket_id = $this->create_tc_ticket( $event_id, 10, [ 'tribe-ticket' => [ 'capacity' => 2 ] ] );
		$this->assertInstanceOf( WP_Post::class, $this->create_order( [ $ticket_id => 1 ] ) );

		$this->assertEquals( 'OFF', strtoupper( $wpdb->get_row( "SHOW SESSION VARIABLES LIKE 'innodb_snapshot_isolation'" )->Value ) );
	}

	/**
	 * Writes a meta value the way a concurrent request would: in the database, behind this request's caches.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $key     The meta key.
	 * @param mixed  $value   The meta value.
	 */
	private function write_from_another_request( int $post_id, string $key, $value ): void {
		global $wpdb;
		$updated = $wpdb->update( $wpdb->postmeta, [ 'meta_value' => $value ], [ 'post_id' => $post_id, 'meta_key' => $key ] );

		if ( ! $updated ) {
			$wpdb->insert( $wpdb->postmeta, [ 'post_id' => $post_id, 'meta_key' => $key, 'meta_value' => $value ] );
		}
	}

	/**
	 * Creates an order from a fresh cart while holding its stock, as the paid gateways do before the charge.
	 *
	 * @param int $ticket_id The ticket ID.
	 * @param int $quantity  The quantity.
	 *
	 * @return WP_Post|WP_Error|false
	 */
	private function hold_checkout( int $ticket_id, int $quantity ) {
		$cart = tribe( Cart::class );
		$cart->clear_cart();
		++$this->checkouts;
		$cart->set_cart_hash( md5( 'checkout-' . $this->checkouts ) );
		$cart->get_repository()->upsert_item( $ticket_id, $quantity );

		$order = tribe( Order::class )->create_from_cart_holding_stock(
			tribe( Gateway::class ),
			[
				'purchaser_user_id'    => 0,
				'purchaser_full_name'  => 'Test Purchaser',
				'purchaser_first_name' => 'Test',
				'purchaser_last_name'  => 'Purchaser',
				'purchaser_email'      => "buyer-{$this->checkouts}@test.com",
			]
		);

		$cart->clear_cart();

		return $order;
	}
}
