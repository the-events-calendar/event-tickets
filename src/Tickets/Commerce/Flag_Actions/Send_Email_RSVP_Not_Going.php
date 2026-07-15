<?php
/**
 * Sends the RSVP "Not Going" confirmation email for RSVP V2 orders.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Emails\Email\RSVP_Not_Going;
use TEC\Tickets\RSVP\V2\Traits\Not_Going_Order;

/**
 * Class Send_Email_RSVP_Not_Going, sends the RSVP "Not Going" confirmation email for
 * RSVP V2 orders where the attendee indicated they will not be attending.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Send_Email_RSVP_Not_Going extends Flag_Action_Abstract {
	use Not_Going_Order;

	/**
	 * {@inheritDoc}
	 *
	 * @var array
	 */
	protected $flags = [
		'send_email_completed_order',
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
		// Bail if tickets emails is not enabled.
		if ( ! tec_tickets_emails_is_enabled() ) {
			return;
		}

		if ( ! $this->is_rsvp_v2_not_going_order( $order ) ) {
			return;
		}

		if ( empty( $order->events_in_order ) || ! is_array( $order->events_in_order ) ) {
			return;
		}

		$provider  = tribe( $order->provider );
		$attendees = $provider->get_attendees_by_order_id( $order->ID );

		if ( empty( $attendees ) ) {
			return;
		}

		$to = $attendees[0]['holder_email'] ?? '';

		if ( ! is_email( $to ) ) {
			return;
		}

		$event_id = reset( $order->events_in_order );

		$email_class = tribe( RSVP_Not_Going::class );
		$email_class->set( 'post_id', $event_id );
		$email_class->set( 'tickets', $attendees );
		$email_class->recipient = $to;

		$email_class->send();
	}
}
