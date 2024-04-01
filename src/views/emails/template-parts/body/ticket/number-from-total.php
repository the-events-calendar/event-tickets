<?php
/**
 * Event Tickets Emails: Main template > Body > Ticket > Number from total tickets.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/ticket/number-from-total.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 * If you are looking for Event related templates, see in The Events Calendar plugin.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @var Tribe__Template                    $this             Current template object.
 * @var \TEC\Tickets\Emails\Email_Abstract $email            The email object.
 * @var bool                               $preview          Whether the email is in preview mode or not.
 * @var bool                               $is_tec_active    Whether `The Events Calendar` is active or not.
 * @var array                              $tickets          The list of tickets.
 * @var array                              $ticket           The ticket object.
 * @var int                                $i                The current ticket number, from total.
 * @var WP_Post|null                       $event The event post object with properties added by the `tribe_get_event` function.
 *
 * @see tribe_get_event() For the format of the event object.
 */

if ( empty( $tickets ) || count( $tickets ) === 1 ) {
	return;
}

?>
<div class="tec-tickets__email-table-content-ticket-number-from-total">
	<?php
		echo sprintf(
			// Translators: %1$s: Tickets label, in singular. %2$s: Current ticket number over total. %3$s: Number of total Tickets.
			esc_html__( '%1$s %2$s of %3$s', 'event-tickets' ),
			tribe_get_ticket_label_singular( 'tec_tickets_email_ticket_total' ),
			$i,
			count( $tickets )
		);
	?>
</div>
