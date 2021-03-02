<?php

use Tribe__Tickets__RSVP as RSVP;

class RSVP_Tickets_Test extends \Codeception\TestCase\WPTestCase {
	/**
	 * When a ticket has a stock value, make sure the object returns expected value druing the life of
	 * the in-stock values
	 */
	public function test_ticket_with_stock() {
		add_filter( 'tribe_tickets_ticket_object_is_ticket_cache_enabled', '__return_false' );

		$post_id = $this->factory->post->create();
		$start = strtotime( date( 'Y-m-d H:00:00' ) );
		$end = strtotime( date( 'Y-m-d H:00:00', strtotime( '+5 days' ) ) );

		$capacity = 10;
		$data = $this->generate_ticket_data( $start, $end, $capacity );

		$rsvp = RSVP::get_instance();
		$rsvp->ticket_add( $post_id, $data );
		$tickets = $rsvp->get_event_tickets( $post_id );

		$this->assertCount( 1, $tickets, 'RSVP ticket has been created' );

		/** @var \Tribe__Tickets__Ticket_Object $ticket */
		$ticket = $tickets[0];

		$this->assertTrue( $ticket->managing_stock(), 'RSVP ticket is managing stock' );
		$this->assertEquals( 10, $ticket->original_stock(), 'RSVP ticket has the appropriate stock amount' );
		$this->assertEquals( 10, $ticket->remaining(), 'RSVP ticket has the appropriate remaining stock amount' );
		$this->assertTrue( $ticket->is_in_stock(), 'RSVP ticket is in stock' );

		// sell one ticket
		$rsvp->generate_tickets_for( $ticket->ID, 1, $this->fake_attendee_details() );

		$tickets = $rsvp->get_event_tickets( $post_id );
		/** @var \Tribe__Tickets__Ticket_Object $ticket */
		$ticket = $tickets[0];
		$this->assertEquals( 10, $ticket->capacity(), 'RSVP ticket has the appropriate stock amount after selling 1 ticket' );
		$this->assertEquals( 9, $ticket->inventory(), 'RSVP ticket has the appropriate inventory() stock amount after selling 1 ticket' );
		$this->assertTrue( $ticket->is_in_stock(), 'RSVP ticket is in stock after selling 1 ticket' );

		// sell 9 more tickets
		$rsvp->generate_tickets_for( $ticket->ID, 9, $this->fake_attendee_details() );

		$tickets = $rsvp->get_event_tickets( $post_id );
		/** @var \Tribe__Tickets__Ticket_Object $ticket */
		$ticket = $tickets[0];
		$this->assertEquals( 10, $ticket->capacity(), 'RSVP ticket has the appropriate stock amount after selling 10 tickets' );
		$this->assertEquals( 0, $ticket->inventory(), 'RSVP ticket has the appropriate inventory() stock amount after selling 10 tickets' );
		$this->assertFalse( $ticket->is_in_stock(), 'RSVP ticket is not in stock after selling 10 tickets' );

		// switch to stock-less tickets
		delete_post_meta( $ticket->ID, '_manage_stock' );
		$tickets = $rsvp->get_event_tickets( $post_id );
		/** @var \Tribe__Tickets__Ticket_Object $ticket */
		$ticket = $tickets[0];
		$this->assertFalse( $ticket->managing_stock(), 'RSVP ticket is not managing stock' );
		$this->assertEquals( '', $ticket->capacity(), 'RSVP ticket is not managing stock, so original stock should be blank' );
		$this->assertEquals( -1, $ticket->inventory(), 'RSVP ticket has the appropriate inventory() stock amount' );
		$this->assertTrue( $ticket->is_in_stock(), 'RSVP ticket that is not managing stock should be report that it is in stock' );
	}

	/**
	 * When a ticket does not have a stock value, make sure the object returns expected value druing the life of
	 * the in-stock values
	 */
	public function test_ticket_without_stock() {
		add_filter( 'tribe_tickets_ticket_object_is_ticket_cache_enabled', '__return_false' );

		$start = strtotime( date( 'Y-m-d H:00:00' ) );
		$end = strtotime( date( 'Y-m-d H:00:00', strtotime( '+5 days' ) ) );
		$post_id = $this->factory->post->create();

		$data = $this->generate_ticket_data( $start, $end, '' );

		$rsvp = RSVP::get_instance();
		$rsvp->ticket_add( $post_id, $data );
		$tickets = $rsvp->get_event_tickets( $post_id );

		$this->assertArrayHasKey( 0, $tickets, 'RSVP ticket has been created' );

		/** @var \Tribe__Tickets__Ticket_Object $ticket */
		$ticket = $tickets[0];

		$this->assertFalse( $ticket->managing_stock(), 'RSVP ticket is not managing stock' );
		$this->assertEquals( '', $ticket->capacity(), 'RSVP ticket is not managing stock, so original stock should be blank' );
		$this->assertEquals( -1, $ticket->inventory(), 'RSVP ticket has the appropriate inventory() stock amount' );
		$this->assertTrue( $ticket->is_in_stock(), 'RSVP ticket that is not managing stock should be report that it is in stock' );

		// sell one ticket
		$rsvp->generate_tickets_for( $ticket->ID, 1, $this->fake_attendee_details() );

		$tickets = $rsvp->get_event_tickets( $post_id );
		/** @var \Tribe__Tickets__Ticket_Object $ticket */
		$ticket = $tickets[0];
		$this->assertEquals( '', $ticket->capacity(), 'RSVP ticket has the appropriate stock amount after selling 1 ticket' );
		$this->assertEquals( -1, $ticket->inventory(), 'RSVP ticket has the appropriate inventory() stock amount after selling 1 ticket' );
		$this->assertTrue( $ticket->is_in_stock(), 'RSVP ticket is in stock after selling 1 ticket' );

		// sell 9 more tickets
		$rsvp->generate_tickets_for( $ticket->ID, 9, $this->fake_attendee_details() );

		$tickets = $rsvp->get_event_tickets( $post_id );
		/** @var \Tribe__Tickets__Ticket_Object $ticket */
		$ticket = $tickets[0];
		$this->assertEquals( '', $ticket->capacity(), 'RSVP ticket has the appropriate stock amount after selling 100000 tickets' );
		$this->assertEquals( -1, $ticket->inventory(), 'RSVP ticket has the appropriate inventory() stock amount after selling 100000 tickets' );
		$this->assertTrue( $ticket->is_in_stock(), 'RSVP ticket is in stock after selling 100000 tickets' );

		// switch to stock-tracking tickets
		$data['tribe-ticket'] = [
			'capacity' => 100,
		];
		$rsvp->save_ticket( $post_id, $ticket, $data );
		$tickets = $rsvp->get_event_tickets( $post_id );
		/** @var \Tribe__Tickets__Ticket_Object $ticket */
		$ticket = $tickets[0];
		$this->assertTrue( $ticket->managing_stock(), 'RSVP ticket is now managing stock' );
		$this->assertEquals( 100, $ticket->capacity(), 'RSVP ticket is now managing stock, so original stock should be blank' );
		$this->assertEquals( 90, $ticket->inventory(), 'RSVP ticket has the appropriate inventory() stock amount' );
		$this->assertTrue( $ticket->is_in_stock(), 'RSVP ticket that is now managing stock should be report that it is in stock' );
	}

	protected function fake_attendee_details(array $overrides = array()) {
		return array_merge( array(
			'full_name'    => 'Jane Doe',
			'email'        => 'jane@doe.com',
			'order_status' => 'yes',
			'optout'       => 'no',
			'order_id'     => RSVP::generate_order_id(),
		), $overrides );
	}

	protected function generate_ticket_data( $start, $end, $capacity ) {
		$data = [
			'ticket_provider'     => 'RSVP',
			'ticket_name'         => __METHOD__,
			'ticket_description'  => __CLASS__ . '::' . __METHOD__,
			'ticket_start_date'   => date( 'Y-m-d', $start ),
			'ticket_start_hour'   => date( 'H', $start ),
			'ticket_start_minute' => date( 'i', $start ),
			'ticket_end_date'     => date( 'Y-m-d', $end ),
			'ticket_end_hour'     => date( 'H', $end ),
			'ticket_end_minute'   => date( 'i', $end ),
			'tribe-ticket'        => [
				'capacity' => $capacity,
			],
		];

		return $data;
	}
}
