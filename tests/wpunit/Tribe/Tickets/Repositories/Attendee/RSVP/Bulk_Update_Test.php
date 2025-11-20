<?php

namespace Tribe\Tickets\Repositories\Attendee\RSVP;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;

/**
 * Test the bulk_update() method in the RSVP Attendee Repository.
 *
 * @package Tribe\Tickets\Repositories\Attendee\RSVP
 */
class Bulk_Update_Test extends \Codeception\TestCase\WPTestCase {

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
	 * It should update multiple attendees with same field values.
	 *
	 * @test
	 */
	public function should_update_multiple_attendees_with_same_field_values() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$attendee_ids = $this->create_many_attendees_for_ticket( 3, $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
		] );

		$repository = tribe_attendees( 'rsvp' );
		$results = $repository->bulk_update( $attendee_ids, [
			'attendee_status' => 'no',
		] );

		// All should succeed
		$this->assertCount( 3, $results );
		foreach ( $results as $attendee_id => $success ) {
			$this->assertTrue( $success, "Attendee {$attendee_id} should update successfully" );
		}

		// Verify updates
		foreach ( $attendee_ids as $attendee_id ) {
			$status = get_post_meta( $attendee_id, '_tribe_rsvp_status', true );
			$this->assertEquals( 'no', $status );
		}
	}

	/**
	 * It should return results array indexed by attendee ID.
	 *
	 * @test
	 */
	public function should_return_results_array_indexed_by_attendee_id() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$attendee_ids = $this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id );

		$repository = tribe_attendees( 'rsvp' );
		$results = $repository->bulk_update( $attendee_ids, [
			'attendee_status' => 'yes',
		] );

		$this->assertIsArray( $results );
		$this->assertCount( 2, $results );

		foreach ( $attendee_ids as $attendee_id ) {
			$this->assertArrayHasKey( $attendee_id, $results );
			$this->assertIsBool( $results[ $attendee_id ] );
		}
	}

	/**
	 * It should handle empty attendee IDs array.
	 *
	 * @test
	 */
	public function should_handle_empty_attendee_ids_array() {
		$repository = tribe_attendees( 'rsvp' );
		$results = $repository->bulk_update( [], [
			'attendee_status' => 'yes',
		] );

		$this->assertIsArray( $results );
		$this->assertEmpty( $results );
	}

	/**
	 * It should handle partial failures.
	 *
	 * @test
	 */
	public function should_handle_partial_failures() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$valid_attendee_1 = $this->create_attendee_for_ticket( $ticket_id, $post_id );
		$valid_attendee_2 = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		$repository = tribe_attendees( 'rsvp' );
		$results = $repository->bulk_update( [ $valid_attendee_1, $valid_attendee_2 ], [
			'attendee_status' => 'yes',
		] );

		$this->assertCount( 2, $results );
		$this->assertTrue( $results[ $valid_attendee_1 ], "Valid attendee 1 should succeed" );
		$this->assertTrue( $results[ $valid_attendee_2 ], "Valid attendee 2 should succeed" );
	}

	/**
	 * It should update multiple fields at once.
	 *
	 * @test
	 */
	public function should_update_multiple_fields_at_once() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$attendee_ids = $this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id );

		$repository = tribe_attendees( 'rsvp' );
		$results = $repository->bulk_update( $attendee_ids, [
			'attendee_status' => 'no',
			'optout'          => '1',
		] );

		// All should succeed
		foreach ( $results as $success ) {
			$this->assertTrue( $success );
		}

		// Verify both fields were updated
		foreach ( $attendee_ids as $attendee_id ) {
			$rsvp = tribe( 'tickets.rsvp' );
			$status = get_post_meta( $attendee_id, $rsvp::ATTENDEE_RSVP_KEY, true );
			$optout = get_post_meta( $attendee_id, $rsvp::ATTENDEE_OPTOUT_KEY, true );

			$this->assertEquals( 'no', $status );
			$this->assertEquals( '1', $optout );
		}
	}

	/**
	 * It should handle updating large number of attendees.
	 *
	 * @test
	 */
	public function should_handle_updating_large_number_of_attendees() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$attendee_ids = $this->create_many_attendees_for_ticket( 20, $ticket_id, $post_id );

		$repository = tribe_attendees( 'rsvp' );
		$results = $repository->bulk_update( $attendee_ids, [
			'attendee_status' => 'no',
		] );

		$this->assertCount( 20, $results );

		// All should succeed
		foreach ( $results as $success ) {
			$this->assertTrue( $success );
		}
	}

	/**
	 * It should update attendee email.
	 *
	 * @test
	 */
	public function should_update_attendee_email() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$attendee_ids = $this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id );

		$repository = tribe_attendees( 'rsvp' );
		$results = $repository->bulk_update( $attendee_ids, [
			'email' => 'newemail@example.com',
		] );

		// All should succeed
		foreach ( $results as $success ) {
			$this->assertTrue( $success );
		}

		// Verify email was updated
		foreach ( $attendee_ids as $attendee_id ) {
			$rsvp = tribe( 'tickets.rsvp' );
			$email = get_post_meta( $attendee_id, $rsvp->email, true );
			$this->assertEquals( 'newemail@example.com', $email );
		}
	}
}
