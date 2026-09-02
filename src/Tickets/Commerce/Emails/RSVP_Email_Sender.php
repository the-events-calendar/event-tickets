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
	 *
	 * @since TBD Removed the `tec_tickets_emails_is_enabled()` check: `Order_Email_Sender_Registry::send()`
	 *            now checks it once for every sender before dispatching.
	 */
	public function send( WP_Post $order ): void {
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

		// Group tickets by holder email for per-attendee fan-out — mirrors
		// Tribe__Tickets__Tickets::send_tickets_email_for_attendees().
		$unique = [];

		foreach ( $attendees as $attendee ) {
			if ( ! is_array( $attendee ) ) {
				continue;
			}

			$holder_email = $attendee['holder_email'] ?? '';

			if ( ! is_string( $holder_email ) ) {
				continue;
			}

			$holder_email = strtolower( trim( $holder_email ) );

			if ( '' === $holder_email || ! is_email( $holder_email ) ) {
				continue;
			}

			if ( ! isset( $unique[ $holder_email ] ) ) {
				$unique[ $holder_email ] = [];
			}

			$unique[ $holder_email ][] = $attendee;
		}

              // The Main Guest receives every ticket in the order, but neither obvious source identifies
              // them: the order purchaser holds the WP account address for a logged-in user, and attendee
              // rows come back sorted by guest name rather than creation order. Attendee IDs do follow
              // creation order, and the Main Guest is created first.
              usort( $ordered, static fn( $a, $b ) => ( (int) ( $a['attendee_id'] ?? 0 ) ) <=> ( (int) (
$b['attendee_id'] ?? 0 ) ) );
              $main_guest_email = strtolower( trim( (string) ( $ordered[0]['holder_email'] ?? '' ) ) );

		if ( ! is_email( $main_guest_email ) ) {
			$main_guest_email = strtolower( trim( (string) ( $order->purchaser['email'] ?? '' ) ) );
		}

		if ( is_email( $main_guest_email ) ) {
			$unique[ $main_guest_email ] = $attendees;
		}

		foreach ( $unique as $recipient => $tickets ) {
			$this->send_rsvp_email( $tickets, (int) $event_id, $recipient, $going );
		}
	}

	/**
	 * Sends the "Going" or "Not Going" RSVP email for a set of attendees.
	 *
	 * Split out of `send()` so callers outside the order status-transition cascade — such as an
	 * attendee flipping their response on the My Tickets page — reach the same email, rather than
	 * having to rebuild it and drift from this one.
	 *
	 * @since TBD
	 *
	 * @param array<int,array<string,mixed>> $attendees The attendees to include in the email.
	 * @param int                            $event_id  The event the RSVP belongs to.
	 * @param string                         $recipient The email address to send to.
	 * @param bool                           $going     Whether the response is "Going" or "Not Going".
	 *
	 * @return bool Whether the email was sent.
	 */
	public function send_rsvp_email( array $attendees, int $event_id, string $recipient, bool $going ): bool {
		if ( empty( $attendees ) || empty( $event_id ) || empty( $recipient ) ) {
			return false;
		}

		// Callers outside `Order_Email_Sender_Registry::send()` do not pass through its check.
		if ( ! tec_tickets_emails_is_enabled() ) {
			return false;
		}

		$email_class = $going
			? tribe( RSVP_Email::class )
			: tribe( RSVP_Not_Going::class );

		if ( ! $email_class->is_enabled() ) {
			return false;
		}

		$email_class->set( 'post_id', $event_id );
		$email_class->set( 'tickets', $attendees );
		$email_class->recipient = $recipient;

		return (bool) $email_class->send();
	}
}
