<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Order_Maker as PayPal_Order_Maker;
use Tribe__Tickets__Commerce__PayPal__Attendance_Totals as Tickets__Attendance;


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

	use PayPal_Order_Maker;
	use PayPal_Ticket_Maker;
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
	public function it_should_count_total_sold_attendees_correctly() {

		$event_id          = $this->factory()->event->create();
		$tpp_id           = $this->create_paypal_ticket( $event_id, 5 );
		$this->generate_orders( $event_id, [ $tpp_id ], 10, 1, 'completed' );
		//$created_attendees = $this->create_many_attendees_for_ticket( 10, $tpp_id, $event_id );

		$Tickets__Attendance = new Tickets__Attendance( $event_id );
		$total_sold       = $Tickets__Attendance->get_total_sold();

		$this->assertEquals( 10, $total_sold );

	}

// get_total_pending()
//get_total_complete()
//get_total_cancelled()
}
