<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker as Attendee_Maker;
use Tribe__Tickets__RSVP__Attendance_Totals as Tickets__Attendance;

/**
 * Test Calculations
 *
 * Class Attendance_RSVP_TotalsTest
 *
 * @package Tribe\Tickets
 */
class Attendance_RSVP_TotalsTest extends \Codeception\TestCase\WPTestCase {

	use RSVP_Ticket_Maker;
	use Attendee_Maker;

	public function setUp() {
		// before
		parent::setUp();

		$this->factory()->event = new Event();

		// let's avoid confirmation emails
		add_filter( 'tribe_tickets_rsvp_send_mail', '__return_false' );

		add_filter( 'tribe_tickets_user_can_manage_attendees', '__return_true' );

	}

	public function tearDown() {
		// your tear down methods here
		// then
		parent::tearDown();
	}

	/**
	 * @return \Tribe__Tickets__RSVP__Attendance_Totals
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
	public function it_should_count_total_rsvps_correctly() {

		$event_id          = $this->factory()->event->create();
		$rsvp_id           = $this->create_rsvp_ticket( $event_id );
		$created_attendees = $this->create_many_attendees_for_ticket( 10, $rsvp_id, $event_id );

		$tickets__attendance = $this->make_instance( $event_id );
		$total_rsvps         = $tickets__attendance->get_total_rsvps();

		$this->assertEquals( count( $created_attendees ), $total_rsvps );

	}

	/**
	 * @test
	 */
	public function it_should_count_total_going_correctly() {

		$event_id          = $this->factory()->event->create();
		$rsvp_id           = $this->create_rsvp_ticket( $event_id );
		$created_attendees = $this->create_many_attendees_for_ticket( 10, $rsvp_id, $event_id );

		$tickets__attendance = $this->make_instance( $event_id );
		$get_total_going     = $tickets__attendance->get_total_going();

		$this->assertEquals( count( $created_attendees ), $get_total_going );

	}

	/**
	 * @test
	 */
	public function it_should_count_total_not_going_correctly() {

		$event_id          = $this->factory()->event->create();
		$rsvp_id           = $this->create_rsvp_ticket( $event_id );
		$created_attendees = $this->create_many_attendees_for_ticket( 10, $rsvp_id, $event_id, [ 'rsvp_status' => 'no' ] );

		$tickets__attendance = $this->make_instance( $event_id );
		$get_total_not_going = $tickets__attendance->get_total_not_going();

		$this->assertEquals( count( $created_attendees ), $get_total_not_going );

	}

	/**
	 * @test
	 */
	public function it_should_count_total_and_going_and_not_going_correctly() {

		$event_id              = $this->factory()->event->create();
		$rsvp_id               = $this->create_rsvp_ticket( $event_id );
		$created_attendees_yes = $this->create_many_attendees_for_ticket( 5, $rsvp_id, $event_id );
		$created_attendees_no  = $this->create_many_attendees_for_ticket( 8, $rsvp_id, $event_id, [ 'rsvp_status' => 'no' ] );

		$tickets__attendance = $this->make_instance( $event_id );
		$get_total_not_going = $tickets__attendance->get_total_not_going();
		$get_total_going     = $tickets__attendance->get_total_going();
		$get_total_rsvps     = $tickets__attendance->get_total_rsvps();

		$this->assertEquals( count( $created_attendees_yes ), $get_total_going );
		$this->assertEquals( count( $created_attendees_no ), $get_total_not_going );
		$this->assertEquals( count( $created_attendees_yes ) + count( $created_attendees_no ), $get_total_rsvps );

	}

}
