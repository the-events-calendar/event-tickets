<?php

namespace TEC\Tickets\Commerce\Stock;

use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class UpdateTest extends \Codeception\TestCase\WPTestCase {

	use Ticket_Maker;
	use Order_Maker;
	use RSVP_Ticket_Maker;

	/**
	 * @inheritDoc
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		add_filter( 'tribe_tickets_ticket_object_is_ticket_cache_enabled', '__return_false' );
	}

	public function test_ticket_restock_after_attendee_deletion_with_individual_capacity() {
		$maker = new Event();
		$event_id = $maker->create();

		// create ticket with default capacity of 100.
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10 );
		// get ticket.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$this->assertEquals( 100, $ticket->available(), 'There should be 100 tickets available' );

		// create order.
		$order = $this->create_order( [ $ticket_a_id => 2 ] );

		//get attendees.
		$attendees = tribe_attendees()->where( 'event_id', $event_id )->all();

		$this->assertEquals( 2, count( $attendees ), 'There should be 2 attendees' );

		// delete attendee.
		$attendee = $attendees[0];
		$deleted = tribe( Attendee::class )->delete( $attendee->ID );

		$new_count = tec_tc_attendees()->by( 'event_id', $event_id )->count();
		$this->assertEquals( 1, $new_count, 'There should be 1 attendee' );

		// get ticket.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$this->assertEquals( 99, $ticket->available(), 'There should be 99 tickets available' );
	}
}