<?php
/**
 * Event Tickets Emails: Main template > Body > Ticket > Security code.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/ticket/security-code.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version TBD
 *
 * @since TBD
 */

if ( empty( $ticket_security_code ) ) {
	return;
}

?>
<div class="tec-tickets__email-table-content-ticket-security-code">
	<?php echo esc_html( $ticket_security_code ); ?>
</div>
