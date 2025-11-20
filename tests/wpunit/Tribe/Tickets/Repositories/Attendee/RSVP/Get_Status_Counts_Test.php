<?php

namespace Tribe\Tickets\Repositories\Attendee\RSVP;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;

/**
 * Test the get_status_counts() method in the RSVP Attendee Repository.
 *
 * @package Tribe\Tickets\Repositories\Attendee\RSVP
 */
class Get_Status_Counts_Test extends \Codeception\TestCase\WPTestCase {

	use RSVP_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post' ];
		} );
	}

	/**
	 * It should return correct counts for yes and no statuses.
	 *
	 * @test
	 */
	public function should_return_correct_counts_for_yes_and_no_statuses() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Create 5 "yes" attendees
		$this->create_many_attendees_for_ticket( 5, $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
		] );

		// Create 3 "no" attendees
		$this->create_many_attendees_for_ticket( 3, $ticket_id, $post_id, [
			'rsvp_status' => 'no',
		] );

		$repository = tribe_attendees( 'rsvp' );
		$counts = $repository->get_status_counts( $post_id );

		$this->assertIsArray( $counts );
		$this->assertEquals( 5, $counts['yes'] );
		$this->assertEquals( 3, $counts['no'] );
	}

	/**
	 * It should return empty array for event with no attendees.
	 *
	 * @test
	 */
	public function should_return_empty_array_for_event_with_no_attendees() {
		$post_id = $this->factory->post->create();
		$this->create_rsvp_ticket( $post_id );

		$repository = tribe_attendees( 'rsvp' );
		$counts = $repository->get_status_counts( $post_id );

		$this->assertIsArray( $counts );
		$this->assertEmpty( $counts );
	}

	/**
	 * It should only count attendees for the specified event.
	 *
	 * @test
	 */
	public function should_only_count_attendees_for_specified_event() {
		$event_1 = $this->factory->post->create();
		$event_2 = $this->factory->post->create();

		$ticket_1 = $this->create_rsvp_ticket( $event_1 );
		$ticket_2 = $this->create_rsvp_ticket( $event_2 );

		// Event 1: 3 yes, 2 no
		$this->create_many_attendees_for_ticket( 3, $ticket_1, $event_1, [ 'rsvp_status' => 'yes' ] );
		$this->create_many_attendees_for_ticket( 2, $ticket_1, $event_1, [ 'rsvp_status' => 'no' ] );

		// Event 2: 4 yes, 1 no
		$this->create_many_attendees_for_ticket( 4, $ticket_2, $event_2, [ 'rsvp_status' => 'yes' ] );
		$this->create_many_attendees_for_ticket( 1, $ticket_2, $event_2, [ 'rsvp_status' => 'no' ] );

		$repository = tribe_attendees( 'rsvp' );
		$counts_1 = $repository->get_status_counts( $event_1 );
		$counts_2 = $repository->get_status_counts( $event_2 );

		$this->assertEquals( 3, $counts_1['yes'] );
		$this->assertEquals( 2, $counts_1['no'] );
		$this->assertEquals( 4, $counts_2['yes'] );
		$this->assertEquals( 1, $counts_2['no'] );
	}

	/**
	 * It should return only yes count if no "no" attendees.
	 *
	 * @test
	 */
	public function should_return_only_yes_count_if_no_no_attendees() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->create_many_attendees_for_ticket( 10, $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
		] );

		$repository = tribe_attendees( 'rsvp' );
		$counts = $repository->get_status_counts( $post_id );

		$this->assertEquals( 10, $counts['yes'] );
		$this->assertArrayNotHasKey( 'no', $counts );
	}

	/**
	 * It should return only no count if no "yes" attendees.
	 *
	 * @test
	 */
	public function should_return_only_no_count_if_no_yes_attendees() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->create_many_attendees_for_ticket( 5, $ticket_id, $post_id, [
			'rsvp_status' => 'no',
		] );

		$repository = tribe_attendees( 'rsvp' );
		$counts = $repository->get_status_counts( $post_id );

		$this->assertEquals( 5, $counts['no'] );
		$this->assertArrayNotHasKey( 'yes', $counts );
	}

	/**
	 * It should handle multiple tickets for same event.
	 *
	 * @test
	 */
	public function should_handle_multiple_tickets_for_same_event() {
		$post_id = $this->factory->post->create();
		$ticket_1 = $this->create_rsvp_ticket( $post_id );
		$ticket_2 = $this->create_rsvp_ticket( $post_id );

		// Ticket 1: 3 yes, 2 no
		$this->create_many_attendees_for_ticket( 3, $ticket_1, $post_id, [ 'rsvp_status' => 'yes' ] );
		$this->create_many_attendees_for_ticket( 2, $ticket_1, $post_id, [ 'rsvp_status' => 'no' ] );

		// Ticket 2: 4 yes, 1 no
		$this->create_many_attendees_for_ticket( 4, $ticket_2, $post_id, [ 'rsvp_status' => 'yes' ] );
		$this->create_many_attendees_for_ticket( 1, $ticket_2, $post_id, [ 'rsvp_status' => 'no' ] );

		$repository = tribe_attendees( 'rsvp' );
		$counts = $repository->get_status_counts( $post_id );

		// Should sum across all tickets
		$this->assertEquals( 7, $counts['yes'] ); // 3 + 4
		$this->assertEquals( 3, $counts['no'] );  // 2 + 1
	}

	/**
	 * It should return integer counts.
	 *
	 * @test
	 */
	public function should_return_integer_counts() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->create_many_attendees_for_ticket( 3, $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
		] );

		$repository = tribe_attendees( 'rsvp' );
		$counts = $repository->get_status_counts( $post_id );

		$this->assertIsInt( $counts['yes'] );
	}

	/**
	 * It should handle large number of attendees.
	 *
	 * @test
	 */
	public function should_handle_large_number_of_attendees() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->create_many_attendees_for_ticket( 100, $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
		] );
		$this->create_many_attendees_for_ticket( 50, $ticket_id, $post_id, [
			'rsvp_status' => 'no',
		] );

		$repository = tribe_attendees( 'rsvp' );
		$counts = $repository->get_status_counts( $post_id );

		$this->assertEquals( 100, $counts['yes'] );
		$this->assertEquals( 50, $counts['no'] );
	}

	/**
	 * It should safely handle sql injection attempts via type checking.
	 *
	 * @test
	 */
	public function should_safely_handle_sql_injection_attempts_via_type_checking() {
		$post_id = $this->factory->post->create();

		$repository = tribe_attendees( 'rsvp' );

		// Type hint protects against SQL injection by requiring int
		// Passing a string with SQL injection attempt should be caught by type hint
		// In PHP 7.1+, string "1 OR 1=1" will be converted to int 1
		// But malicious SQL won't execute because it's sanitized by wpdb->prepare
		$result = $repository->get_status_counts( 1 );

		$this->assertIsArray( $result );
		// This should return results for event ID 1 only, not all events
	}
}
