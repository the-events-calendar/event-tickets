<?php
/**
 * Event Tickets Emails: Main template > Body > Ticket > Attendee name.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/ticket/holder-name.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @since  5.5.9   Recipient Name.
 *
 * @var string $ticket
 */

if ( empty( $ticket['holder_name'] ) ) {
	return;
}

?>
<h2 class="tec-tickets__email-table-content-ticket-holder-name">
	<?php echo esc_html( $ticket['holder_name'] ); ?>
</h2>
