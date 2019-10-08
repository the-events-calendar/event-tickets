<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Testcases\Ticket_Object_TestCase;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Ticket_Object as RSVP;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

class Ticket_ObjectTest extends Ticket_Object_TestCase {

	protected $timezone = 'Australia/Melbourne';

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Ticket_Object::class, $sut, 'Object not instantiatable.' );
	}

	/**
	 * @test
	 * it should return the correct event ID
	 */
	public function it_should_return_the_correct_ID() {
		$event_id = $this->make_event();
		$rsvp_id  = $this->create_rsvp_ticket( $event_id, [] );
		$rsvp     = $this->get_ticket( $event_id, $rsvp_id );

		$this->assertEquals( $event_id, $rsvp->get_event_id(), 'Incorrect event ID reported for RSVP.' );
	}

	/**
	 * @test
	 * it should return the original stock/capacity
	 *
	 * @covers original_stock
	 */
	public function it_should_return_the_original_stock() {
		$rsvp = $this->make_rsvp( [
				'meta_input' => [
					'_capacity'   => 10,
					'_stock'      => 5,
					'total_sales' => 5,
				],
			] );

		$this->assertEquals( 10, $rsvp->original_stock() );

		$ticket = $this->make_ticket( 1, [
				'meta_input' => [
					'_capacity'   => 10,
					'_stock'      => 5,
					'total_sales' => 5,
				],
			] );

		$this->assertEquals( 10, $ticket->original_stock() );
	}

	/**
	 * @test
	 * it should return correct current inventory
	 * Note: inventory is based on attendees! So we can't just manually set it (as stock)
	 *
	 * @covers inventory
	 */
	public function it_should_return_correct_current_inventory() {
		$rsvp = $this->make_rsvp( [
				'meta_input' => [
					'_capacity' => 10,
				],
			] );

		$this->create_many_attendees_for_ticket( 5, $rsvp->ID, $rsvp->get_event_id() );

		$this->assertEquals( 5, $rsvp->inventory(), 'Incorrect inventory reported for RSVP.' );

		$ticket = $this->make_ticket( 1, [
				'meta_input' => [
					'_capacity' => 10,
				],
			] );

		$this->create_many_attendees_for_ticket( 5, $ticket->ID, $ticket->get_event_id() );

		$this->assertEquals( 5, $ticket->inventory(), 'Incorrect inventory reported for Ticket.' );
	}

	/**
	 * @test
	 * it should return correct "own" capacity
	 *
	 * @covers capacity
	 */
	public function it_should_return_correct_own_capacity() {
		$rsvp = $this->make_rsvp( [
				'meta_input' => [
					'_capacity' => 10,
				],
			] );

		$this->assertEquals( 10, $rsvp->capacity(), 'Incorrect capacity reported for new RSVP.' );

		$this->create_many_attendees_for_ticket( 5, $rsvp->ID, $rsvp->get_event_id() );

		$this->assertEquals( 10, $rsvp->capacity(), 'Incorrect capacity reported for RSVP with attendees.' );

		$ticket = $this->make_ticket( 1, [
				'meta_input' => [
					'_capacity' => 10,
				],
			] );

		$this->assertEquals( 10, $ticket->capacity(), 'Incorrect capacity reported for new ticket.' );

		$this->create_many_attendees_for_ticket( 5, $ticket->ID, $ticket->get_event_id() );

		$this->assertEquals( 10, $ticket->capacity(), 'Incorrect capacity reported for ticket with attendees.' );
	}

	/**
	 * @test
	 * it should return correct "unlimited" capacity
	 *
	 * @covers capacity
	 */
	public function it_should_return_correct_unlimited_capacity() {
		$rsvp = $this->make_rsvp( [
				'meta_input' => [
					'_capacity' => - 1,
				],
			] );

		$this->assertEquals( - 1, $rsvp->capacity(), 'Incorrect capacity reported for new unlimited capacity RSVP.' );

		$this->create_many_attendees_for_ticket( 5, $rsvp->ID, $rsvp->get_event_id() );

		$this->assertEquals( - 1, $rsvp->capacity(), 'Incorrect capacity reported for unlimited capacity  RSVP with attendees.' );

		$ticket = $this->make_ticket( 1, [
				'meta_input' => [
					'_capacity' => - 1,
				],
			] );

		$this->assertEquals( - 1, $ticket->capacity(), 'Incorrect capacity reported for new unlimited capacity ticket.' );

		$this->create_many_attendees_for_ticket( 5, $ticket->ID, $ticket->get_event_id() );

		$this->assertEquals( - 1, $ticket->capacity(), 'Incorrect capacity reported for unlimited capacity ticket with attendees.' );
	}
}
