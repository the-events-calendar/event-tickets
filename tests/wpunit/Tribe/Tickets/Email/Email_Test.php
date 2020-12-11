<?php

namespace Tribe\Tickets\Emails;

use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class Email_Test extends \Codeception\TestCase\WPTestCase {

	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow fetching ticket attendees by event.
	 *
	 * @test
	 */
	public function should_increment_email_sent_counter() {
		$post_id = $this->factory->post->create();

		$rsvp_ticket_id    = $this->create_rsvp_ticket( $post_id );
		$rsvp_attendee_ids = $this->create_many_attendees_for_ticket( 1, $rsvp_ticket_id, $post_id, [ 'ticket_sent' => false ] );
		$attendee_id       = $rsvp_attendee_ids[0];

		/** @var \Tribe__Tickets__RSVP $rsvp */
		$rsvp = tribe( 'tickets.rsvp' );

		$ticket_sent_count = (int) get_post_meta( $attendee_id, $rsvp->attendee_ticket_sent, true );

		// Before any ticket sent this should be 0.
		$this->assertEquals( 0, $ticket_sent_count );

		// Send Tickets Email.
		$rsvp->send_tickets_email( $attendee_id, $post_id );

		$ticket_sent_count = (int) get_post_meta( $attendee_id, $rsvp->attendee_ticket_sent, true );

		// After sending email should be 1.
		$this->assertEquals( 1, $ticket_sent_count );

		// Again updating the counter.
		$rsvp->update_ticket_sent_counter( $attendee_id, $rsvp->attendee_ticket_sent );

		$ticket_sent_count = (int) get_post_meta( $attendee_id, $rsvp->attendee_ticket_sent, true );

		$this->assertEquals( 2, $ticket_sent_count );
	}

}
