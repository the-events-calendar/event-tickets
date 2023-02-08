<?php
/**
 * Event Tickets Emails: Main template > Body > Ticket > Attendee name.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/ticket/attendee-name.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version TBD
 *
 * @since TBD
 *
 * @since  TBD   Recipient Name.
 *
 * @var string $recipient_first_name Recipient's first name.
 * @var string $recipient_last_name  Recipient's last name.
 * @var string $ticket_text_color    Ticket text color.
 */

if (
	empty( $ticket_attendee_first_name )
	&& empty( $ticket_attendee_last_name )
) {
	return;
}

?>
<h2 class="tec-tickets__email-table-content-ticket-attendee-name">
	<?php echo esc_html( $ticket_attendee_first_name ); ?>
	<?php echo esc_html( $ticket_attendee_last_name ); ?>
</h2>
