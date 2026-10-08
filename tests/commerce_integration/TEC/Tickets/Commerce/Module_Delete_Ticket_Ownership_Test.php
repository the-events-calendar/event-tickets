<?php

namespace TEC\Tickets\Commerce;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * Guards `Module::delete_ticket()` against deleting a ticket or attendee that belongs to a
 * different event than the one the caller was authorized against. A request authorized to edit
 * one post must not be able to delete tickets or attendees from any other event.
 */
class Module_Delete_Ticket_Ownership_Test extends WPTestCase {

	use Ticket_Maker;
	use Attendee_Maker;

	private function module(): Module {
		return tribe( Module::class );
	}

	public function test_does_not_delete_ticket_belonging_to_another_event(): void {
		$owner_event   = static::factory()->post->create();
		$ticket_id     = $this->create_tc_ticket( $owner_event );
		$another_event = static::factory()->post->create();

		$deleted = $this->module()->delete_ticket( $another_event, $ticket_id );

		$this->assertFalse( $deleted, 'Deleting a ticket against an unrelated event must be refused.' );
		$this->assertInstanceOf( \WP_Post::class, get_post( $ticket_id ), 'The ticket must still exist.' );
	}

	public function test_does_not_delete_attendee_belonging_to_another_event(): void {
		$owner_event   = static::factory()->post->create();
		$ticket_id     = $this->create_tc_ticket( $owner_event );
		$attendee_id   = $this->create_attendee_for_ticket( $ticket_id, $owner_event );
		$another_event = static::factory()->post->create();

		$deleted = $this->module()->delete_ticket( $another_event, $attendee_id );

		$this->assertFalse( $deleted, 'Deleting an attendee against an unrelated event must be refused.' );
		$this->assertInstanceOf( \WP_Post::class, get_post( $attendee_id ), 'The attendee must still exist.' );
	}

	public function test_deletes_ticket_belonging_to_the_given_event(): void {
		$owner_event = static::factory()->post->create();
		$ticket_id   = $this->create_tc_ticket( $owner_event );

		$deleted = $this->module()->delete_ticket( $owner_event, $ticket_id );

		$this->assertTrue( $deleted, 'Deleting a ticket against its own event must succeed.' );
		$this->assertNull( get_post( $ticket_id ), 'The ticket must be deleted.' );
	}

	public function test_deletes_attendee_when_no_event_is_asserted(): void {
		$owner_event = static::factory()->post->create();
		$ticket_id   = $this->create_tc_ticket( $owner_event );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $owner_event );

		// A null event is the bulk attendee-delete path (Attendees_Table); it must keep working.
		$deleted = $this->module()->delete_ticket( null, $attendee_id );

		$this->assertTrue( $deleted, 'Deleting an attendee with no asserted event must succeed.' );
		$this->assertNull( get_post( $attendee_id ), 'The attendee must be deleted.' );
	}
}
