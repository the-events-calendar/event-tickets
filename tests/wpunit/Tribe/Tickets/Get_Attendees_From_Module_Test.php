<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__RSVP;

class Get_Attendees_From_Module_Test extends \Codeception\TestCase\WPTestCase {

	use RSVP_Ticket_Maker;
	use Attendee_Maker;

	public function setUp() {
		parent::setUp();

		$this->factory()->event = new Event();
	}

	/**
	 * @test
	 */
	public function it_should_return_attendee_data_when_given_an_array() {
		[ $module, $event_id, $ticket_id, $count ] = $this->given_an_event_with_attendees();

		$attendees = $module->get_attendees_from_module( tribe_attendees( $module->orm_provider )->by( 'ticket', $ticket_id )->all() );

		$this->assertCount( $count, $attendees );
	}

	/**
	 * A generator can only be traversed once, so every internal pass over the argument has to read
	 * from the same materialised list.
	 *
	 * @test
	 */
	public function it_should_return_attendee_data_when_given_a_generator() {
		[ $module, $event_id, $ticket_id, $count ] = $this->given_an_event_with_attendees();

		$generator = tribe_attendees( $module->orm_provider )->by( 'ticket', $ticket_id )->all( true );

		$this->assertInstanceOf( \Generator::class, $generator, 'The ORM should hand back a generator for this test to be meaningful.' );

		$attendees = $module->get_attendees_from_module( $generator );

		$this->assertCount( $count, $attendees );
	}

	/**
	 * @test
	 */
	public function it_should_return_an_empty_array_for_an_empty_generator() {
		[ $module, $event_id ] = $this->given_an_event_with_attendees();

		$attendees = $module->get_attendees_from_module( tribe_attendees( $module->orm_provider )->by( 'ticket', $event_id )->all( true ), $event_id );

		$this->assertSame( [], $attendees );
	}

	/**
	 * @return array{0: Tribe__Tickets__RSVP, 1: int, 2: int, 3: int}
	 */
	private function given_an_event_with_attendees(): array {
		$count     = 3;
		$event_id  = $this->factory()->event->create();
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$this->create_many_attendees_for_ticket( $count, $ticket_id, $event_id );

		return [ tribe( 'tickets.rsvp' ), $event_id, $ticket_id, $count ];
	}
}
