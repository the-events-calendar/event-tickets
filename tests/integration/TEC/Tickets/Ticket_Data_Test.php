<?php

namespace TEC\Tickets;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

class Ticket_Data_Test extends WPTestCase {
	use Ticket_Maker;
	use RSVP_Ticket_Maker;
	use Attendee_Maker;
	use With_Tickets_Commerce;

	/**
	 * @test
	 */
	public function it_should_load_a_ticket_of_any_provider_by_id(): void {
		$post_id   = static::factory()->post->create();
		$tc_ticket = $this->create_tc_ticket( $post_id, 10 );
		$rsvp      = $this->create_rsvp_ticket( $post_id );

		$loaded_tc   = tribe( Ticket_Data::class )->load_ticket_object( $tc_ticket );
		$loaded_rsvp = tribe( Ticket_Data::class )->load_ticket_object( $rsvp );

		$this->assertInstanceOf( Ticket_Object::class, $loaded_tc );
		$this->assertSame( $tc_ticket, $loaded_tc->ID );
		$this->assertInstanceOf( Ticket_Object::class, $loaded_rsvp );
		$this->assertSame( $rsvp, $loaded_rsvp->ID );
	}

	/**
	 * @test
	 */
	public function it_should_not_load_an_attendee_or_a_plain_post_as_a_ticket(): void {
		$post_id          = static::factory()->post->create();
		$rsvp             = $this->create_rsvp_ticket( $post_id );
		$rsvp_attendee_id = $this->create_attendee_for_ticket( $rsvp, $post_id );
		$tc_ticket        = $this->create_tc_ticket( $post_id, 10 );
		$tc_attendee_id   = $this->create_attendee_for_ticket( $tc_ticket, $post_id );

		$ticket_data = tribe( Ticket_Data::class );

		$this->assertNull( $ticket_data->load_ticket_object( $rsvp_attendee_id ), 'An RSVP attendee resolves to its event but is not a ticket.' );
		$this->assertNull( $ticket_data->load_ticket_object( $tc_attendee_id ) );
		$this->assertNull( $ticket_data->load_ticket_object( $post_id ) );
		$this->assertNull( $ticket_data->load_ticket_object( 999999999 ) );
	}
}
