<?php

namespace TEC\Tickets\Commerce;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Attendee_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;

	/**
	 * Updating an attendee from the RSVP reservation page after the RSVP to Tickets
	 * Commerce migration should not fatal while trying to resolve the Commerce Email class.
	 *
	 * @since TBD
	 */
	public function test_maybe_send_tickets_after_status_change_does_not_fatal() {
		$post      = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $post, 10 );
		$order     = $this->create_order( [ $ticket_id => 1 ] );

		$this->assertNotFalse( $order );

		// Simulate a migrated RSVP attendee: the RSVP to Tickets Commerce migration
		// sets the order relation meta on the attendee. Mark attendees as already
		// having received their tickets so no email is dispatched.
		foreach ( tribe( Module::class )->get_event_attendees( $post ) as $attendee ) {
			update_post_meta( $attendee['attendee_id'], Attendee::$order_relation_meta_key, $order->ID );
			update_post_meta( $attendee['attendee_id'], Attendee::$ticket_sent_meta_key, 1 );
		}

		// This used to throw a Not_Bound_Exception for the Tickets Commerce Email class.
		tribe( Attendee::class )->maybe_send_tickets_after_status_change( $post );

		$this->addToAssertionCount( 1 );
	}
}