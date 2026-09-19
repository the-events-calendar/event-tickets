<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe__Tickets__RSVP as RSVP_V1;
use Tribe__Tickets__Ticket_Object;
use Tribe__Tickets__Tickets;

/**
 * `Tribe__Tickets__RSVP::get_tickets()` labels the ticket objects it builds, which is how a v1 RSVP ticket
 * (no `_type` meta of its own) comes back as an RSVP rather than as `default`. A TC-RSVP ticket already
 * carries `tc-rsvp`, and that value is what every RSVP type comparison in Event Tickets and Event Tickets
 * Plus tests against, so the label must not overwrite it.
 *
 * Overwriting it leaves callers unable to tell an RSVP v2 ticket from a v1 one, since `provider_class` on
 * these objects reports the v1 module either way.
 */
class Ticket_Type_Preservation_Test extends WPTestCase {
	use Ticket_Maker;

	private function create_post_with_rsvp_ticket(): array {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		return [ $post_id, $ticket_id ];
	}

	public function test_get_tickets_should_preserve_the_tc_rsvp_type(): void {
		[ $post_id, $ticket_id ] = $this->create_post_with_rsvp_ticket();

		$tickets = tribe( RSVP_V1::class )->get_tickets( $post_id );

		$this->assertCount( 1, $tickets );
		$this->assertInstanceOf( Tribe__Tickets__Ticket_Object::class, $tickets[0] );
		$this->assertEquals( $ticket_id, $tickets[0]->ID );
		$this->assertSame(
			Constants::TC_RSVP_TYPE,
			$tickets[0]->type(),
			'get_tickets() must not overwrite the type a TC-RSVP ticket already carries.'
		);
	}

	public function test_get_all_event_tickets_should_preserve_the_tc_rsvp_type(): void {
		[ $post_id, $ticket_id ] = $this->create_post_with_rsvp_ticket();

		$tickets = Tribe__Tickets__Tickets::get_all_event_tickets( $post_id );

		$found = null;
		foreach ( (array) $tickets as $ticket ) {
			if ( (int) $ticket->ID === (int) $ticket_id ) {
				$found = $ticket;
				break;
			}
		}

		$this->assertNotNull( $found, 'The TC-RSVP ticket should be listed for the post.' );
		$this->assertSame(
			Constants::TC_RSVP_TYPE,
			$found->type(),
			'The ticket type must survive the trip through get_all_event_tickets().'
		);
	}

	/*
	 * The other half of this behaviour, that a ticket carrying no `_type` of its own is still labelled
	 * `rsvp`, needs a real v1 ticket and so lives in the v1 context.
	 *
	 * @see \Tribe\Tickets\RSVP\Get_Tickets_Type_Test
	 */
}
