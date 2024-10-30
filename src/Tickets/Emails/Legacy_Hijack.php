<?php

namespace TEC\Tickets\Emails;

use Tribe__Tickets__Tickets as Tickets_Module;

/**
 * Class Legacy_Hijack.
 *
 * Mostly used to take over legacy methods of sending emails and using the new ones.w
 *
 * @since   5.6.0
 *
 * @package TEC\Tickets\Emails
 */
class Legacy_Hijack {

	/**
	 * Send RSVPs/tickets email for an attendee by injecting itself into the legacy Tickets codebase.
	 *
	 * @since 5.6.0
	 *
	 * @param null|boolean $pre           Previous value from the filter, mostly will be null.
	 * @param string       $to            The email to send the tickets to.
	 * @param array        $attendees     The list of Attendees to send the emails to.
	 * @param array        $args          {
	 *      The list of arguments to use for sending ticket emails.
	 *
	 *      @type string        $subject       The email subject.
	 *      @type string        $content       The email content.
	 *      @type string        $from_name     The name to send tickets from.
	 *      @type string        $from_email    The email to send tickets from.
	 *      @type array|string  $headers       The list of headers to send.
	 *      @type array         $attachments   The list of attachments to send.
	 *      @type string        $provider      The provider slug (rsvp, tpp, woo, edd).
	 *      @type int           $post_id       The post/event ID to send the emails for.
	 *      @type string|int    $order_id      The order ID to send the emails for.
	 * }
	 * @param Tickets_Module $module      Commerce module we are using for these emails.
	 *
	 * @return null|boolean  When we return boolean we disable the legacy emails regardless of status of this email, null lets the old emails trigger.
	 */
	public function send_tickets_email_for_attendee( $pre, $to, $attendees, $args = [], $module = null ): ?bool {
		// Only send back to the old email in case people opted-out of the Tickets Emails feature.
		if ( ! tec_tickets_emails_is_enabled() ) {
			return null;
		}

		if ( ! $module instanceof Tickets_Module ) {
			return false;
		}

		// If no tickets to send for, do not send email.
		if ( empty( $attendees ) ) {
			return false;
		}

		$sent     = false;
		$defaults = [
			'provider'     => 'ticket',
			'post_id'      => 0,
			'order_id'     => '',
			'order_status' => '',
		];

		// Set up the default arguments.
		$args = wp_parse_args( $args, $defaults );

		$provider = $args['provider'];
		$post_id  = $args['post_id'];
		$order_id = $args['order_id'];
		$is_rsvp  = 'rsvp' === $provider || ( is_object( $provider ) && 'Tribe__Tickets__RSVP' === get_class( $provider ) );

		if ( $is_rsvp ) {
			if ( 'no' !== strtolower( $args['order_status'] ) ) {
				$email_class      = tribe( Email\RSVP::class );
				$use_ticket_email = tribe_get_option( $email_class->get_option_key( 'use-ticket-email' ), false );
				if ( ! empty( $use_ticket_email ) ) {
					$email_class = tribe( Email\Ticket::class );
				}
			} else {
				$email_class = tribe( Email\RSVP_Not_Going::class );
			}

		} else {
			$email_class = tribe( Email\Ticket::class );
		}

		/**
		 * Filters the email class to use for sending tickets.
		 *
		 * @since 5.8.4
		 *
		 * @param Email_Abstract $email_class The email class instance to use for sending tickets.
		 * @param string         $provider    The provider slug ('rsvp', 'tpp', 'tc',  'woo', 'edd', etc.)
		 * @param int            $post_id     The Post or Event ID to send the emails for.
		 * @param string|int     $order_id    The Order ID to send the emails for.
		 * @param array          $args          {
		 *      The list of arguments to use for sending ticket emails.
		 *
		 *      @type string        $subject       The email subject.
		 *      @type string        $content       The email content.
		 *      @type string        $from_name     The name to send tickets from.
		 *      @type string        $from_email    The email to send tickets from.
		 *      @type array|string  $headers       The list of headers to send.
		 *      @type array         $attachments   The list of attachments to send.
		 *      @type string        $provider      The provider slug (rsvp, tpp, woo, edd).
		 *      @type int           $post_id       The post/event ID to send the emails for.
		 *      @type string|int    $order_id      The order ID to send the emails for.
		 * }
		 */
		$email_class = apply_filters( 'tec_tickets_email_class', $email_class, $provider, $post_id, $order_id, $args );

		if ( ! $email_class->is_enabled() ) {
			return false;
		}

		// Filter the array so that we have a list of Attendees by Post.
		$attendees_by_post = [];

		/*
		 * Note: in the following code the `$event_id` variable is used to indicate the post ID the ticket is attached
		 * to. This is a pattern across the code, but it does not imply that the Ticket is attached to an Event post,
		 * it could be attached to any post type.
		 * Furthermore: "tickets" here means "attendees".
		 */

		foreach ( $attendees as $attendee ) {
			$event_id = $attendee['event_id'];

			if ( ! isset( $attendees_by_post[ $event_id ] ) ) {
				$attendees_by_post[ $event_id ] = [];
			}

			$attendees_by_post[ $event_id ][] = $attendee;
		}

		// loop the tickets by event and send one email for each event.
		foreach ( $attendees_by_post as $event_id => $post_attendees ) {
			$email_class->set( 'post_id', $event_id );
			$email_class->set( 'tickets', $post_attendees );
			$email_class->recipient = $to;

			$sent = $email_class->send();

			// Handle marking the attendee ticket email as being sent.
			if ( $sent ) {
				// Mark attendee ticket email as being sent for each attendee ticket.
				foreach ( $post_attendees as $attendee ) {
					$module->update_ticket_sent_counter( $attendee['attendee_id'] );

					$module->update_attendee_activity_log(
						$attendee['attendee_id'],
						[
							'type'  => 'email',
							'name'  => $attendee['holder_name'],
							'email' => $attendee['holder_email'],
						]
					);
				}
			} else {
				break;
			}
		}

		return $sent;
	}

	/**
	 * Dispatches a confirmation email that acknowledges the user has RSVP'd
	 * including the tickets.
	 *
	 * @since 5.6.0
	 *
	 * @param null|boolean   $pre      Previous value from the filter, mostly will be null.
	 * @param int            $order_id The order ID.
	 * @param int            $event_id The event ID.
	 * @param Tickets_Module $module   Commerce module we are using for these emails.
	 *
	 * @return null|boolean  When we return boolean we disable the legacy emails regardless of status of this email, null lets the old emails trigger.
	 */
	public function send_rsvp_email( $pre, $order_id, $event_id = null, $module = null ): ?bool {
		// Only send back to the old email in case people opted-out of the Tickets Emails feature.
		if ( ! tec_tickets_emails_is_enabled() ) {
			return null;
		}

		if ( ! $module instanceof Tickets_Module ) {
			return false;
		}

		$all_attendees = $module->get_attendees_by_order_id( $order_id );

		$to_send = [];

		if ( empty( $all_attendees ) ) {
			return false;
		}

		// Look at each attendee and check if a ticket was sent: in each case where a ticket
		// has not yet been sent we should a) send the ticket out by email and b) record the
		// fact it was sent.
		foreach ( $all_attendees as $single_attendee ) {
			// Do not add those attendees/tickets marked as not attending (note that despite the name
			// 'qr_ticket_id', this key is not QR code specific, it's simply the attendee post ID).
			$going_status = get_post_meta( $single_attendee['qr_ticket_id'], $module::ATTENDEE_RSVP_KEY, true );
			if ( in_array( $going_status, $module->get_statuses_by_action( 'count_not_going' ), true ) ) {
				continue;
			}

			// Only add those attendees/tickets that haven't already been sent.
			if ( empty( $single_attendee['ticket_sent'] ) ) {
				$to_send[] = $single_attendee;
			}
		}

		/**
		 * Controls the list of tickets which will be emailed out.
		 *
		 * @param array $to_send       list of tickets to be sent out by email
		 * @param array $all_attendees list of all attendees/tickets, including those already sent out
		 * @param int   $order_id
		 */
		$to_send = (array) apply_filters( 'tribe_tickets_rsvp_tickets_to_send', $to_send, $all_attendees, $order_id );

		if ( empty( $to_send ) ) {
			return false;
		}

		// For now all ticket holders in an order share the same email.
		$to = $all_attendees['0']['holder_email'];

		if ( ! is_email( $to ) ) {
			return false;
		}

		$email_class = tribe( Email\RSVP::class );

		if ( ! $email_class->is_enabled() ) {
			return false;
		}

		$use_ticket_email = tribe_get_option( $email_class->get_option_key( 'use-ticket-email' ), false );
		if ( ! empty( $use_ticket_email ) ) {
			$email_class = tribe( Email\Ticket::class );
		}

		$email_class->set( 'post_id', $event_id );
		$email_class->set( 'tickets', $all_attendees );

		// @todo we need to avoid setting the recipient like this.
		$email_class->recipient = $to;

		$sent = $email_class->send();

		if ( $sent ) {
			foreach ( $all_attendees as $attendee ) {
				$module->update_ticket_sent_counter( $attendee['qr_ticket_id'] );

				$module->update_attendee_activity_log(
					$attendee['attendee_id'],
					[
						'type'  => 'email',
						'name'  => $attendee['holder_name'],
						'email' => $attendee['holder_email'],
					]
				);
			}
		}

		return $sent;
	}

	/**
	 * Dispatches a confirmation email that acknowledges the user has RSVP'd
	 * in cases where they have indicated that they will *not* be attending.
	 *
	 * @since 5.6.0
	 *
	 * @param null|boolean   $pre      Previous value from the filter, mostly will be null.
	 * @param int            $order_id The order ID.
	 * @param int            $event_id The event ID.
	 * @param Tickets_Module $module   Commerce module we are using for these emails.
	 *
	 * @return bool Whether the email was sent or not.
	 */
	public function send_rsvp_non_attendance_confirmation( $pre, $order_id, $event_id, $module ) {
		// Only send back to the old email in case people opted-out of the Tickets Emails feature.
		if ( ! tec_tickets_emails_is_enabled() ) {
			return null;
		}

		if ( ! $module instanceof Tickets_Module ) {
			return false;
		}

		$attendees = $module->get_attendees_by_order_id( $order_id );

		if ( empty( $attendees ) ) {
			return false;
		}

		// For now all ticket holders in an order share the same email.
		$to = $attendees['0']['holder_email'];

		if ( ! is_email( $to ) ) {
			return false;
		}

		$email_class = tribe( Email\RSVP_Not_Going::class );
		$email_class->set( 'post_id', $event_id );
		$email_class->set( 'tickets', $attendees );
		$email_class->recipient = $to;
		$sent                   = $email_class->send();

		return $sent;
	}
}