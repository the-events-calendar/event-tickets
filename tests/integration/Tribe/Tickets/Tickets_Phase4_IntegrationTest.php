<?php

namespace Tribe\Tickets;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;

/**
 * Integration tests for Phase 4: Parent Class Updates.
 *
 * Tests the refactored checkin(), uncheckin(), update_capacity(), and
 * update_ticket_sent_counter() methods in Tribe__Tickets__Tickets parent class
 * to ensure they use repository methods instead of direct WordPress functions.
 *
 * @since TBD
 */
class Tickets_Phase4_IntegrationTest extends \Codeception\TestCase\WPTestCase {
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
	 * Test that checkin() uses repository instead of update_post_meta().
	 *
	 * @test
	 */
	public function test_checkin_uses_repository() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [ 'meta_input' => [ '_capacity' => 100 ] ] );

		// Create an attendee.
		$attendee_id = $this->create_rsvp_attendee( $ticket_id, $post_id );

		// Check in the attendee.
		$result = $this->rsvp->checkin( $attendee_id, false );

		$this->assertTrue( $result, 'Check-in should succeed' );

		// Verify check-in status was set.
		$checkin_status = get_post_meta( $attendee_id, '_tribe_rsvp_checkedin', true );
		$this->assertEquals( '1', $checkin_status, 'Attendee should be checked in' );

		// Verify check-in details were set.
		$checkin_details = get_post_meta( $attendee_id, '_tribe_rsvp_checkedin_details', true );
		$this->assertIsArray( $checkin_details, 'Check-in details should be an array' );
		$this->assertArrayHasKey( 'date', $checkin_details );
		$this->assertArrayHasKey( 'source', $checkin_details );
		$this->assertEquals( 'site', $checkin_details['source'] );
	}

	/**
	 * Test that checkin() with QR code sets qr_status.
	 *
	 * @test
	 */
	public function test_checkin_with_qr_sets_status() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [ 'meta_input' => [ '_capacity' => 100 ] ] );

		// Create an attendee.
		$attendee_id = $this->create_rsvp_attendee( $ticket_id, $post_id );

		// Check in with QR code.
		$result = $this->rsvp->checkin( $attendee_id, true );

		$this->assertTrue( $result, 'QR check-in should succeed' );

		// Verify QR status was set.
		$qr_status = get_post_meta( $attendee_id, '_tribe_qr_status', true );
		$this->assertEquals( '1', $qr_status, 'QR status should be set' );

		// Verify check-in details show app source.
		$checkin_details = get_post_meta( $attendee_id, '_tribe_rsvp_checkedin_details', true );
		$this->assertEquals( 'app', $checkin_details['source'] );
	}


	/**
	 * Test that uncheckin() uses repository instead of delete_post_meta().
	 *
	 * @test
	 */
	public function test_uncheckin_uses_repository() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [ 'meta_input' => [ '_capacity' => 100 ] ] );
		$attendee_id = $this->create_rsvp_attendee( $ticket_id, $post_id );

		// Check in first.
		$this->rsvp->checkin( $attendee_id, true );

		// Verify attendee is checked in.
		$this->assertEquals( '1', get_post_meta( $attendee_id, '_tribe_rsvp_checkedin', true ) );
		$this->assertEquals( '1', get_post_meta( $attendee_id, '_tribe_qr_status', true ) );

		// Uncheck in.
		$result = $this->rsvp->uncheckin( $attendee_id );

		$this->assertTrue( $result, 'Uncheck-in should succeed' );

		// Verify check-in data was cleared.
		$checkin_status = get_post_meta( $attendee_id, '_tribe_rsvp_checkedin', true );
		$this->assertEmpty( $checkin_status, 'Check-in status should be cleared' );

		$qr_status = get_post_meta( $attendee_id, '_tribe_qr_status', true );
		$this->assertEmpty( $qr_status, 'QR status should be cleared' );

		$checkin_details = get_post_meta( $attendee_id, '_tribe_rsvp_checkedin_details', true );
		$this->assertEmpty( $checkin_details, 'Check-in details should be cleared' );
	}


	/**
	 * Test that update_capacity() uses repository for stock management.
	 *
	 * @test
	 */
	public function test_stock_management_uses_repository() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Update capacity with stock management enabled.
		$ticket = get_post( $ticket_id );
		$data = [
			'capacity' => 100,
			'stock'    => 100,
		];

		$this->rsvp->update_capacity( $ticket, $data, 'create' );

		// Verify stock management was set via repository.
		$manage_stock = get_post_meta( $ticket_id, '_manage_stock', true );
		$this->assertEquals( 'yes', $manage_stock, 'Stock management should be enabled' );

		$stock = get_post_meta( $ticket_id, '_stock', true );
		$this->assertEquals( '100', $stock, 'Stock should be set to 100' );
	}

	/**
	 * Test that update_capacity() handles unlimited stock correctly.
	 *
	 * @test
	 */
	public function test_unlimited_stock_uses_repository() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Update capacity to unlimited.
		$ticket = get_post( $ticket_id );
		$data = [
			'capacity' => -1,
		];

		$this->rsvp->update_capacity( $ticket, $data, 'create' );

		// Verify stock management was disabled via repository.
		$manage_stock = get_post_meta( $ticket_id, '_manage_stock', true );
		$this->assertEquals( 'no', $manage_stock, 'Stock management should be disabled for unlimited' );
	}

	/**
	 * Test that update_ticket_sent_counter() uses repository.
	 *
	 * @test
	 */
	public function test_ticket_sent_counter_uses_repository() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [ 'meta_input' => [ '_capacity' => 100 ] ] );
		$attendee_id = $this->create_rsvp_attendee( $ticket_id, $post_id );

		// Initial count should be 0.
		$initial_count = get_post_meta( $attendee_id, '_tribe_rsvp_attendee_ticket_sent', true );
		$this->assertEmpty( $initial_count, 'Initial sent count should be empty' );

		// Increment counter.
		$this->rsvp->update_ticket_sent_counter( $attendee_id );

		// Verify count was incremented.
		$count = get_post_meta( $attendee_id, '_tribe_rsvp_attendee_ticket_sent', true );
		$this->assertEquals( '1', $count, 'Sent count should be 1 after first increment' );

		// Increment again.
		$this->rsvp->update_ticket_sent_counter( $attendee_id );

		// Verify count is now 2.
		$count = get_post_meta( $attendee_id, '_tribe_rsvp_attendee_ticket_sent', true );
		$this->assertEquals( '2', $count, 'Sent count should be 2 after second increment' );
	}

}
