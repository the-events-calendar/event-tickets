<?php
/**
 * File: Send_Email_RSVP.php
 * Sends the RSVP-specific confirmation email for TC-RSVP orders.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Emails\Email\RSVP as RSVP_Email;
use TEC\Tickets\Emails\Email\RSVP_Not_Going;

/**
 * Class Send_Email_RSVP, sends the RSVP-specific confirmation email for TC-RSVP orders.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Send_Email_RSVP extends Flag_Action_Abstract {
	/**
	 * {@inheritDoc}
	 *
	 * @var array
	 */
	protected $flags = [
		'send_email',
	];

	/**
	 * {@inheritDoc}
	 *
	 * @var array
	 */
	protected $post_types = [
		Order::POSTTYPE,
	];

	/**
	 * {@inheritDoc}
	 */
	public function handle( Status_Interface $new_status, $old_status, \WP_Post $order ) {
		// Only handle TC-RSVP orders; regular purchases are handled by Send_Email.
		if ( ! $this->is_rsvp_order( $order ) ) {
			return;
		}

		// Bail if tickets emails is not enabled.
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

		return $email_class->send();
	}
}
