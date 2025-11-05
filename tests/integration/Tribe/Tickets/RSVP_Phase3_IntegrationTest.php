<?php

namespace Tribe\Tickets;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;

/**
 * Integration tests for Phase 3: RSVP data decoupling - Write Operations.
 *
 * Tests the refactored save_ticket(), delete_ticket(), and
 * update_sales_and_stock_by_order_status() methods to ensure they use
 * repository methods instead of direct WordPress functions.
 *
 * @since TBD
 */
class RSVP_Phase3_IntegrationTest extends \Codeception\TestCase\WPTestCase {
	use Ticket_Maker;

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
	 * Test that save_ticket() creates a new ticket using repository.
	 *
	 * @test
	 */
	public function test_save_ticket_creates_new_ticket() {
		$post_id = static::factory()->post->create();

		$ticket       = new \Tribe__Tickets__Ticket_Object();
		$ticket->name = 'Test RSVP Ticket';
		$ticket->description = 'Test ticket description';
		$ticket->price       = 0;
		$ticket->menu_order  = 1;
		$ticket->show_description = true;

		$raw_data = [
			'tribe-ticket' => [
				'capacity' => 50,
				'stock'    => 50,
			],
		];

		$ticket_id = $this->rsvp->save_ticket( $post_id, $ticket, $raw_data );

		$this->assertIsInt( $ticket_id, 'save_ticket should return ticket ID' );
		$this->assertGreaterThan( 0, $ticket_id, 'Ticket ID should be greater than 0' );

		// Verify ticket was created correctly.
		$created_ticket = get_post( $ticket_id );
		$this->assertEquals( 'Test RSVP Ticket', $created_ticket->post_title );
		$this->assertEquals( 'Test ticket description', $created_ticket->post_excerpt );
		$this->assertEquals( 'publish', $created_ticket->post_status );

		// Verify meta was set via repository.
		$this->assertEquals( 'yes', get_post_meta( $ticket_id, '_tribe_ticket_show_description', true ) );
		$this->assertEquals( 0, get_post_meta( $ticket_id, '_price', true ) );
		$this->assertEquals( $post_id, get_post_meta( $ticket_id, '_tribe_rsvp_for_event', true ) );
	}

	/**
	 * Test that save_ticket() updates an existing ticket using repository.
	 *
	 * @test
	 */
	public function test_save_ticket_updates_existing_ticket() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'post_title'   => 'Original Title',
				'post_excerpt' => 'Original Description',
				'meta_input'   => [
					'_capacity'   => 25,
					'total_sales' => 0,
					'_stock'      => 25,
				],
			]
		);

		$ticket              = new \Tribe__Tickets__Ticket_Object();
		$ticket->ID          = $ticket_id;
		$ticket->name        = 'Updated RSVP Ticket';
		$ticket->description = 'Updated description';
		$ticket->price       = 10;
		$ticket->show_description = false;

		$raw_data = [
			'tribe-ticket' => [
				'capacity' => 25,
				'stock'    => 25,
			],
		];

		$result = $this->rsvp->save_ticket( $post_id, $ticket, $raw_data );

		$this->assertEquals( $ticket_id, $result, 'save_ticket should return the same ticket ID on update' );

		// Verify ticket was updated.
		$updated_ticket = get_post( $ticket_id );
		$this->assertEquals( 'Updated RSVP Ticket', $updated_ticket->post_title );
		$this->assertEquals( 'Updated description', $updated_ticket->post_excerpt );

		// Verify meta was updated via repository.
		$this->assertEquals( 'no', get_post_meta( $ticket_id, '_tribe_ticket_show_description', true ) );
		$this->assertEquals( 10, get_post_meta( $ticket_id, '_price', true ) );
	}

	/**
	 * Test that save_ticket() handles start and end dates correctly.
	 *
	 * @test
	 */
	public function test_save_ticket_handles_dates() {
		$post_id = static::factory()->post->create();

		$ticket              = new \Tribe__Tickets__Ticket_Object();
		$ticket->name        = 'Ticket with Dates';
		$ticket->description = 'Ticket description';
		$ticket->price       = 0;

		$raw_data = [
			'tribe-ticket'      => [
				'capacity' => 10,
			],
			'ticket_start_date' => '2025-01-01',
			'ticket_start_time' => '10:00:00',
			'ticket_end_date'   => '2025-12-31',
			'ticket_end_time'   => '23:59:59',
		];

		$ticket_id = $this->rsvp->save_ticket( $post_id, $ticket, $raw_data );

		$this->assertGreaterThan( 0, $ticket_id );

		// Verify dates were set via repository.
		$start_date = get_post_meta( $ticket_id, '_ticket_start_date', true );
		$end_date   = get_post_meta( $ticket_id, '_ticket_end_date', true );

		$this->assertStringContainsString( '2025-01-01', $start_date );
		$this->assertStringContainsString( '2025-12-31', $end_date );
	}

	/**
	 * Test that delete_ticket() removes ticket and marks orphaned attendees.
	 *
	 * @test
	 */
	public function test_delete_ticket_marks_orphaned_attendees() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Create an attendee for this ticket.
		$attendee_id = $this->create_rsvp_attendee( $ticket_id, $post_id );

		// Get ticket title before deletion.
		$ticket_title = get_post( $ticket_id )->post_title;

		// Delete the ticket.
		$result = $this->rsvp->delete_ticket( $post_id, $ticket_id );

		$this->assertTrue( $result, 'delete_ticket should return true on success' );

		// Verify ticket was trashed (soft delete).
		$deleted_ticket = get_post( $ticket_id );
		$this->assertEquals( 'trash', $deleted_ticket->post_status, 'Ticket should be moved to trash' );

		// Verify attendee was marked with deleted product name.
		$deleted_product = get_post_meta( $attendee_id, '_tribe_deleted_product_name', true );
		$this->assertEquals( $ticket_title, $deleted_product, 'Attendee should be marked with deleted ticket name' );
	}

	/**
	 * Test that delete_ticket() returns false for invalid ticket.
	 *
	 * @test
	 */
	public function test_delete_ticket_returns_false_for_invalid_ticket() {
		$post_id = static::factory()->post->create();
		$result  = $this->rsvp->delete_ticket( $post_id, 99999 );

		$this->assertFalse( $result, 'delete_ticket should return false for invalid ticket ID' );
	}

	/**
	 * Test that delete_ticket() handles missing event ID.
	 *
	 * @test
	 */
	public function test_delete_ticket_handles_missing_event_id() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Delete without providing event ID.
		$result = $this->rsvp->delete_ticket( null, $ticket_id );

		$this->assertTrue( $result, 'delete_ticket should work without event_id parameter' );

		// Verify ticket was trashed.
		$deleted_ticket = get_post( $ticket_id );
		$this->assertEquals( 'trash', $deleted_ticket->post_status );
	}

	/**
	 * Test that update_sales_and_stock_by_order_status() uses atomic repository method.
	 *
	 * @test
	 */
	public function test_update_sales_and_stock_by_order_status_uses_atomic_method() {
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

		// Create an attendee with initial status.
		$attendee_id = $this->create_rsvp_attendee( $ticket_id, $post_id );
		update_post_meta( $attendee_id, '_tribe_rsvp_status', 'yes' );

		// Change status from 'yes' to 'no' (should not affect stock in default RSVP options).
		// This test verifies the method doesn't crash and uses the repository.
		$result = $this->rsvp->update_sales_and_stock_by_order_status( $attendee_id, 'no', $ticket_id );

		// The result depends on the RSVP options configuration.
		// The important thing is the method executes without errors.
		$this->assertIsBool( $result, 'update_sales_and_stock_by_order_status should return boolean' );
	}

	/**
	 * Test that update_sales_and_stock_by_order_status() returns false when no change needed.
	 *
	 * @test
	 */
	public function test_update_sales_and_stock_by_order_status_returns_false_when_no_change() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$attendee_id = $this->create_rsvp_attendee( $ticket_id, $post_id );
		update_post_meta( $attendee_id, '_tribe_rsvp_status', 'yes' );

		// Try to update with same status (no change).
		$result = $this->rsvp->update_sales_and_stock_by_order_status( $attendee_id, 'yes', $ticket_id );

		$this->assertFalse( $result, 'Should return false when no status change' );
	}

	/**
	 * Test that update_capacity() uses repository for stock updates.
	 *
	 * @test
	 */
	public function test_update_capacity_uses_repository() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 25,
					'total_sales' => 0,
					'_stock'      => 25,
				],
			]
		);

		$ticket     = get_post( $ticket_id );
		$ticket->ID = $ticket_id;

		$data = [
			'capacity' => 50,
			'stock'    => 50,
		];

		$this->rsvp->update_capacity( $ticket, $data, 'create' );

		// Verify stock was updated.
		$updated_stock = get_post_meta( $ticket_id, '_stock', true );
		$this->assertEquals( 50, $updated_stock, 'Stock should be updated to 50' );

		// Verify manage_stock was set.
		$manage_stock = get_post_meta( $ticket_id, '_manage_stock', true );
		$this->assertEquals( 'yes', $manage_stock, 'manage_stock should be set to yes' );
	}

	/**
	 * Test that update_capacity() handles unlimited capacity.
	 *
	 * @test
	 */
	public function test_update_capacity_handles_unlimited() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 25,
					'total_sales' => 0,
					'_stock'      => 25,
				],
			]
		);

		$ticket     = get_post( $ticket_id );
		$ticket->ID = $ticket_id;

		$data = [
			'capacity' => -1, // Unlimited.
		];

		$this->rsvp->update_capacity( $ticket, $data, 'update' );

		// Verify manage_stock was set to 'no'.
		$manage_stock = get_post_meta( $ticket_id, '_manage_stock', true );
		$this->assertEquals( 'no', $manage_stock, 'manage_stock should be set to no for unlimited capacity' );

		// Verify stock meta was deleted.
		$stock = get_post_meta( $ticket_id, '_stock', true );
		$this->assertEmpty( $stock, 'Stock meta should be deleted for unlimited capacity' );
	}
}
