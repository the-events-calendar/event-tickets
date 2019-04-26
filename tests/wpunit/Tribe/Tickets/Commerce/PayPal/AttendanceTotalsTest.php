<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use  Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
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

	use Ticket_Maker;
	use Attendee_Maker;

	public function setUp() {
		// before
		parent::setUp();

		$this->factory()->event = new Event();

		// let's avoid die()s
		add_filter( 'tribe_exit', function () {
			return [ $this, 'dont_die' ];
		} );

		/**
		 * Enable TTP
		 */
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );

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
		$tpp_id           = $this->create_paypal_ticket( $event_id, 5 );
		$created_attendees = $this->create_many_attendees_for_ticket( 10, $tpp_id, $event_id );

		foreach ( $created_attendees as $attendee ) {
			tribe( 'tickets.commerce.paypal' )->checkin( $attendee, true );
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
		$tpp_id           = $this->create_paypal_ticket( $event_id, 5 );
		$created_attendees = $this->create_many_attendees_for_ticket( 10, $tpp_id, $event_id );

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
		$tpp_id           = $this->create_paypal_ticket( $event_id, 5 );
		$created_attendees = $this->create_many_attendees_for_ticket( 10, $tpp_id, $event_id );

		// delete 4 attendees
		for ( $x = 0; $x < 4; $x ++ ) {
			tribe( 'tickets.commerce.paypal' )->delete_ticket( $event_id, $created_attendees[ $x ] );
		}

		$Tickets__Attendance = new Tickets__Attendance( $event_id );
		$total_deleted       = $Tickets__Attendance->get_total_deleted();

		$this->assertEquals( 4, $total_deleted );

	}

}
