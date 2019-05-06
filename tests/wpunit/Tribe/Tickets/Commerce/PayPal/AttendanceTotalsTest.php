<?php

namespace Tribe\Tickets\Commerce\PayPal;

use Tribe\Events\Test\Factories\Event;
use Tribe__Tickets__Status__Manager as Manager;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Order_Maker as PayPal_Order_Maker;
use Tribe__Tickets__Commerce__PayPal__Attendance_Totals as Tickets__Attendance;

/**
 * Test Status TTPManager
 *
 * @group   core
 *
 * @package Tribe__Tickets__Commerce__PayPal__Status_Manager
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

	public function dont_die() {
		// no-op, go on
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	/**
	 * Use to initialize TPP with status manager to prevent undefined error
	 */
	private function set_ttp_with_status_manager() {
		tribe_update_option( 'ticket-paypal-enable', true );

		Manager::get_instance()->setup();
	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_should_count_total_sold_attendees_correctly() {

		$this->set_ttp_with_status_manager();
		$event_id  = $this->factory()->event->create();
		$ticket_id = $this->create_paypal_ticket( $event_id, 4, [
			'meta_input' => [
				'_stock'    => 50,
				'_capacity' => 50,
			]
		] );

		$this->create_paypal_orders( $event_id, $ticket_id, 5, 4, 'completed' );
		$Tickets__Attendance = new Tickets__Attendance( $event_id );
		$total       = $Tickets__Attendance->get_total_sold();

		$this->assertEquals( 20, $total );

	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_should_count_total_pending_attendees_correctly() {

		$this->set_ttp_with_status_manager();
		$event_id  = $this->factory()->event->create();
		$ticket_id = $this->create_paypal_ticket( $event_id, 5, [
			'meta_input' => [
				'_stock'    => 50,
				'_capacity' => 50,
			]
		] );
		$this->create_paypal_orders( $event_id, $ticket_id, 1, 1, 'pending-payment' );
		$Tickets__Attendance = new Tickets__Attendance( $event_id );
		$total       = $Tickets__Attendance->get_total_pending();

		$this->assertEquals( 1, $total );

	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_should_count_total_complete_attendees_correctly() {

		$this->set_ttp_with_status_manager();
		$event_id  = $this->factory()->event->create();
		$ticket_id = $this->create_paypal_ticket( $event_id, 2, [
			'meta_input' => [
				'_stock'    => 50,
				'_capacity' => 50,
			]
		] );

		$this->create_paypal_orders( $event_id, $ticket_id, 2, 10, 'completed' );
		$Tickets__Attendance = new Tickets__Attendance( $event_id );
		$total       = $Tickets__Attendance->get_total_complete();

		$this->assertEquals( 20, $total );

	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_should_count_total_cancelled_attendees_correctly() {

		$this->set_ttp_with_status_manager();
		$event_id  = $this->factory()->event->create();
		$ticket_id = $this->create_paypal_ticket( $event_id, 10, [
			'meta_input' => [
				'_stock'    => 50,
				'_capacity' => 50,
			]
		] );

		$this->create_paypal_orders( $event_id, $ticket_id, 3, 2, 'denied' );
		$Tickets__Attendance = new Tickets__Attendance( $event_id );
		$total       = $Tickets__Attendance->get_total_cancelled();

		$this->assertEquals( 6, $total );

	}

}
