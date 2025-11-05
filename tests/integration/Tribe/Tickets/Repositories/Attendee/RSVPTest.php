<?php

namespace Tribe\Tickets\Repositories\Attendee;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;

/**
 * Test the RSVP Attendee Repository methods.
 *
 * @since TBD
 */
class RSVPTest extends \Codeception\TestCase\WPTestCase {
	use Ticket_Maker;
	use Attendee_Maker;

	/**
	 * @before
	 */
	public function ensure_posts_are_ticketable(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	/**
	 * Test that get_field returns correct value for a valid field.
	 *
	 * @test
	 */
	public function test_get_field_returns_correct_value() {
		$post_id     = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket(
			$ticket_id,
			$post_id,
			[
				'full_name' => 'John Doe',
				'email'     => 'john@example.com',
			]
		);

		$repository = tribe( 'tickets.attendee-repository.rsvp' );

		$full_name = $repository->get_field( $attendee_id, 'full_name' );
		$this->assertEquals( 'John Doe', $full_name, 'get_field should return correct full_name value' );

		$email = $repository->get_field( $attendee_id, 'email' );
		$this->assertEquals( 'john@example.com', $email, 'get_field should return correct email value' );

		$ticket = $repository->get_field( $attendee_id, 'ticket_id' );
		$this->assertEquals( $ticket_id, $ticket, 'get_field should return correct ticket_id value' );

		$event = $repository->get_field( $attendee_id, 'event_id' );
		$this->assertEquals( $post_id, $event, 'get_field should return correct event_id value' );
	}

	/**
	 * Test that get_field returns null for invalid field.
	 *
	 * @test
	 */
	public function test_get_field_returns_null_for_invalid_field() {
		$post_id     = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		$repository = tribe( 'tickets.attendee-repository.rsvp' );
		$result     = $repository->get_field( $attendee_id, 'invalid_field_name' );

		$this->assertNull( $result, 'get_field should return null for invalid field name' );
	}

	/**
	 * Test that get_field supports all field aliases.
	 *
	 * @test
	 */
	public function test_get_field_supports_aliases() {
		$post_id     = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket(
			$ticket_id,
			$post_id,
			[
				'full_name'   => 'Jane Smith',
				'email'       => 'jane@example.com',
				'rsvp_status' => 'yes',
			]
		);

		$repository = tribe( 'tickets.attendee-repository.rsvp' );

		// Test key field aliases from the implementation.
		$this->assertEquals( $ticket_id, $repository->get_field( $attendee_id, 'ticket_id' ), 'ticket_id alias should work' );
		$this->assertEquals( $post_id, $repository->get_field( $attendee_id, 'event_id' ), 'event_id alias should work' );
		$this->assertEquals( $post_id, $repository->get_field( $attendee_id, 'post_id' ), 'post_id alias should work' );
		$this->assertEquals( 'Jane Smith', $repository->get_field( $attendee_id, 'full_name' ), 'full_name alias should work' );
		$this->assertEquals( 'jane@example.com', $repository->get_field( $attendee_id, 'email' ), 'email alias should work' );
		$this->assertEquals( 'yes', $repository->get_field( $attendee_id, 'attendee_status' ), 'attendee_status alias should work' );

		// Test that security_code, order_id, and optout aliases work.
		$security_code = $repository->get_field( $attendee_id, 'security_code' );
		$this->assertNotEmpty( $security_code, 'security_code alias should work and return a value' );

		$order_id = $repository->get_field( $attendee_id, 'order_id' );
		$this->assertNotEmpty( $order_id, 'order_id alias should work and return a value' );
	}

	/**
	 * Test that bulk_update updates multiple attendees successfully.
	 *
	 * @test
	 */
	public function test_bulk_update_updates_multiple_attendees() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$attendee_1 = $this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'rsvp_status' => 'yes' ] );
		$attendee_2 = $this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'rsvp_status' => 'yes' ] );
		$attendee_3 = $this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'rsvp_status' => 'yes' ] );

		$repository = tribe( 'tickets.attendee-repository.rsvp' );
		$results    = $repository->bulk_update(
			[ $attendee_1, $attendee_2, $attendee_3 ],
			[ 'attendee_status' => 'no' ]
		);

		$this->assertIsArray( $results, 'bulk_update should return an array' );
		$this->assertCount( 3, $results, 'bulk_update should return results for all 3 attendees' );
		$this->assertTrue( $results[ $attendee_1 ], 'Attendee 1 update should succeed' );
		$this->assertTrue( $results[ $attendee_2 ], 'Attendee 2 update should succeed' );
		$this->assertTrue( $results[ $attendee_3 ], 'Attendee 3 update should succeed' );

		// Verify the updates were actually applied.
		$this->assertEquals( 'no', $repository->get_field( $attendee_1, 'attendee_status' ), 'Attendee 1 status should be updated' );
		$this->assertEquals( 'no', $repository->get_field( $attendee_2, 'attendee_status' ), 'Attendee 2 status should be updated' );
		$this->assertEquals( 'no', $repository->get_field( $attendee_3, 'attendee_status' ), 'Attendee 3 status should be updated' );
	}

	/**
	 * Test that bulk_update returns correct results array structure.
	 *
	 * @test
	 */
	public function test_bulk_update_returns_results_array() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$attendee_1 = $this->create_attendee_for_ticket( $ticket_id, $post_id );
		$attendee_2 = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		$repository = tribe( 'tickets.attendee-repository.rsvp' );
		$results    = $repository->bulk_update(
			[ $attendee_1, $attendee_2 ],
			[ 'attendee_status' => 'no' ]
		);

		$this->assertArrayHasKey( $attendee_1, $results, 'Results should be indexed by attendee ID' );
		$this->assertArrayHasKey( $attendee_2, $results, 'Results should be indexed by attendee ID' );
		$this->assertIsBool( $results[ $attendee_1 ], 'Result values should be boolean' );
		$this->assertIsBool( $results[ $attendee_2 ], 'Result values should be boolean' );
	}

	/**
	 * Test that bulk_update handles failures correctly.
	 *
	 * @test
	 */
	public function test_bulk_update_handles_failures_correctly() {
		$post_id   = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$valid_attendee   = $this->create_attendee_for_ticket( $ticket_id, $post_id );
		$invalid_attendee = 99999;

		$repository = tribe( 'tickets.attendee-repository.rsvp' );
		$results    = $repository->bulk_update(
			[ $valid_attendee, $invalid_attendee ],
			[ 'attendee_status' => 'no' ]
		);

		$this->assertIsArray( $results, 'bulk_update should return an array even with failures' );
		$this->assertArrayHasKey( $valid_attendee, $results, 'Results should include valid attendee' );
		$this->assertArrayHasKey( $invalid_attendee, $results, 'Results should include invalid attendee' );
		$this->assertTrue( $results[ $valid_attendee ], 'Valid attendee update should succeed' );
		$this->assertFalse( $results[ $invalid_attendee ], 'Invalid attendee update should fail' );
	}

	/**
	 * Test that get_status_counts returns correct counts for an event.
	 *
	 * @test
	 */
	public function test_get_status_counts_returns_correct_counts() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Create 5 "yes" attendees.
		$this->create_many_attendees_for_ticket( 5, $ticket_id, $post_id, [ 'rsvp_status' => 'yes' ] );

		// Create 3 "no" attendees.
		$this->create_many_attendees_for_ticket( 3, $ticket_id, $post_id, [ 'rsvp_status' => 'no' ] );

		$repository = tribe( 'tickets.attendee-repository.rsvp' );
		$counts     = $repository->get_status_counts( $post_id );

		$this->assertIsArray( $counts, 'get_status_counts should return an array' );
		$this->assertArrayHasKey( 'yes', $counts, 'Counts should include "yes" status' );
		$this->assertArrayHasKey( 'no', $counts, 'Counts should include "no" status' );
		$this->assertEquals( 5, $counts['yes'], 'Should have 5 "yes" attendees' );
		$this->assertEquals( 3, $counts['no'], 'Should have 3 "no" attendees' );
	}

	/**
	 * Test that get_status_counts returns empty array for event without attendees.
	 *
	 * @test
	 */
	public function test_get_status_counts_returns_empty_for_event_without_attendees() {
		$post_id = static::factory()->post->create();
		// Create ticket but no attendees.
		$this->create_rsvp_ticket( $post_id );

		$repository = tribe( 'tickets.attendee-repository.rsvp' );
		$counts     = $repository->get_status_counts( $post_id );

		$this->assertIsArray( $counts, 'get_status_counts should return an array' );
		$this->assertEmpty( $counts, 'Counts should be empty for event with no attendees' );
	}

	/**
	 * Test that get_status_counts groups by status correctly.
	 *
	 * @test
	 */
	public function test_get_status_counts_groups_by_status() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Create attendees with different statuses.
		$this->create_many_attendees_for_ticket( 10, $ticket_id, $post_id, [ 'rsvp_status' => 'yes' ] );
		$this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id, [ 'rsvp_status' => 'no' ] );

		$repository = tribe( 'tickets.attendee-repository.rsvp' );
		$counts     = $repository->get_status_counts( $post_id );

		$this->assertEquals( 10, $counts['yes'], 'Should correctly group and count "yes" status' );
		$this->assertEquals( 2, $counts['no'], 'Should correctly group and count "no" status' );

		// Verify the sum.
		$total = array_sum( $counts );
		$this->assertEquals( 12, $total, 'Total count should be 12' );
	}
}
