<?php
/**
 * Sends RSVP confirmation emails for TC-RSVP orders.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Emails
 */

namespace TEC\Tickets\Commerce\Emails;

use TEC\Tickets\Emails\Email\RSVP as RSVP_Email;
use TEC\Tickets\Emails\Email\RSVP_Not_Going;
use WP_Post;

/**
 * Class RSVP_Email_Sender.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Emails
 */
class RSVP_Email_Sender implements Order_Email_Sender_Interface {
	use Order_Type_Trait;

	/**
	 * {@inheritDoc}
	 */
	public function supports( WP_Post $order ): bool {
		return $this->is_rsvp_order( $order );
	}

	/**
	 * {@inheritDoc}
	 */
	public function send( WP_Post $order ): void {
		if ( ! tec_tickets_emails_is_enabled() ) {
			return;
		}

		$provider  = tribe( $order->provider );
		$attendees = $provider->get_attendees_by_order_id( $order->ID );

		if ( empty( $attendees ) ) {
			return;
		}

		$event_id = $order->events_in_order[0] ?? $attendees[0]['event_id'] ?? 0;

		if ( empty( $event_id ) ) {
			return;
		}

		// Read the going/not-going status directly off the order item: the attendee-level RSVP
		// status meta is stamped by Order_Endpoint AFTER the order reaches Completed, so it is
		// not yet reliable at this point in the same status-transition cascade.
		$order_status = $order->items[0]['extra']['order_status'] ?? 'yes';
		$going        = tribe_is_truthy( $order_status );

		$email_class = $going
			? tribe( RSVP_Email::class )
			: tribe( RSVP_Not_Going::class );

		if ( ! $email_class->is_enabled() ) {
			return;
		}

		$email_class->set( 'post_id', $event_id );
		$email_class->set( 'tickets', $attendees );
		$email_class->recipient = $order->purchaser['email'] ?? $attendees[0]['holder_email'];

		$email_class->send();
	}
}
