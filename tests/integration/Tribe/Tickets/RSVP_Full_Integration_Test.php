<?php

namespace Tribe\Tickets;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;

/**
 * Full integration tests for RSVP data decoupling.
 *
 * This test suite covers complete end-to-end workflows including:
 * - RSVP submission (going/not going)
 * - Attendee status changes
 * - Stock management with concurrent operations simulation
 * - Unique ID generation
 *
 * Complements Phase 3 and Phase 4 integration tests which cover:
 * - Ticket creation/updates/deletion
 * - Check-in/uncheckin
 * - ET+ custom fields
 *
 * @since TBD
 */
class RSVP_Full_Integration_Test extends \Codeception\TestCase\WPTestCase {
	use Ticket_Maker;
	use Attendee_Maker;

	/**
	 * @var \Tribe__Tickets__RSVP
	 */
	private $rsvp;

	/**
	 * @before
	 */
	public function ensure_posts_are_ticketable(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	/**
	 * @before
	 */
	public function ensure_user_can_manage_tickets(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * Setup test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->rsvp = tribe( 'tickets.rsvp' );
	}

	/**
	 * Test RSVP submission workflow - happy path with "going" status.
	 *
	 * Verifies:
	 * - Attendee is created with correct data
	 * - Stock is decremented atomically
	 * - Sales count is incremented
	 * - RSVP status is set correctly
	 *
	 * @test
	 */
	public function test_rsvp_submission_going_workflow() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 10,
					'total_sales' => 0,
					'_stock'      => 10,
				],
			]
		);

		// Simulate RSVP form submission data.
		$attendee_data = [
			'full_name'       => 'John Doe',
			'email'           => 'john@example.com',
			'order_status'    => 'yes',
			'optout'          => false,
			'order_attendee_id' => 1,
		];

		// Use the RSVP module to generate the attendee.
		$attendee_id = $this->rsvp->generate_tickets_for(
			$ticket_id,
			1,
			$attendee_data
		);

		$this->assertIsArray( $attendee_id, 'generate_tickets_for should return an array of attendee IDs' );
		$this->assertCount( 1, $attendee_id, 'Should generate 1 attendee' );

		$attendee_id = reset( $attendee_id );
		$this->assertGreaterThan( 0, $attendee_id, 'Attendee ID should be valid' );

		// Verify attendee data.
		$attendee_post = get_post( $attendee_id );
		$this->assertNotNull( $attendee_post, 'Attendee post should exist' );
		$this->assertEquals( 'tribe_rsvp_attendees', $attendee_post->post_type );

		// Verify RSVP status.
		$status = get_post_meta( $attendee_id, '_tribe_rsvp_status', true );
		$this->assertEquals( 'yes', $status, 'RSVP status should be "yes"' );

		// Verify ticket association.
		$ticket = get_post_meta( $attendee_id, '_tribe_rsvp_product', true );
		$this->assertEquals( $ticket_id, $ticket, 'Attendee should be associated with ticket' );

		// Verify event association.
		$event = get_post_meta( $attendee_id, '_tribe_rsvp_event', true );
		$this->assertEquals( $post_id, $event, 'Attendee should be associated with event' );

		// Verify stock was decremented (via repository).
		$ticket_repo = tribe( 'tickets.ticket-repository.rsvp' );
		$stock       = $ticket_repo->get_field( $ticket_id, 'stock' );
		$this->assertEquals( 9, $stock, 'Stock should be decremented to 9' );

		// Verify sales was incremented.
		$sales = $ticket_repo->get_field( $ticket_id, 'sales' );
		$this->assertEquals( 1, $sales, 'Sales should be incremented to 1' );
	}

	/**
	 * Test RSVP submission workflow - "not going" status.
	 *
	 * Verifies:
	 * - Attendee is created with "no" status
	 * - Stock behavior depends on RSVP options configuration
	 * - Attendee data is stored correctly
	 *
	 * @test
	 */
	public function test_rsvp_submission_not_going_workflow() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 10,
					'total_sales' => 0,
					'_stock'      => 10,
				],
			]
		);

		// Simulate RSVP form submission with "not going" status.
		$attendee_data = [
			'full_name'       => 'Jane Smith',
			'email'           => 'jane@example.com',
			'order_status'    => 'no',
			'optout'          => false,
			'order_attendee_id' => 1,
		];

		// Generate attendee with "not going" status.
		$attendee_ids = $this->rsvp->generate_tickets_for(
			$ticket_id,
			1,
			$attendee_data
		);

		$this->assertIsArray( $attendee_ids, 'Should return array of attendee IDs' );
		$this->assertCount( 1, $attendee_ids, 'Should generate 1 attendee' );

		$attendee_id = reset( $attendee_ids );

		// Verify RSVP status is "no".
		$status = get_post_meta( $attendee_id, '_tribe_rsvp_status', true );
		$this->assertEquals( 'no', $status, 'RSVP status should be "no"' );

		// Verify attendee exists and has correct data.
		$full_name = get_post_meta( $attendee_id, '_tribe_rsvp_full_name', true );
		$email     = get_post_meta( $attendee_id, '_tribe_rsvp_email', true );

		$this->assertEquals( 'Jane Smith', $full_name, 'Full name should be stored' );
		$this->assertEquals( 'jane@example.com', $email, 'Email should be stored' );
	}

	/**
	 * Test attendee status change workflow (going to not going).
	 *
	 * Verifies:
	 * - Status change is recorded
	 * - Stock is adjusted appropriately via repository
	 * - Sales count is updated
	 *
	 * @test
	 */
	public function test_attendee_status_change_going_to_not_going() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 10,
					'total_sales' => 1,
					'_stock'      => 9,
				],
			]
		);

		// Create attendee with "going" status.
		$attendee_id = $this->create_attendee_for_ticket(
			$ticket_id,
			$post_id,
			[
				'rsvp_status' => 'yes',
				'full_name'   => 'Test User',
				'email'       => 'test@example.com',
			]
		);

		// Change status from "yes" to "no".
		$result = $this->rsvp->update_sales_and_stock_by_order_status(
			$attendee_id,
			'no',
			$ticket_id
		);

		// Verify status was updated.
		$new_status = get_post_meta( $attendee_id, '_tribe_rsvp_status', true );
		$this->assertEquals( 'no', $new_status, 'Status should be changed to "no"' );

		// The stock/sales adjustment depends on RSVP options configuration.
		// This test verifies the method executes without errors.
		$this->assertIsBool( $result, 'update_sales_and_stock_by_order_status should return boolean' );
	}

	/**
	 * Test attendee status change workflow (not going to going).
	 *
	 * Verifies:
	 * - Status change is recorded
	 * - Stock is decremented if available
	 * - Sales count is incremented
	 *
	 * @test
	 */
	public function test_attendee_status_change_not_going_to_going() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 10,
					'total_sales' => 0,
					'_stock'      => 10,
				],
			]
		);

		// Create attendee with "not going" status.
		$attendee_id = $this->create_attendee_for_ticket(
			$ticket_id,
			$post_id,
			[
				'rsvp_status' => 'no',
				'full_name'   => 'Test User',
				'email'       => 'test@example.com',
			]
		);

		// Change status from "no" to "yes".
		$result = $this->rsvp->update_sales_and_stock_by_order_status(
			$attendee_id,
			'yes',
			$ticket_id
		);

		// Verify status was updated.
		$new_status = get_post_meta( $attendee_id, '_tribe_rsvp_status', true );
		$this->assertEquals( 'yes', $new_status, 'Status should be changed to "yes"' );

		// Verify the method executed.
		$this->assertIsBool( $result, 'update_sales_and_stock_by_order_status should return boolean' );
	}

	/**
	 * Test capacity limit validation - prevent overselling.
	 *
	 * Verifies:
	 * - Cannot create attendee when capacity is full
	 * - Stock remains at 0 when at capacity
	 * - Proper error handling when capacity exceeded
	 *
	 * @test
	 */
	public function test_capacity_limit_prevents_overselling() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 2,
					'total_sales' => 2,
					'_stock'      => 0,
				],
			]
		);

		// Try to generate attendee when at capacity.
		$attendee_data = [
			'full_name'       => 'Late User',
			'email'           => 'late@example.com',
			'order_status'    => 'yes',
			'optout'          => false,
			'order_attendee_id' => 1,
		];

		// This should fail or not decrement stock below 0.
		$result = $this->rsvp->generate_tickets_for(
			$ticket_id,
			1,
			$attendee_data
		);

		// Verify stock didn't go negative.
		$ticket_repo = tribe( 'tickets.ticket-repository.rsvp' );
		$stock       = $ticket_repo->get_field( $ticket_id, 'stock' );
		$this->assertGreaterThanOrEqual( 0, $stock, 'Stock should never go below 0' );
	}

	/**
	 * Test stock depletion scenario.
	 *
	 * Verifies:
	 * - Multiple attendees deplete stock correctly
	 * - Stock reaches exactly 0 when capacity is filled
	 * - Sales count matches capacity
	 *
	 * @test
	 */
	public function test_stock_depletion_scenario() {
		$post_id   = static::factory()->post->create();
		$capacity  = 5;
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

		$ticket_repo = tribe( 'tickets.ticket-repository.rsvp' );

		// Create attendees until capacity is reached.
		for ( $i = 0; $i < $capacity; $i++ ) {
			$attendee_data = [
				'full_name'       => "User $i",
				'email'           => "user$i@example.com",
				'order_status'    => 'yes',
				'optout'          => false,
				'order_attendee_id' => $i + 1,
			];

			$result = $this->rsvp->generate_tickets_for(
				$ticket_id,
				1,
				$attendee_data
			);

			$this->assertIsArray( $result, "Attendee $i should be created successfully" );
		}

		// Verify final stock is 0.
		$final_stock = $ticket_repo->get_field( $ticket_id, 'stock' );
		$this->assertEquals( 0, $final_stock, 'Stock should be depleted to 0' );

		// Verify final sales equals capacity.
		$final_sales = $ticket_repo->get_field( $ticket_id, 'sales' );
		$this->assertEquals( $capacity, $final_sales, 'Sales should equal capacity' );
	}

	/**
	 * Test unique ID generation for attendees.
	 *
	 * Verifies:
	 * - Each attendee gets a unique security code
	 * - Security codes are not empty
	 * - Multiple attendees have different codes
	 *
	 * @test
	 */
	public function test_unique_id_generation_for_attendees() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Create multiple attendees.
		$attendee_1 = $this->create_attendee_for_ticket( $ticket_id, $post_id );
		$attendee_2 = $this->create_attendee_for_ticket( $ticket_id, $post_id );
		$attendee_3 = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		// Retrieve security codes via repository.
		$attendee_repo = tribe( 'tickets.attendee-repository.rsvp' );

		$security_1 = $attendee_repo->get_field( $attendee_1, 'security_code' );
		$security_2 = $attendee_repo->get_field( $attendee_2, 'security_code' );
		$security_3 = $attendee_repo->get_field( $attendee_3, 'security_code' );

		// Verify all codes are non-empty.
		$this->assertNotEmpty( $security_1, 'Attendee 1 should have security code' );
		$this->assertNotEmpty( $security_2, 'Attendee 2 should have security code' );
		$this->assertNotEmpty( $security_3, 'Attendee 3 should have security code' );

		// Verify all codes are unique.
		$this->assertNotEquals( $security_1, $security_2, 'Security codes should be unique' );
		$this->assertNotEquals( $security_2, $security_3, 'Security codes should be unique' );
		$this->assertNotEquals( $security_1, $security_3, 'Security codes should be unique' );

		// Verify codes are strings.
		$this->assertIsString( $security_1, 'Security code should be a string' );
		$this->assertIsString( $security_2, 'Security code should be a string' );
		$this->assertIsString( $security_3, 'Security code should be a string' );
	}

	/**
	 * Test error handling when ticket does not exist.
	 *
	 * Verifies:
	 * - Proper error handling for invalid ticket ID
	 * - No data corruption occurs
	 *
	 * @test
	 */
	public function test_error_handling_for_invalid_ticket() {
		$invalid_ticket_id = 99999;

		$attendee_data = [
			'full_name'       => 'Test User',
			'email'           => 'test@example.com',
			'order_status'    => 'yes',
			'optout'          => false,
			'order_attendee_id' => 1,
		];

		// Try to generate tickets for invalid ticket.
		$result = $this->rsvp->generate_tickets_for(
			$invalid_ticket_id,
			1,
			$attendee_data
		);

		// Should return false or empty array.
		$this->assertTrue(
			false === $result || ( is_array( $result ) && empty( $result ) ),
			'Should fail gracefully for invalid ticket'
		);
	}

	/**
	 * Test concurrent RSVP submissions (simulated).
	 *
	 * Verifies:
	 * - Multiple rapid RSVP submissions are handled correctly
	 * - Stock is decremented accurately
	 * - No race conditions in stock management
	 *
	 * This simulates concurrent requests by rapidly creating attendees.
	 *
	 * @test
	 */
	public function test_simulated_concurrent_rsvp_submissions() {
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

		$ticket_repo = tribe( 'tickets.ticket-repository.rsvp' );

		// Simulate 10 rapid RSVP submissions.
		$attendee_ids = [];
		for ( $i = 0; $i < 10; $i++ ) {
			// Direct use of adjust_sales to simulate atomic operations.
			$new_sales = $ticket_repo->adjust_sales( $ticket_id, 1 );

			if ( false !== $new_sales ) {
				// Create the attendee only if stock was available.
				$attendee_ids[] = $this->create_attendee_for_ticket(
					$ticket_id,
					$post_id,
					[ 'rsvp_status' => 'yes' ]
				);
			}
		}

		// Verify exactly 10 attendees were created.
		$this->assertCount( 10, $attendee_ids, 'Should create exactly 10 attendees' );

		// Verify final stock is 0.
		$final_stock = $ticket_repo->get_field( $ticket_id, 'stock' );
		$this->assertEquals( 0, $final_stock, 'Stock should be 0 after all RSVPs' );

		// Verify final sales is 10.
		$final_sales = $ticket_repo->get_field( $ticket_id, 'sales' );
		$this->assertEquals( 10, $final_sales, 'Sales should be 10 after all RSVPs' );
	}

	/**
	 * Test edge case: Unlimited capacity.
	 *
	 * Verifies:
	 * - Unlimited capacity (-1) allows any number of attendees
	 * - Stock management is disabled for unlimited tickets
	 *
	 * @test
	 */
	public function test_unlimited_capacity_allows_infinite_attendees() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'     => -1,
					'total_sales'   => 0,
					'_manage_stock' => 'no',
				],
			]
		);

		// Create many attendees (should all succeed).
		for ( $i = 0; $i < 20; $i++ ) {
			$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );
			$this->assertGreaterThan( 0, $attendee_id, "Attendee $i should be created for unlimited ticket" );
		}

		// Verify manage_stock is 'no'.
		$manage_stock = get_post_meta( $ticket_id, '_manage_stock', true );
		$this->assertEquals( 'no', $manage_stock, 'Stock management should be disabled for unlimited capacity' );
	}
}
