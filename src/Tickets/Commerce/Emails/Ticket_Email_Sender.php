<?php
/**
 * Sends ticket confirmation emails for Commerce orders.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Emails
 */

namespace TEC\Tickets\Commerce\Emails;

use TEC\Tickets\Commerce\BackgroundJobs\SendTicketEmail;
use WP_Post;

use function TEC\Common\StellarWP\Shepherd\shepherd;

/**
 * Class Ticket_Email_Sender.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Emails
 */
class Ticket_Email_Sender implements Order_Email_Sender_Interface {
	use Order_Type_Trait;

	/**
	 * {@inheritDoc}
	 */
	public function supports( WP_Post $order ): bool {
		return ! $this->is_rsvp_order( $order );
	}

	/**
	 * {@inheritDoc}
	 */
	public function send( WP_Post $order ): void {
		// Temporary fix for manual attendees first email.
		// @todo backend review this logic.
		if ( ! empty( $order->gateway ) && 'manual' === $order->gateway && empty( $order->events_in_order ) ) {
			$order->events_in_order[] = $order;
		}

		if ( empty( $order->events_in_order ) || ! is_array( $order->events_in_order ) ) {
			return;
		}

		foreach ( $order->events_in_order as $event_id ) {
			$event = get_post( $event_id );

			if ( ! $event instanceof WP_Post ) {
				continue;
			}

			shepherd()->dispatch( new SendTicketEmail( $order->ID, $event->ID ) );
		}
	}
}
