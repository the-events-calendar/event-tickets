<?php

namespace Tribe\Tickets\RSVP;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe__Tickets__RSVP as RSVP;

/**
 * A v1 RSVP ticket carries no `_type` meta of its own, so `get_tickets()` is what labels it as an RSVP.
 * That labelling has to stay in place, since it is the only thing giving these tickets a type at all.
 *
 * The companion case, that a TC-RSVP ticket keeps the `tc-rsvp` type it already carries rather than being
 * relabelled, lives in the RSVP v2 suite.
 *
 * @see \TEC\Tickets\RSVP\V2\Ticket_Type_Preservation_Test
 */
class Get_Tickets_Type_Test extends WPTestCase {
	use Ticket_Maker;

	public function test_a_ticket_without_a_type_of_its_own_is_labelled_rsvp(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$this->assertEmpty(
			get_post_meta( $ticket_id, '_type', true ),
			'This test is only meaningful for a ticket that carries no type of its own.'
		);

		$tickets = tribe( RSVP::class )->get_tickets( $event_id );

		$this->assertCount( 1, $tickets );
		$this->assertEquals( $ticket_id, $tickets[0]->ID );
		$this->assertSame( 'rsvp', $tickets[0]->type() );
	}
}
