<?php
/**
 * Tests for the V2 Attendee Repository.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Repositories
 */

namespace TEC\Tickets\RSVP\V2\Repositories;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\RSVP_V2\TC_RSVP_Attendee_Maker;
use Tribe\Tickets\Test\RSVP_V2\TC_RSVP_Ticket_Maker;

/**
 * Class Attendee_Repository_Test
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Repositories
 */
class Attendee_Repository_Test extends WPTestCase {

	use TC_RSVP_Ticket_Maker;
	use TC_RSVP_Attendee_Maker;

	/**
	 * @before
	 */
	public function set_up_ticketable_post_types(): void {
		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', static function () {
			return [ 'post' ];
		} );
	}

	/**
	 * @test
	 */
	public function it_should_filter_by_event(): void {
		$post_1_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$post_2_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$ticket_1_id = $this->create_tc_rsvp_ticket( $post_1_id );
		$ticket_2_id = $this->create_tc_rsvp_ticket( $post_2_id );

		$attendee_1_id = $this->create_tc_rsvp_attendee( $ticket_1_id, $post_1_id );
		$attendee_2_id = $this->create_tc_rsvp_attendee( $ticket_2_id, $post_2_id );

		$repo = new Attendee_Repository();
		$attendees = $repo->by( 'event', $post_1_id )->all();

		$attendee_ids = array_map( static fn( $attendee ) => $attendee->ID, $attendees );

		$this->assertContains( $attendee_1_id, $attendee_ids, 'Attendee for post 1 should be returned' );
		$this->assertNotContains( $attendee_2_id, $attendee_ids, 'Attendee for post 2 should not be returned' );
	}

	/**
	 * @test
	 */
	public function it_should_filter_by_ticket(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$ticket_1_id = $this->create_tc_rsvp_ticket( $post_id );
		$ticket_2_id = $this->create_tc_rsvp_ticket( $post_id );

		$attendee_1_id = $this->create_tc_rsvp_attendee( $ticket_1_id, $post_id );
		$attendee_2_id = $this->create_tc_rsvp_attendee( $ticket_2_id, $post_id );

		$repo = new Attendee_Repository();
		$attendees = $repo->by( 'ticket', $ticket_1_id )->all();

		$attendee_ids = array_map( static fn( $attendee ) => $attendee->ID, $attendees );

		$this->assertContains( $attendee_1_id, $attendee_ids, 'Attendee for ticket 1 should be returned' );
		$this->assertNotContains( $attendee_2_id, $attendee_ids, 'Attendee for ticket 2 should not be returned' );
	}

	/**
	 * @test
	 */
	public function it_should_filter_by_going_status(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		// Create going attendees.
		$going_ids = $this->create_going_tc_rsvp_attendees( 2, $ticket_id, $post_id );

		// Create not going attendees.
		$not_going_ids = $this->create_not_going_tc_rsvp_attendees( 3, $ticket_id, $post_id );

		$repo = new Attendee_Repository();
		$going_attendees = $repo->by( 'going', true )->all();

		$going_attendee_ids = array_map( static fn( $attendee ) => $attendee->ID, $going_attendees );

		foreach ( $going_ids as $going_id ) {
			$this->assertContains( $going_id, $going_attendee_ids, 'Going attendee should be returned' );
		}

		foreach ( $not_going_ids as $not_going_id ) {
			$this->assertNotContains( $not_going_id, $going_attendee_ids, 'Not going attendee should not be returned' );
		}
	}

	/**
	 * @test
	 */
	public function it_should_filter_by_not_going_status(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		// Create going attendees.
		$going_ids = $this->create_going_tc_rsvp_attendees( 2, $ticket_id, $post_id );

		// Create not going attendees.
		$not_going_ids = $this->create_not_going_tc_rsvp_attendees( 3, $ticket_id, $post_id );

		$repo = new Attendee_Repository();
		$not_going_attendees = $repo->by( 'not_going', true )->all();

		$not_going_attendee_ids = array_map( static fn( $attendee ) => $attendee->ID, $not_going_attendees );

		foreach ( $not_going_ids as $not_going_id ) {
			$this->assertContains( $not_going_id, $not_going_attendee_ids, 'Not going attendee should be returned' );
		}

		foreach ( $going_ids as $going_id ) {
			$this->assertNotContains( $going_id, $not_going_attendee_ids, 'Going attendee should not be returned' );
		}
	}

	/**
	 * @test
	 */
	public function it_should_filter_by_rsvp_status_meta(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		// Create attendees with different statuses.
		$yes_attendee_id = $this->create_tc_rsvp_attendee( $ticket_id, $post_id, [ 'rsvp_status' => 'yes' ] );
		$no_attendee_id = $this->create_tc_rsvp_attendee( $ticket_id, $post_id, [ 'rsvp_status' => 'no' ] );

		$repo = new Attendee_Repository();
		$yes_attendees = $repo->by( 'rsvp_status', 'yes' )->all();

		$this->assertCount( 1, $yes_attendees, 'Should return only one attendee with status yes' );
		$this->assertSame( $yes_attendee_id, $yes_attendees[0]->ID );
	}

	/**
	 * @test
	 */
	public function it_should_return_count(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$this->create_many_tc_rsvp_attendees( 5, $ticket_id, $post_id );

		$repo = new Attendee_Repository();
		$count = $repo->count();

		$this->assertSame( 5, $count );
	}

	/**
	 * @test
	 */
	public function it_should_chain_multiple_filters(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		// Create going and not going attendees.
		$this->create_going_tc_rsvp_attendees( 3, $ticket_id, $post_id );
		$this->create_not_going_tc_rsvp_attendees( 2, $ticket_id, $post_id );

		$repo = new Attendee_Repository();
		$going_for_event = $repo
			->by( 'event', $post_id )
			->by( 'going', true )
			->all();

		$this->assertCount( 3, $going_for_event, 'Should return only going attendees for the event' );
	}
}
