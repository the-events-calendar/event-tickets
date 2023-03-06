<?php
/**
 * Event Tickets Emails: Main template > Body > Ticket > Number from total tickets.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/ticket/number-from-total.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 */

if ( empty( $tickets ) || count( $tickets ) === 1 ) {
	return;
}

?>
<div class="tec-tickets__email-table-content-ticket-number-from-total">
	<?php
		echo sprintf(
			// Translators: %1$s: Tickets label, in singular. %2$s: Current ticket number over total. %3$s: Number of total Tickets.
			esc_html__( '%1$s %2$s of %2$s', 'event_tickets' ),
			tribe_get_ticket_label_singular( 'tec_tickets_email_ticket_total' ),
			$i,
			count( $tickets )
		);
	?>
</div>
