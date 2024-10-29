<?php

namespace TEC\Tickets\Tests\Integration\Order_Modifiers\Fees\Checkout;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Module as Commerce;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Order_Modifiers\Controller;
use TEC\Tickets\Order_Modifiers\Modifiers\Modifier_Manager;
use Tribe\Tickets\Test\Commerce\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use TEC\Tickets\Order_Modifiers\Checkout\Fees;
use Tribe\Tickets\Test\Traits\Order_Modifiers;

class Order_Test extends WPTestCase {

	use Ticket_Maker;
	use Order_Maker;
	use Order_Modifiers;

	/**
	 * The type of order modifier being tested (fee).
	 *
	 * @var string
	 */
	protected string $modifier_type = 'fee';

	/**
	 * The ticket ID for reuse across tests.
	 *
	 * @var int
	 */
	protected int $ticket_id;

	/**
	 * The modifier strategy to be reused across tests.
	 *
	 * @var mixed
	 */
	protected $modifier_strategy;

	/**
	 * The modifier manager to be reused across tests.
	 *
	 * @var Modifier_Manager
	 */
	protected Modifier_Manager $modifier_manager;

	/**
	 * Set up common elements for all tests.
	 *
	 * @before
	 */
	public function set_up(): void {
		// Step 1: Create a ticket to use in all tests.
		$post_id         = static::factory()->post->create();
		$ticket_price    = 23;
		$this->ticket_id = $this->create_ticket( Commerce::class, $post_id, $ticket_price );

		// Step 2: Set up the modifier strategy and manager to reuse in tests.
		$this->modifier_strategy = tribe( Controller::class )->get_modifier( $this->modifier_type );
		$this->modifier_manager  = new Modifier_Manager( $this->modifier_strategy );
	}

	/**
	 * Data provider for testing different types of fees, with support for multiple fees.
	 *
	 * @return Generator
	 */
	public function fee_type_provider(): Generator {
		// Single flat fee
		yield 'Single Flat Fee' => [
			'modifiers'                 => [
				[
					'order_modifier_amount'       => 5,
					'order_modifier_sub_type'     => 'flat',
					'order_modifier_slug'         => 'flat_fee_1',
					'order_modifier_display_name' => 'Flat Fee 1',
				],
			],
			'expected_total_adjustment' => 5.00,
		];

		// Multiple flat fees
		yield 'Multiple Flat Fees (10 Fees)' => [
			'modifiers'                 => array_fill(
				0,
				10,
				[
					'order_modifier_amount'       => 10,
					'order_modifier_sub_type'     => 'flat',
					'order_modifier_slug'         => 'flat_fee',
					'order_modifier_display_name' => 'Flat Fee',
				]
			),
			'expected_total_adjustment' => 10 * 10.00, // 10 flat fees of $10 each
		];

		// Multiple percent fees
		yield 'Multiple Percent Fees (10 Fees)' => [
			'modifiers'                 => array_fill(
				0,
				10,
				[
					'order_modifier_amount'       => 10, // 10%
					'order_modifier_sub_type'     => 'percent',
					'order_modifier_slug'         => 'percent_fee',
					'order_modifier_display_name' => 'Percent Fee',
				]
			),
			'expected_total_adjustment' => 230 * ( 10 * 0.10 ), // 10% of $230 applied 10 times
		];

		// Combination of flat and percent fees
		yield 'Flat and Percent Fees (Mix)' => [
			'modifiers'                 => [
				[
					'order_modifier_amount'       => 5,
					'order_modifier_sub_type'     => 'flat',
					'order_modifier_slug'         => 'flat_fee_1',
					'order_modifier_display_name' => 'Flat Fee 1',
				],
				[
					'order_modifier_amount'       => 10, // 10%
					'order_modifier_sub_type'     => 'percent',
					'order_modifier_slug'         => 'percent_fee',
					'order_modifier_display_name' => 'Percent Fee',
				],
			],
			'expected_total_adjustment' => 5.00 + ( 230 * 0.10 ), // $5 flat + 10% of $230
		];

		// Excessively large fee
		yield 'Excessively Large Fee' => [
			'modifiers'                 => [
				[
					'order_modifier_amount'       => 1000000,
					'order_modifier_sub_type'     => 'flat',
					'order_modifier_slug'         => 'large_fee',
					'order_modifier_display_name' => 'Large Fee',
				],
			],
			'expected_total_adjustment' => 1000000.00, // Add $1,000,000 to total
		];

		// 100% percent fee
		yield 'Max Percent Fee (100%)' => [
			'modifiers'                 => [
				[
					'order_modifier_amount'       => 100, // 100%
					'order_modifier_sub_type'     => 'percent',
					'order_modifier_slug'         => 'max_percent_fee',
					'order_modifier_display_name' => 'Max Percent Fee',
				],
			],
			'expected_total_adjustment' => 230, // 100% of $230
		];

		// Multiple percent fees with varying percentages
		yield 'Multiple Percent Fees (5%, 10%, 15%)' => [
			'modifiers'                 => [
				[
					'order_modifier_amount'       => 5,
					'order_modifier_sub_type'     => 'percent',
					'order_modifier_slug'         => 'percent_fee_5',
					'order_modifier_display_name' => '5% Percent Fee',
				],
				[
					'order_modifier_amount'       => 10,
					'order_modifier_sub_type'     => 'percent',
					'order_modifier_slug'         => 'percent_fee_10',
					'order_modifier_display_name' => '10% Percent Fee',
				],
				[
					'order_modifier_amount'       => 15,
					'order_modifier_sub_type'     => 'percent',
					'order_modifier_slug'         => 'percent_fee_15',
					'order_modifier_display_name' => '15% Percent Fee',
				],
			],
			'expected_total_adjustment' => ( 230 * 0.05 ) + ( 230 * 0.10 ) + ( 230 * 0.15 ), // Sum of all percentage adjustments
		];

		// Multiple 100% percent fees
		yield 'Multiple 100% Percent Fees' => [
			'modifiers'                 => array_fill(
				0,
				3,
				[
					'order_modifier_amount'       => 100, // 100% fee
					'order_modifier_sub_type'     => 'percent',
					'order_modifier_slug'         => 'max_percent_fee',
					'order_modifier_display_name' => 'Max Percent Fee',
				]
			),
			'expected_total_adjustment' => 230 * 3, // Applying 100% fee 3 times (triple the order total)
		];

		// Maximum percent and flat fee combination
		yield 'Max Percent Fee with Flat Fee' => [
			'modifiers'                 => [
				[
					'order_modifier_amount'       => 100, // 100% fee
					'order_modifier_sub_type'     => 'percent',
					'order_modifier_slug'         => 'max_percent_fee',
					'order_modifier_display_name' => 'Max Percent Fee',
				],
				[
					'order_modifier_amount'       => 50, // Flat fee of $50
					'order_modifier_sub_type'     => 'flat',
					'order_modifier_slug'         => 'flat_fee',
					'order_modifier_display_name' => 'Flat Fee',
				],
			],
			'expected_total_adjustment' => 230 + 50, // 100% of $230 plus $50 flat fee
		];
	}

	/**
	 * Test applying various types of fee modifiers during checkout using data provider.
	 *
	 * @dataProvider fee_type_provider
	 *
	 * @param array $modifiers                 The modifiers data for the test.
	 * @param float $expected_total_adjustment The expected total adjustment for the modifiers.
	 */
	public function test_apply_fee_modifiers_during_checkout( array $modifiers, float $expected_total_adjustment ) {
		$this->markTestSkipped( 'This test needs to be revisited.' );

		// Step 1: Insert the modifiers.
		$modifier_ids = [];
		foreach ( $modifiers as $modifier_data ) {
			$modifier_data['modifier'] = $this->modifier_type;
			$inserted_modifier         = $this->upsert_order_modifier_for_test( $modifier_data );
			$modifier_ids[]            = $inserted_modifier->id;
		}

		// Step 2: Associate the modifiers with the ticket.
		$this->modifier_manager->sync_modifier_relationships( $modifier_ids, [ $this->ticket_id ] );

		// Step 3: Add tickets to the cart.
		$cart            = new Cart();
		$ticket_quantity = 10; // Purchase 10 tickets.
		$cart->get_repository()->add_item( $this->ticket_id, $ticket_quantity );

		// Step 4: Create the order.
		$purchaser = [
			'purchaser_user_id'    => 0,
			'purchaser_full_name'  => 'Test Purchaser',
			'purchaser_first_name' => 'Test',
			'purchaser_last_name'  => 'Purchaser',
			'purchaser_email'      => 'test-' . uniqid() . '@test.com',
		];
		$order     = tribe( Order::class )->create_from_cart( tribe( Gateway::class ), $purchaser );

		// Step 5: Calculate the expected total.
		$expected_total = $order->subtotal->get_decimal() + $expected_total_adjustment;

		// Reset fees before assertions.
		$this->reset_fees();

		// Step 6: Assert that the total matches the expected total.
		$this->assertEquals( $expected_total, $order->total_value->get_decimal() );

		// Clear cart and reset fees.
		$cart->clear_cart();
		$this->modifier_manager->delete_relationships_by_post( $this->ticket_id );
	}

	/**
	 * Reset the fees for the test class.
	 */
	protected function reset_fees() {
		static $closure_reset = null;
		if ( null === $closure_reset ) {
			$closure_reset = Closure::bind(
				function() {
					self::$fees_appended = false;
					self::$fees_displayed = false;
					echo 'Fees reset for class ' . self::class . PHP_EOL;
				},
				null,
				Fees::class
			);
		}

		$closure_reset();
	}
}
