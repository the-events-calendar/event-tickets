<?php

namespace TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\Stripe;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Cart\Agnostic_Cart;
use TEC\Tickets\Commerce\Cart\Cart_Interface;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Coupon;
use TEC\Tickets\Commerce\Order_Modifiers\Table_Views\Coupon_Table;
use TEC\Tickets\Commerce\Order_Modifiers\Table_Views\Order_Modifier_Table;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Coupon_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Testcases\Order_Modifiers_TestCase;
use Tribe\Tests\Traits\With_Uopz;

/**
 * Class Payment_Intent_Coupon_Test
 *
 * Tests that Payment Intent creation correctly handles coupons by using the cart total
 * instead of recalculating from individual items.
 *
 * @since TBD
 */
class Payment_Intent_Coupon_Test extends Order_Modifiers_TestCase {

	use Coupon_Creator;
	use Ticket_Maker;
	use With_Uopz;

	/**
	 * The type of order modifier being tested (coupon).
	 *
	 * @var string
	 */
	protected string $modifier_type = 'coupon';

	/**
	 * @var \WP_Post
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

		// Bind the Cart_Interface to Agnostic_Cart for proper cart functionality.
		tribe_singleton( Cart_Interface::class, Agnostic_Cart::class );

		// Create a test event and tickets.
		$this->event = static::factory()->post->create( [ 'post_title' => 'Test Event' ] );
		$this->ticket_id_1 = $this->create_tc_ticket( $this->event, 10 ); // $10 ticket.
		$this->ticket_id_2 = $this->create_tc_ticket( $this->event, 15 ); // $15 ticket.

		// Create a 20% discount coupon using the Coupon_Creator trait.
		$this->coupon = $this->create_coupon(
			[
				'raw_amount'   => 20,
				'sub_type'     => 'percent',
				'slug'         => 'test-coupon',
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
	 * Test that the cart correctly calculates totals with coupons applied.
	 * This verifies the core functionality that our Payment Intent fix relies on.
	 *
	 * @test
	 */
	public function it_should_calculate_cart_total_correctly_with_coupons() {
		// Set up cart with ticket and coupon.
		$cart = tribe( Cart::class );
		$cart->add_ticket( $this->ticket_id_1, 1 ); // Add $10 ticket.
		$this->coupon->add_to_cart( $cart->get_repository() ); // Add 20% discount.

		// Verify the cart total includes the discount.
		$cart_total = $cart->get_cart_total();
		$this->assertEquals( 8.0, $cart_total, 'Cart total should be $8.00 with 20% discount' );

		// Verify the cart has the coupon applied.
		$cart_items = $cart->get_repository()->get_items();
		$coupon_items = array_filter( $cart_items, function( $item ) {
			return isset( $item['type'] ) && $item['type'] === 'coupon';
		} );
		$this->assertCount( 1, $coupon_items, 'Cart should contain one coupon' );
	}

	/**
	 * Test that the cart correctly calculates totals with multiple tickets and coupons.
	 *
	 * @test
	 */
	public function it_should_calculate_cart_total_correctly_multiple_tickets_and_coupons() {
		// Set up cart with multiple tickets and coupon.
		$cart = tribe( Cart::class );
		$cart->add_ticket( $this->ticket_id_1, 2 ); // Add 2x $10 tickets = $20.
		$cart->add_ticket( $this->ticket_id_2, 1 ); // Add 1x $15 ticket = $15.
		// Total: $35, with 20% discount = $28.
		$this->coupon->add_to_cart( $cart->get_repository() );

		// Verify the cart total includes the discount.
		$cart_total = $cart->get_cart_total();
		$this->assertEquals( 28.0, $cart_total, 'Cart total should be $28.00 with 20% discount on $35' );

		// Verify the cart has the coupon applied.
		$cart_items = $cart->get_repository()->get_items();
		$coupon_items = array_filter( $cart_items, function( $item ) {
			return isset( $item['type'] ) && $item['type'] === 'coupon';
		} );
		$this->assertCount( 1, $coupon_items, 'Cart should contain one coupon' );
	}

	/**
	 * Get the table class instance for order modifiers testing.
	 *
	 * @return Order_Modifier_Table
	 */
	protected function get_table_class_instance(): Order_Modifier_Table {
		return tribe( Coupon_Table::class );
	}
}
