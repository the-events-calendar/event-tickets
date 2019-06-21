<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker as Attendee_Maker;
use Tribe__Tickets__Attendance_Totals as Tickets__Attendance;
use Tribe__Tickets_Plus__Commerce__WooCommerce__Status_Manager as WooManager;

/**
 * Test Calculations
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
	 * @return Tickets__Attendance
	 */
	private function make_instance( $event_id ) {

		return new Tickets__Attendance( $event_id );
	}

	/**
	 * @test
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance( 0 );

		$this->assertInstanceOf( Tickets__Attendance::class, $sut );
	}

	/**
	 * @test
	 */
	public function it_should_count_checked_in_attendees_correctly() {

		$event_id          = $this->factory()->event->create();
		$rsvp_id           = $this->create_rsvp_ticket( $event_id );
		$created_attendees = $this->create_many_attendees_for_ticket( 10, $rsvp_id, $event_id );

		foreach ( $created_attendees as $attendee ) {
			tribe( 'tickets.rsvp' )->checkin( $attendee, true );
		}

		$tickets__attendance = $this->make_instance( $event_id );
		$checked_in          = $tickets__attendance->get_total_checked_in();

		$this->assertEquals( count( $created_attendees ), $checked_in );

	}

	/**
	 * @test
	 */
	public function it_should_count_not_checked_in_attendees_correctly() {

		$event_id          = $this->factory()->event->create();
		$rsvp_id           = $this->create_rsvp_ticket( $event_id );
		$created_attendees = $this->create_many_attendees_for_ticket( 10, $rsvp_id, $event_id );

		$tickets__attendance = $this->make_instance( $event_id );
		$not_checked_in      = $tickets__attendance->get_total_not_checked_in();

		$this->assertEquals( count( $created_attendees ), $not_checked_in );

	}

	/**
	 * @test
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

		$tickets__attendance = $this->make_instance( $event_id );
		$total_deleted       = $tickets__attendance->get_total_deleted();

		$this->assertEquals( 4, $total_deleted );

	}

}
