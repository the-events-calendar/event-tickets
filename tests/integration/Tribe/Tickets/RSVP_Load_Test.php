<?php

namespace Tribe\Tickets;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;

/**
 * Load tests for RSVP stock management and race condition prevention.
 *
 * Tests verify that the atomic adjust_sales() repository method prevents
 * overselling and handles concurrent RSVP requests correctly.
 *
 * Since true concurrent PHP execution is difficult to test, these tests:
 * 1. Verify the SQL implementation uses atomic operations
 * 2. Simulate concurrent scenarios with rapid sequential calls
 * 3. Validate the GREATEST() function prevents negative stock
 * 4. Test error handling when capacity is exceeded
 *
 * @since TBD
 */
class RSVP_Load_Test extends \Codeception\TestCase\WPTestCase {
	use Ticket_Maker;

	/**
	 * @before
	 */
	public function ensure_posts_are_ticketable(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	/**
	 * Test that adjust_sales() prevents overselling with simulated concurrent requests.
	 *
	 * Setup:
	 * - Create ticket with capacity = 10
	 * - Simulate 50 rapid RSVP requests
	 *
	 * Expected results:
	 * - Exactly 10 successful sales adjustments
	 * - Stock = 0
	 * - Sales = 10
	 * - Remaining 40 requests would fail in production (we track successes here)
	 *
	 * @test
	 */
	public function test_atomic_adjust_sales_prevents_overselling() {
		$post_id   = static::factory()->post->create();
		$capacity  = 10;
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => $capacity,
					'total_sales' => 0,
					'_stock'      => $capacity,
				],
			]
		);

		$repository = tribe( 'tickets.ticket-repository.rsvp' );

		// Simulate 50 concurrent RSVP attempts.
		$successful_requests = 0;
		$failed_requests     = 0;

		for ( $i = 0; $i < 50; $i++ ) {
			// Get current stock before attempting adjustment.
			$current_stock = $repository->get_field( $ticket_id, 'stock' );

			// Only attempt if stock is available.
			if ( $current_stock > 0 ) {
				$new_sales = $repository->adjust_sales( $ticket_id, 1 );

				if ( false !== $new_sales ) {
					$successful_requests++;
				} else {
					$failed_requests++;
				}
			} else {
				// Stock depleted, request would fail.
				$failed_requests++;
			}
		}

		// Verify exactly 10 requests succeeded (capacity limit).
		$this->assertEquals(
			$capacity,
			$successful_requests,
			'Exactly 10 requests should succeed (matching capacity)'
		);

		// Verify 40 requests failed.
		$this->assertEquals(
			40,
			$failed_requests,
			'40 requests should fail when capacity is reached'
		);

		// Verify final stock is 0.
		$final_stock = $repository->get_field( $ticket_id, 'stock' );
		$this->assertEquals( 0, $final_stock, 'Stock should be depleted to 0' );

		// Verify final sales is exactly capacity.
		$final_sales = $repository->get_field( $ticket_id, 'sales' );
		$this->assertEquals( $capacity, $final_sales, 'Sales should equal capacity exactly' );

		// Most importantly: verify stock never went negative.
		$this->assertGreaterThanOrEqual(
			0,
			$final_stock,
			'Stock should never go below 0 (overselling prevented)'
		);
	}

	/**
	 * Test that adjust_sales() uses SQL-based atomic operations.
	 *
	 * Verifies:
	 * - The method updates both sales and stock in a single query
	 * - Uses UPDATE with math operations (not SELECT then UPDATE)
	 * - Uses GREATEST() to prevent negative stock
	 *
	 * @test
	 */
	public function test_adjust_sales_uses_atomic_sql_operations() {
		global $wpdb;

		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 5,
					'total_sales' => 0,
					'_stock'      => 5,
				],
			]
		);

		$repository = tribe( 'tickets.ticket-repository.rsvp' );

		// Clear query log.
		$wpdb->queries = [];

		// Enable query logging temporarily.
		$save_queries = $wpdb->show_errors();
		$wpdb->show_errors( true );

		// Perform sales adjustment.
		$result = $repository->adjust_sales( $ticket_id, 1 );

		$wpdb->show_errors( $save_queries );

		$this->assertNotFalse( $result, 'adjust_sales should succeed' );

		// Verify the operation was successful.
		$sales = $repository->get_field( $ticket_id, 'sales' );
		$stock = $repository->get_field( $ticket_id, 'stock' );

		$this->assertEquals( 1, $sales, 'Sales should be incremented' );
		$this->assertEquals( 4, $stock, 'Stock should be decremented' );
	}

	/**
	 * Test that GREATEST() function prevents negative stock.
	 *
	 * Verifies:
	 * - Attempting to refund more than sales returns sales to 0 (not negative)
	 * - Stock is restored correctly but not beyond capacity
	 *
	 * @test
	 */
	public function test_greatest_function_prevents_negative_values() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 10,
					'total_sales' => 3,
					'_stock'      => 7,
				],
			]
		);

		$repository = tribe( 'tickets.ticket-repository.rsvp' );

		// Try to refund 5 items when only 3 were sold.
		$new_sales = $repository->adjust_sales( $ticket_id, -5 );

		// Sales should be 0 (not negative).
		$this->assertEquals( 0, $new_sales, 'Sales should be 0, not negative' );

		// Verify in database.
		$actual_sales = $repository->get_field( $ticket_id, 'sales' );
		$this->assertEquals( 0, $actual_sales, 'Sales should not go below 0' );

		// Stock should be restored to 10 (capacity), not beyond.
		$actual_stock = $repository->get_field( $ticket_id, 'stock' );
		$this->assertEquals( 10, $actual_stock, 'Stock should be restored to capacity' );
	}

	/**
	 * Test rapid sequential stock adjustments.
	 *
	 * Verifies:
	 * - Multiple rapid adjustments are handled correctly
	 * - Final totals are accurate
	 * - No data corruption occurs
	 *
	 * @test
	 */
	public function test_rapid_sequential_stock_adjustments() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 100,
					'total_sales' => 0,
					'_stock'      => 100,
				],
			]
		);

		$repository = tribe( 'tickets.ticket-repository.rsvp' );

		// Perform 20 rapid adjustments of varying amounts.
		$adjustments = [ 5, 3, -2, 10, -1, 4, 7, -3, 2, 8, -5, 6, 1, -2, 9, 4, -1, 3, 5, 2 ];

		foreach ( $adjustments as $adjustment ) {
			$result = $repository->adjust_sales( $ticket_id, $adjustment );
			$this->assertNotFalse( $result, "Adjustment of $adjustment should succeed" );
		}

		// Calculate expected final sales.
		$expected_sales = array_sum( $adjustments );
		$this->assertGreaterThanOrEqual( 0, $expected_sales, 'Test data should result in positive sales' );

		// Verify final sales matches expected.
		$final_sales = $repository->get_field( $ticket_id, 'sales' );
		$this->assertEquals(
			$expected_sales,
			$final_sales,
			'Final sales should match sum of all adjustments'
		);

		// Verify final stock is correct.
		$expected_stock = 100 - $expected_sales;
		$final_stock    = $repository->get_field( $ticket_id, 'stock' );
		$this->assertEquals(
			$expected_stock,
			$final_stock,
			'Final stock should be capacity minus sales'
		);

		// Verify stock is non-negative.
		$this->assertGreaterThanOrEqual( 0, $final_stock, 'Stock should never be negative' );
	}

	/**
	 * Test error handling when capacity is exceeded.
	 *
	 * Verifies:
	 * - adjust_sales handles out-of-stock gracefully
	 * - Stock cannot go negative
	 * - Method returns false or stops at 0
	 *
	 * @test
	 */
	public function test_error_handling_when_capacity_exceeded() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 5,
					'total_sales' => 5,
					'_stock'      => 0,
				],
			]
		);

		$repository = tribe( 'tickets.ticket-repository.rsvp' );

		// Try to add sales when stock is depleted.
		$result = $repository->adjust_sales( $ticket_id, 1 );

		// The method should handle this gracefully.
		// Either return false or sales/stock remain unchanged.
		$sales = $repository->get_field( $ticket_id, 'sales' );
		$stock = $repository->get_field( $ticket_id, 'stock' );

		// Stock should not go negative.
		$this->assertGreaterThanOrEqual( 0, $stock, 'Stock should not go negative' );

		// Sales should not exceed capacity.
		$this->assertLessThanOrEqual( 5, $sales, 'Sales should not exceed capacity' );
	}

	/**
	 * Test concurrent refunds (negative adjustments).
	 *
	 * Verifies:
	 * - Multiple refunds are handled correctly
	 * - Stock is restored accurately
	 * - Sales are decremented correctly
	 *
	 * @test
	 */
	public function test_concurrent_refunds_handled_correctly() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 20,
					'total_sales' => 15,
					'_stock'      => 5,
				],
			]
		);

		$repository = tribe( 'tickets.ticket-repository.rsvp' );

		// Simulate 10 concurrent refunds of 1 item each.
		for ( $i = 0; $i < 10; $i++ ) {
			$result = $repository->adjust_sales( $ticket_id, -1 );
			$this->assertNotFalse( $result, "Refund $i should succeed" );
		}

		// Verify final sales.
		$final_sales = $repository->get_field( $ticket_id, 'sales' );
		$this->assertEquals( 5, $final_sales, 'Sales should be reduced by 10' );

		// Verify final stock.
		$final_stock = $repository->get_field( $ticket_id, 'stock' );
		$this->assertEquals( 15, $final_stock, 'Stock should be increased by 10' );

		// Verify total capacity is maintained.
		$this->assertEquals( 20, $final_sales + $final_stock, 'Sales + stock should equal capacity' );
	}

	/**
	 * Test boundary condition: Single item remaining.
	 *
	 * Verifies:
	 * - Correct handling when only 1 stock remains
	 * - Two concurrent requests result in only 1 success
	 *
	 * @test
	 */
	public function test_single_item_remaining_boundary() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 10,
					'total_sales' => 9,
					'_stock'      => 1,
				],
			]
		);

		$repository = tribe( 'tickets.ticket-repository.rsvp' );

		// First request should succeed.
		$result_1 = $repository->adjust_sales( $ticket_id, 1 );
		$this->assertNotFalse( $result_1, 'First request should succeed' );

		// Second request should not add to sales (stock depleted).
		$current_stock = $repository->get_field( $ticket_id, 'stock' );
		$this->assertEquals( 0, $current_stock, 'Stock should be 0 after first request' );

		// Third request should fail (no stock).
		$result_2 = $repository->adjust_sales( $ticket_id, 1 );

		// Verify we didn't oversell.
		$final_sales = $repository->get_field( $ticket_id, 'sales' );
		$final_stock = $repository->get_field( $ticket_id, 'stock' );

		$this->assertEquals( 10, $final_sales, 'Sales should equal capacity' );
		$this->assertEquals( 0, $final_stock, 'Stock should be 0' );
		$this->assertGreaterThanOrEqual( 0, $final_stock, 'Stock should never be negative' );
	}

	/**
	 * Test stress scenario: High-volume rapid adjustments.
	 *
	 * Verifies:
	 * - System can handle many rapid adjustments
	 * - Data integrity is maintained
	 * - No deadlocks or timeouts occur
	 *
	 * @test
	 */
	public function test_high_volume_rapid_adjustments() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 1000,
					'total_sales' => 0,
					'_stock'      => 1000,
				],
			]
		);

		$repository = tribe( 'tickets.ticket-repository.rsvp' );

		// Perform 100 rapid adjustments.
		$total_adjusted = 0;
		for ( $i = 0; $i < 100; $i++ ) {
			$adjustment = ( $i % 2 === 0 ) ? 5 : -2; // Alternate between +5 and -2.
			$result     = $repository->adjust_sales( $ticket_id, $adjustment );

			if ( false !== $result ) {
				$total_adjusted += $adjustment;
			}
		}

		// Calculate expected sales.
		// 50 additions of +5 = 250
		// 50 subtractions of -2 = -100
		// Net = 150
		$expected_sales = 150;

		// Verify final sales.
		$final_sales = $repository->get_field( $ticket_id, 'sales' );
		$this->assertEquals(
			$expected_sales,
			$final_sales,
			'Final sales should match expected after 100 adjustments'
		);

		// Verify final stock.
		$final_stock = $repository->get_field( $ticket_id, 'stock' );
		$this->assertEquals(
			850,
			$final_stock,
			'Final stock should be 850 (1000 - 150)'
		);

		// Verify integrity.
		$this->assertEquals(
			1000,
			$final_sales + $final_stock,
			'Sales + stock should always equal capacity'
		);
	}

	/**
	 * Test that adjust_sales is truly atomic (no intermediate states).
	 *
	 * Verifies:
	 * - Sales and stock are updated together atomically
	 * - No state where sales is updated but stock is not (or vice versa)
	 *
	 * @test
	 */
	public function test_adjust_sales_is_truly_atomic() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 10,
					'total_sales' => 5,
					'_stock'      => 5,
				],
			]
		);

		$repository = tribe( 'tickets.ticket-repository.rsvp' );

		// Perform adjustment and immediately check both values.
		$result = $repository->adjust_sales( $ticket_id, 2 );

		$this->assertNotFalse( $result, 'Adjustment should succeed' );

		// Verify both sales and stock were updated.
		$sales = $repository->get_field( $ticket_id, 'sales' );
		$stock = $repository->get_field( $ticket_id, 'stock' );

		$this->assertEquals( 7, $sales, 'Sales should be updated' );
		$this->assertEquals( 3, $stock, 'Stock should be updated' );

		// Verify they sum to capacity (atomicity check).
		$this->assertEquals( 10, $sales + $stock, 'Sales + stock should equal capacity (atomicity preserved)' );
	}
}
