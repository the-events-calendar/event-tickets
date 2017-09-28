<?php

class RSVP_Tickets_Test extends Tribe__Tickets__WP_UnitTestCase {
	/**
	 * When a ticket has a stock value, make sure the object returns expected value druing the life of
	 * the in-stock values
	 */
	public function test_ticket_with_stock() {
		$start = strtotime( date( 'Y-m-d H:00:00' ) );
		$end = strtotime( date( 'Y-m-d H:00:00', strtotime( '+5 days' ) ) );

		$data = [
			'ticket_provider' => 'Tribe__Tickets__RSVP',
			'ticket_name' => __METHOD__,
			'ticket_description' => __CLASS__ . '::' . __METHOD__,
			'ticket_start_date' => date( 'Y-m-d', $start ),
			'ticket_start_hour' => date( 'H', $start ),
			'ticket_start_minute' => date( 'i', $start ),
			'ticket_end_date' => date( 'Y-m-d', $end ),
			'ticket_end_hour' => date( 'H', $end ),
			'ticket_end_minute' => date( 'i', $end ),
			'ticket_rsvp_stock' => 10,
		];

		$rsvp = Tribe__Tickets__RSVP::get_instance();
		$rsvp->ticket_add( 1, $data );
		$tickets = $rsvp->get_event_tickets( 1 );

		$this->assertArrayHasKey( 0, $tickets, 'RSVP ticket has been created' );

		$ticket = $tickets[0];

		$this->assertTrue( $ticket->managing_stock(), 'RSVP ticket is managing stock' );
		$this->assertEquals( 10, $ticket->original_stock(), 'RSVP ticket has the appropriate stock amount' );
		$this->assertEquals( 10, $ticket->remaining(), 'RSVP ticket has the appropriate remaining stock amount' );
		$this->assertTrue( $ticket->is_in_stock(), 'RSVP ticket is in stock' );

		// sell one ticket
		update_post_meta( $ticket->ID, 'total_sales', 1 );

		$tickets = $rsvp->get_event_tickets( 1 );
		$ticket = $tickets[0];
		$this->assertEquals( 10, $ticket->original_stock(), 'RSVP ticket has the appropriate stock amount after selling 1 ticket' );
		$this->assertEquals( 9, $ticket->remaining(), 'RSVP ticket has the appropriate remaining stock amount after selling 1 ticket' );
		$this->assertTrue( $ticket->is_in_stock(), 'RSVP ticket is in stock after selling 1 ticket' );

		// sell 10 tickets
		update_post_meta( $ticket->ID, 'total_sales', 10 );

		$tickets = $rsvp->get_event_tickets( 1 );
		$ticket = $tickets[0];
		$this->assertEquals( 10, $ticket->original_stock(), 'RSVP ticket has the appropriate stock amount after selling 10 tickets' );
		$this->assertEquals( 0, $ticket->remaining(), 'RSVP ticket has the appropriate remaining stock amount after selling 10 tickets' );
		$this->assertFalse( $ticket->is_in_stock(), 'RSVP ticket is not in stock after selling 10 tickets' );

		// switch to stock-less tickets
		$data['ticket_rsvp_stock'] = '';
		$rsvp->save_ticket( 1, $ticket, $data );
		$tickets = $rsvp->get_event_tickets( 1 );
		$ticket = $tickets[0];
		$this->assertFalse( $ticket->managing_stock(), 'RSVP ticket is not managing stock' );
		$this->assertEquals( '', $ticket->original_stock(), 'RSVP ticket is not managing stock, so original stock should be blank' );
		$this->assertEquals( 0, $ticket->remaining(), 'RSVP ticket has the appropriate remaining stock amount' );
		$this->assertTrue( $ticket->is_in_stock(), 'RSVP ticket that is not managing stock should be report that it is in stock' );
	}

	/**
	 * When a ticket does not have a stock value, make sure the object returns expected value druing the life of
	 * the in-stock values
	 */
	public function test_ticket_without_stock() {
		$start = strtotime( date( 'Y-m-d H:00:00' ) );
		$end = strtotime( date( 'Y-m-d H:00:00', strtotime( '+5 days' ) ) );

		$data = [
			'ticket_provider' => 'Tribe__Tickets__RSVP',
			'ticket_name' => __METHOD__,
			'ticket_description' => __CLASS__ . '::' . __METHOD__,
			'ticket_start_date' => date( 'Y-m-d', $start ),
			'ticket_start_hour' => date( 'H', $start ),
			'ticket_start_minute' => date( 'i', $start ),
			'ticket_end_date' => date( 'Y-m-d', $end ),
			'ticket_end_hour' => date( 'H', $end ),
			'ticket_end_minute' => date( 'i', $end ),
			'ticket_rsvp_stock' => '',
		];

		$rsvp = Tribe__Tickets__RSVP::get_instance();
		$rsvp->ticket_add( 1, $data );
		$tickets = $rsvp->get_event_tickets( 1 );

		$this->assertArrayHasKey( 0, $tickets, 'RSVP ticket has been created' );

		$ticket = $tickets[0];

		$this->assertFalse( $ticket->managing_stock(), 'RSVP ticket is not managing stock' );
		$this->assertEquals( '', $ticket->original_stock(), 'RSVP ticket is not managing stock, so original stock should be blank' );
		$this->assertEquals( 0, $ticket->remaining(), 'RSVP ticket has the appropriate remaining stock amount' );
		$this->assertTrue( $ticket->is_in_stock(), 'RSVP ticket that is not managing stock should be report that it is in stock' );

		// sell one ticket
		update_post_meta( $ticket->ID, 'total_sales', 1 );

		$tickets = $rsvp->get_event_tickets( 1 );
		$ticket = $tickets[0];
		$this->assertEquals( '', $ticket->original_stock(), 'RSVP ticket has the appropriate stock amount after selling 1 ticket' );
		$this->assertEquals( 0, $ticket->remaining(), 'RSVP ticket has the appropriate remaining stock amount after selling 1 ticket' );
		$this->assertTrue( $ticket->is_in_stock(), 'RSVP ticket is in stock after selling 1 ticket' );

		// sell 100000 tickets
		update_post_meta( $ticket->ID, 'total_sales', 100000 );

		$tickets = $rsvp->get_event_tickets( 1 );
		$ticket = $tickets[0];
		$this->assertEquals( '', $ticket->original_stock(), 'RSVP ticket has the appropriate stock amount after selling 100000 tickets' );
		$this->assertEquals( 0, $ticket->remaining(), 'RSVP ticket has the appropriate remaining stock amount after selling 100000 tickets' );
		$this->assertTrue( $ticket->is_in_stock(), 'RSVP ticket is in stock after selling 100000 tickets' );

		// switch to stock-tracking tickets
		$data['ticket_rsvp_stock'] = 100100;
		$rsvp->save_ticket( 1, $ticket, $data );
		$tickets = $rsvp->get_event_tickets( 1 );
		$ticket = $tickets[0];
		$this->assertTrue( $ticket->managing_stock(), 'RSVP ticket is now managing stock' );
		$this->assertEquals( 100100, $ticket->original_stock(), 'RSVP ticket is now managing stock, so original stock should be blank' );
		$this->assertEquals( 100, $ticket->remaining(), 'RSVP ticket has the appropriate remaining stock amount' );
		$this->assertTrue( $ticket->is_in_stock(), 'RSVP ticket that is now managing stock should be report that it is in stock' );
	}
}
