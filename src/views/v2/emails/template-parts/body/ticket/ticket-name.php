<?php
/**
 * Event Tickets Emails: Main template > Body > Ticket > Ticket type name.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/ticket/ticket-name.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @var string $recipient_first_name Recipient's first name.
 * @var string $recipient_last_name  Recipient's last name.
 * @var string $ticket_text_color    Ticket text color.
 */

if ( empty( $ticket['ticket_name'] ) ) {
	return;
}

?>
<div class="tec-tickets__email-table-content-ticket-type-name">
	<?php echo esc_html( $ticket['ticket_name'] ); ?>
</div>
