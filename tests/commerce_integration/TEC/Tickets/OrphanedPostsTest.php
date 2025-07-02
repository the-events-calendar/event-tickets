<?php

namespace Tribe\Tickets;

use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker as TC_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker as TC_Order_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tests\Traits\With_Uopz;

/**
 * Test class for orphaned posts functionality.
 *
 * @since TBD
 */
class OrphanedPostsTest extends \Codeception\TestCase\WPTestCase {

	use RSVP_Ticket_Maker;
	use TC_Ticket_Maker;
	use TC_Order_Maker;
	use Attendee_Maker;
	use With_Uopz;
	
	/**
	 * Test get_orphaned_post_ids with RSVP provider.
	 *
	 * @test
	 */
	public function should_get_orphaned_post_ids_for_rsvp_provider() {
		// Create an event that will be deleted to create orphaned tickets.
		$event_id = tribe_events()->set_args( [
			'title'      => 'Test Event for Orphaned RSVP',
			'status'     => 'publish',
			'start_date' => '2025-01-01 00:00:00',
			'duration'   => 2 * HOUR_IN_SECONDS,
		] )->create()->ID;

		// Create RSVP tickets for the event.
		$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id );

		// Create attendees for the RSVP ticket.
		$attendee_ids = $this->create_many_attendees_for_ticket( 3, $rsvp_ticket_id, $event_id );

		// Verify tickets and attendees exist before deletion.
		$this->assertNotEmpty( $rsvp_ticket_id );
		$this->assertNotEmpty( $attendee_ids );

		// Delete the event to create orphaned tickets/attendees.
		wp_delete_post( $event_id, true );

		// Get orphaned post IDs for RSVP provider.
		$orphaned_post_ids = tribe( 'Tribe__Tickets__RSVP' )->get_orphaned_post_ids( 'Tribe__Tickets__RSVP' );

		// Should find the orphaned RSVP ticket and attendees.
		$this->assertContains( $rsvp_ticket_id, $orphaned_post_ids );
		foreach ( $attendee_ids as $attendee_id ) {
			$this->assertContains( $attendee_id, $orphaned_post_ids );
		}
	}

	/**
	 * Test get_orphaned_post_ids with Tickets Commerce provider.
	 *
	 * @test
	 */
	public function should_get_orphaned_post_ids_for_tickets_commerce_provider() {
		// Create an event that will be deleted to create orphaned tickets.
		$event_id = tribe_events()->set_args( [
			'title'      => 'Test Event for Orphaned TC',
			'status'     => 'publish',
			'start_date' => '2025-01-01 00:00:00',
			'duration'   => 2 * HOUR_IN_SECONDS,
		] )->create()->ID;

		// Create Tickets Commerce tickets for the event.
		$tc_ticket_id = $this->create_tc_ticket( $event_id );

		// Create an order and attendees for the TC ticket.
		$order_id = $this->create_order( [ $tc_ticket_id => 3 ] );

		// Verify tickets and attendees exist before deletion.
		$this->assertNotEmpty( $tc_ticket_id );
		$this->assertNotEmpty( $order_id );

		// Get attendees from the order.
		$attendee_ids = tribe( Module::class )->get_attendees_by_order_id( $order_id );
		$attendee_ids = wp_list_pluck( $attendee_ids, 'ID' );

		$this->assertNotEmpty( $attendee_ids );

		// Delete the event to create orphaned tickets/attendees.
		wp_delete_post( $event_id, true );

		// Get orphaned post IDs for Tickets Commerce provider.
		$orphaned_post_ids = tribe( Module::class )->get_orphaned_post_ids( 'TEC\Tickets\Commerce\Module' );

		// Should find the orphaned TC ticket and attendees.
		$this->assertContains( $tc_ticket_id, $orphaned_post_ids );
		foreach ( $attendee_ids as $attendee_id ) {
			$this->assertContains( $attendee_id, $orphaned_post_ids );
		}
	}

	/**
	 * Test get_orphaned_post_ids with invalid provider.
	 *
	 * @test
	 */
	public function should_return_empty_array_for_invalid_provider() {
		$orphaned_post_ids = tribe( 'Tribe__Tickets__RSVP' )->get_orphaned_post_ids( 'Invalid_Provider' );

		$this->assertEmpty( $orphaned_post_ids );
	}

	/**
	 * Test get_orphaned_posts with count = false.
	 *
	 * @test
	 */
	public function should_get_orphaned_posts_with_count_false() {
		// Create an event that will be deleted.
		$event_id = tribe_events()->set_args( [
			'title'      => 'Test Event for Orphaned Posts Count',
			'status'     => 'publish',
			'start_date' => '2025-01-01 00:00:00',
			'duration'   => 2 * HOUR_IN_SECONDS,
		] )->create()->ID;

		// Create RSVP tickets and attendees.
		$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id );
		$rsvp_attendee_ids = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket_id, $event_id );

		// Delete the event.
		wp_delete_post( $event_id, true );

		// Get orphaned posts (should return array of IDs).
		$orphaned_posts = tribe( 'Tribe__Tickets__RSVP' )->get_orphaned_posts( false );

		$this->assertIsArray( $orphaned_posts );
		$this->assertContains( $rsvp_ticket_id, $orphaned_posts );
		foreach ( $rsvp_attendee_ids as $attendee_id ) {
			$this->assertContains( $attendee_id, $orphaned_posts );
		}
	}

	/**
	 * Test get_orphaned_posts with count = true.
	 *
	 * @test
	 */
	public function should_get_orphaned_posts_with_count_true() {
		// Create an event that will be deleted.
		$event_id = tribe_events()->set_args( [
			'title'      => 'Test Event for Orphaned Posts Count',
			'status'     => 'publish',
			'start_date' => '2025-01-01 00:00:00',
			'duration'   => 2 * HOUR_IN_SECONDS,
		] )->create()->ID;

		// Create RSVP tickets and attendees.
		$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id );
		$rsvp_attendee_ids = $this->create_many_attendees_for_ticket( 3, $rsvp_ticket_id, $event_id );

		// Delete the event.
		wp_delete_post( $event_id, true );

		// Get orphaned posts count (should return integer).
		$orphaned_count = tribe( 'Tribe__Tickets__RSVP' )->get_orphaned_posts( true );

		$this->assertIsInt( $orphaned_count );
		$this->assertEquals( 4, $orphaned_count ); // 1 ticket + 3 attendees
	}

	/**
	 * Test caching functionality.
	 *
	 * @test
	 */
	public function should_cache_orphaned_post_ids() {
		// Create an event that will be deleted.
		$event_id = tribe_events()->set_args( [
			'title'      => 'Test Event for Caching',
			'status'     => 'publish',
			'start_date' => '2025-01-01 00:00:00',
			'duration'   => 2 * HOUR_IN_SECONDS,
		] )->create()->ID;

		// Create RSVP tickets and attendees.
		$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id );
		$rsvp_attendee_ids = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket_id, $event_id );

		// Delete the event.
		wp_delete_post( $event_id, true );

		// First call should query database and cache results.
		$orphaned_posts_1 = tribe( 'Tribe__Tickets__RSVP' )->get_orphaned_post_ids( 'Tribe__Tickets__RSVP' );

		// Second call should return cached results.
		$orphaned_posts_2 = tribe( 'Tribe__Tickets__RSVP' )->get_orphaned_post_ids( 'Tribe__Tickets__RSVP' );

		// Results should be identical (cached).
		$this->assertEquals( $orphaned_posts_1, $orphaned_posts_2 );
		$this->assertContains( $rsvp_ticket_id, $orphaned_posts_1 );
		foreach ( $rsvp_attendee_ids as $attendee_id ) {
			$this->assertContains( $attendee_id, $orphaned_posts_1 );
		}
	}

	/**
	 * Test cache invalidation when posts are deleted.
	 *
	 * @test
	 */
	public function should_invalidate_cache_when_posts_are_deleted() {
		// Create an event that will be deleted.
		$event_id = tribe_events()->set_args( [
			'title'      => 'Test Event for Cache Invalidation',
			'status'     => 'publish',
			'start_date' => '2025-01-01 00:00:00',
			'duration'   => 2 * HOUR_IN_SECONDS,
		] )->create()->ID;

		// Create RSVP tickets and attendees.
		$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id );
		$rsvp_attendee_ids = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket_id, $event_id );

		// Delete the event.
		wp_delete_post( $event_id, true );

		// First call to populate cache.
		$orphaned_posts_1 = tribe( 'Tribe__Tickets__RSVP' )->get_orphaned_post_ids( 'Tribe__Tickets__RSVP' );

		// Delete one of the orphaned posts to trigger cache invalidation.
		wp_delete_post( $rsvp_ticket_id, true );

		// Second call should return updated results (cache should be invalidated).
		$orphaned_posts_2 = tribe( 'Tribe__Tickets__RSVP' )->get_orphaned_post_ids( 'Tribe__Tickets__RSVP' );

		// The deleted ticket should not be in the second result.
		$this->assertNotContains( $rsvp_ticket_id, $orphaned_posts_2 );
		$this->assertContains( $rsvp_ticket_id, $orphaned_posts_1 );
	}

	/**
	 * Test with no orphaned posts.
	 *
	 * @test
	 */
	public function should_return_empty_array_when_no_orphaned_posts() {
		// Get orphaned post IDs when there are no orphaned posts.
		$orphaned_posts = tribe( 'Tribe__Tickets__RSVP' )->get_orphaned_post_ids( 'Tribe__Tickets__RSVP' );

		$this->assertEmpty( $orphaned_posts );
		$this->assertIsArray( $orphaned_posts );
	}

	/**
	 * Test mixed providers scenario.
	 *
	 * @test
	 */
	public function should_handle_mixed_providers_correctly() {
		// Create an event that will be deleted.
		$event_id = tribe_events()->set_args( [
			'title'      => 'Test Event for Mixed Providers',
			'status'     => 'publish',
			'start_date' => '2025-01-01 00:00:00',
			'duration'   => 2 * HOUR_IN_SECONDS,
		] )->create()->ID;

		// Create both RSVP and TC tickets.
		$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id );
		$tc_ticket_id = $this->create_tc_ticket( $event_id );

		// Create orders and attendees for both ticket types.
		$rsvp_attendee_ids = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket_id, $event_id );
		$tc_order_id = $this->create_order( [ $tc_ticket_id => 3 ] );

		// Get attendees from the order.
		$tc_attendee_ids = tribe( Module::class )->get_attendees_by_order_id( $tc_order_id );
		$tc_attendee_ids = wp_list_pluck( $tc_attendee_ids, 'ID' );

		// Delete the event.
		wp_delete_post( $event_id, true );

		// Get orphaned posts for RSVP provider.
		$rsvp_orphaned = tribe( 'Tribe__Tickets__RSVP' )->get_orphaned_post_ids( 'Tribe__Tickets__RSVP' );

		// Get orphaned posts for TC provider.
		$tc_orphaned = tribe( Module::class )->get_orphaned_post_ids( 'TEC\Tickets\Commerce\Module' );

		// RSVP should only contain RSVP-related posts.
		$this->assertContains( $rsvp_ticket_id, $rsvp_orphaned );
		foreach ( $rsvp_attendee_ids as $attendee_id ) {
			$this->assertContains( $attendee_id, $rsvp_orphaned );
		}
		$this->assertNotContains( $tc_ticket_id, $rsvp_orphaned );

		// TC should only contain TC-related posts.
		$this->assertContains( $tc_ticket_id, $tc_orphaned );
		foreach ( $tc_attendee_ids as $attendee_id ) {
			$this->assertContains( $attendee_id, $tc_orphaned );
		}
		$this->assertNotContains( $rsvp_ticket_id, $tc_orphaned );
	}
} 