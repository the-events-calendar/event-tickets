<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker as Attendee_Maker;
use Tribe__Tickets__Attendance_Totals as Tickets__Attendance;

/**
 * Test Calculations
 *
 * @since TBD
 *
 * Class AttendanceTotalsTest
 *
 * @package Tribe\Tickets
 */
class AttendanceTotalsTest extends \Codeception\TestCase\WPTestCase {

	use RSVP_Ticket_Maker;
	use Attendee_Maker;

	public function setUp() {
		// before
		parent::setUp();

		$this->factory()->event = new Event();

		add_filter( 'tribe_tickets_user_can_manage_attendees', '__return_true' );

	}

	public function tearDown() {
		// your tear down methods here
		// then
		parent::tearDown();
	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_should_count_checked_in_attendees_correctly() {

		$event_id          = $this->factory()->event->create();
		$rsvp_id           = $this->create_rsvp_ticket( $event_id );
		$created_attendees = $this->create_many_attendees_for_ticket( 10, $rsvp_id, $event_id );

		foreach ( $created_attendees as $attendee ) {
			tribe( 'tickets.rsvp' )->checkin( $attendee, true );
		}

		$Tickets__Attendance = new Tickets__Attendance( $event_id );
		$checked_in          = $Tickets__Attendance->get_total_checked_in();

		$this->assertEquals( count( $created_attendees ), $checked_in );

	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_should_count_not_checked_in_attendees_correctly() {

		$event_id          = $this->factory()->event->create();
		$rsvp_id           = $this->create_rsvp_ticket( $event_id );
		$created_attendees = $this->create_many_attendees_for_ticket( 10, $rsvp_id, $event_id );

		$Tickets__Attendance = new Tickets__Attendance( $event_id );
		$not_checked_in      = $Tickets__Attendance->get_total_not_checked_in();

		$this->assertEquals( count( $created_attendees ), $not_checked_in );

	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_should_count_deleted_attendees_correctly() {

		$user = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user );

		$event_id          = $this->factory()->event->create();
		$rsvp_id           = $this->create_rsvp_ticket( $event_id );
		$created_attendees = $this->create_many_attendees_for_ticket( 10, $rsvp_id, $event_id );

		// delete 4 attendees
		for ( $x = 0; $x < 4; $x ++ ) {
			tribe( 'tickets.rsvp' )->delete_ticket( $event_id, $created_attendees[ $x ] );
		}

		$Tickets__Attendance = new Tickets__Attendance( $event_id );
		$total_deleted       = $Tickets__Attendance->get_total_deleted();

		$this->assertEquals( 4, $total_deleted );

	}

}
