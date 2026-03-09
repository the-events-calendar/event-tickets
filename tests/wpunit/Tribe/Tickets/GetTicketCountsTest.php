<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Tickets as Tickets;

/**
 * Tests for Tribe__Tickets__Tickets::get_ticket_counts().
 *
 * Covers both the CAPPED_STOCK_MODE fix (stock must reflect remaining global pool,
 * not the ticket cap ceiling) and the available double-count fix (global stock must
 * not be added to 'available' when all tickets use OWN_STOCK_MODE).
 */
class GetTicketCountsTest extends \Codeception\TestCase\WPTestCase {

	use PayPal_Ticket_Maker;

	public function setUp() {
		parent::setUp();

		$this->factory()->event = new Event();
		$this->event_id         = $this->factory()->event->create();

		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );

		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	public function tearDown() {
		unset( $this->event_id );

		parent::tearDown();
	}

	/**
	 * @test
	 * @covers Tribe__Tickets__Tickets::get_ticket_counts
	 */
	public function it_returns_empty_array_for_missing_post_id() {
		$counts = Tickets::get_ticket_counts( 0 );

		$this->assertEmpty( $counts );
	}

	/**
	 * @test
	 * @covers Tribe__Tickets__Tickets::get_ticket_counts
	 */
	public function it_returns_empty_array_for_event_with_no_tickets() {
		$counts = Tickets::get_ticket_counts( $this->event_id );

		$this->assertEmpty( $counts );
	}

	/**
	 * @test
	 * @covers Tribe__Tickets__Tickets::get_ticket_counts
	 */
	public function it_returns_correct_counts_for_own_stock_mode_ticket() {
		// capacity=10, sales=3 → available=7
		$this->create_paypal_ticket_basic( $this->event_id, 1, [
			'meta_input' => [
				'_capacity'   => 10,
				'total_sales' => 3,
			],
		] );

		$counts = Tickets::get_ticket_counts( $this->event_id );

		$this->assertEquals( 1, $counts['tickets']['count'],     'count mismatch' );
		$this->assertEquals( 0, $counts['tickets']['global'],    'global flag must not be set for OWN_STOCK_MODE' );
		$this->assertEquals( 7, $counts['tickets']['stock'],     'stock must equal ticket available' );
		$this->assertEquals( 7, $counts['tickets']['available'], 'available must equal ticket available' );
	}

	/**
	 * @test
	 * @covers Tribe__Tickets__Tickets::get_ticket_counts
	 */
	public function it_returns_correct_counts_for_global_stock_mode_ticket() {
		// Shared pool: 50 total, 11 sold → 39 remaining.
		$this->create_distinct_paypal_tickets_basic( $this->event_id, [
			[
				'meta_input' => [
					'_capacity'                     => 50,
					'total_sales'                   => 11,
					Global_Stock::TICKET_STOCK_MODE => Global_Stock::GLOBAL_STOCK_MODE,
				],
			],
		], 50 );

		$counts = Tickets::get_ticket_counts( $this->event_id );

		$this->assertEquals( 1,  $counts['tickets']['count'],     'count mismatch' );
		$this->assertEquals( 1,  $counts['tickets']['global'],    'global flag must be set for GLOBAL_STOCK_MODE' );
		$this->assertEquals( 39, $counts['tickets']['stock'],     'stock must equal remaining global pool' );
		$this->assertEquals( 39, $counts['tickets']['available'], 'available must equal remaining global pool' );
	}

	/**
	 * @test
	 *
	 * Regression test for the CAPPED_STOCK_MODE fix.
	 *
	 * Before the fix, CAPPED tickets fell through to:
	 *     $stock_level = $ticket->global_stock_cap()
	 * which returns the ticket's sales ceiling (20 in this test), not what's
	 * actually left in the shared pool (45).  Both 'stock' and 'available' must
	 * now reflect the remaining pool, matching GLOBAL_STOCK_MODE behaviour.
	 *
	 * @covers Tribe__Tickets__Tickets::get_ticket_counts
	 */
	public function it_uses_remaining_global_pool_not_ticket_cap_for_capped_stock_mode() {
		// Shared pool: 50 total, 5 sold → 45 remaining.
		// CAPPED ticket cap: 20 (max this ticket type may sell from the pool).
		$this->create_distinct_paypal_tickets_basic( $this->event_id, [
			[
				'meta_input' => [
					'_capacity'                     => 20,
					'total_sales'                   => 5,
					Global_Stock::TICKET_STOCK_MODE => Global_Stock::CAPPED_STOCK_MODE,
					Global_Stock::TICKET_STOCK_CAP  => 20,
				],
			],
		], 50 );

		$counts = Tickets::get_ticket_counts( $this->event_id );

		$this->assertEquals( 1,  $counts['tickets']['count'],     'count mismatch' );
		$this->assertEquals( 1,  $counts['tickets']['global'],    'global flag must be set for CAPPED_STOCK_MODE' );
		$this->assertEquals( 45, $counts['tickets']['stock'],     'stock must reflect remaining global pool, not the cap' );
		$this->assertEquals( 45, $counts['tickets']['available'], 'available must reflect remaining global pool, not the cap' );

		// Explicit guard: the cap value must never be used as the stock figure.
		$this->assertNotEquals( 20, $counts['tickets']['stock'], 'stock must NOT equal the ticket type cap (20)' );
	}

	/**
	 * @test
	 *
	 * Regression test for the available double-count fix.
	 *
	 * Before the fix, $types['tickets']['available'] += $global_stock was executed
	 * unconditionally in the post-loop section, even when every ticket uses
	 * OWN_STOCK_MODE.  The guard now prevents that addition so 'available' only
	 * reflects what each OWN ticket reported via available().
	 *
	 * @covers Tribe__Tickets__Tickets::get_ticket_counts
	 */
	public function it_does_not_add_global_stock_to_available_when_all_tickets_are_own_stock_mode() {
		// OWN ticket: capacity=10, sales=3 → available=7.
		$this->create_paypal_ticket_basic( $this->event_id, 1, [
			'meta_input' => [
				'_capacity'   => 10,
				'total_sales' => 3,
			],
		] );

		// Manually enable global stock on the event (simulates a legacy or misconfigured
		// state where the event has a global stock level even though every ticket is OWN).
		$global_stock = new Global_Stock( $this->event_id );
		$global_stock->enable( true );
		update_post_meta( $this->event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 50 );

		$counts = Tickets::get_ticket_counts( $this->event_id );

		// 'available' must be 7 (own ticket only), not 57 (7 + 50 global pool).
		$this->assertEquals( 7, $counts['tickets']['available'], 'global stock must NOT be added when all tickets use OWN_STOCK_MODE' );
		$this->assertEquals( 7, $counts['tickets']['stock'],     'stock must only include OWN_STOCK_MODE ticket stock' );
		$this->assertEquals( 0, $counts['tickets']['global'],    'global flag must not be set for OWN-only events' );
	}

	/**
	 * @test
	 * @covers Tribe__Tickets__Tickets::get_ticket_counts
	 */
	public function it_returns_correct_counts_for_mixed_own_and_global_tickets() {
		// OWN ticket: capacity=10, sales=2 → available=8.
		// GLOBAL ticket: draws from a shared pool of 30 with 5 sold → pool remaining=25.
		// Expected totals: stock = 8 + 25 = 33, available = 8 + 25 = 33.
		$this->create_distinct_paypal_tickets_basic( $this->event_id, [
			[
				'meta_input' => [
					'_capacity'   => 10,
					'total_sales' => 2,
					// OWN_STOCK_MODE is the default; no explicit key needed.
				],
			],
			[
				'meta_input' => [
					'_capacity'                     => 30,
					'total_sales'                   => 5,
					Global_Stock::TICKET_STOCK_MODE => Global_Stock::GLOBAL_STOCK_MODE,
				],
			],
		], 30 );

		$counts = Tickets::get_ticket_counts( $this->event_id );

		$this->assertEquals( 2,  $counts['tickets']['count'],     'count mismatch' );
		$this->assertEquals( 1,  $counts['tickets']['global'],    'global flag must be set when at least one ticket uses shared capacity' );
		$this->assertEquals( 33, $counts['tickets']['stock'],     'stock must be OWN ticket stock + remaining global pool' );
		$this->assertEquals( 33, $counts['tickets']['available'], 'available must be OWN ticket available + remaining global pool' );
	}

	/**
	 * @test
	 * @covers Tribe__Tickets__Tickets::get_ticket_counts
	 */
	public function it_counts_shared_pool_only_once_for_mixed_global_and_capped_tickets() {
		// Two tickets sharing the same pool of 40:
		//   GLOBAL ticket: 5 sold
		//   CAPPED ticket: cap=15, 5 sold
		// Total sold from pool: 10 → pool remaining: 30.
		// Both tickets hit 'continue', so the pool is added exactly once below the loop.
		$this->create_distinct_paypal_tickets_basic( $this->event_id, [
			[
				'meta_input' => [
					'_capacity'                     => 40,
					'total_sales'                   => 5,
					Global_Stock::TICKET_STOCK_MODE => Global_Stock::GLOBAL_STOCK_MODE,
				],
			],
			[
				'meta_input' => [
					'_capacity'                     => 15,
					'total_sales'                   => 5,
					Global_Stock::TICKET_STOCK_MODE => Global_Stock::CAPPED_STOCK_MODE,
					Global_Stock::TICKET_STOCK_CAP  => 15,
				],
			],
		], 40 );

		$counts = Tickets::get_ticket_counts( $this->event_id );

		$this->assertEquals( 2,  $counts['tickets']['count'],     'count mismatch' );
		$this->assertEquals( 1,  $counts['tickets']['global'],    'global flag must be set' );
		$this->assertEquals( 30, $counts['tickets']['stock'],     'global pool must be counted once, not once per shared ticket' );
		$this->assertEquals( 30, $counts['tickets']['available'], 'available must equal global pool, not double-counted' );
	}
}
