<?php
/**
 * Event Tickets Emails: Main template > Body > Tickets total.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/tickets-total.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @var Tribe_Template  $this  Current template object.
 */

if ( empty( $tickets ) || count( $tickets ) === 1 ) {
	return;
}

?>
<tr>
	<td class="tec-tickets__email-table-content-tickets-total">
		<?php
		echo sprintf(
			// Translators: %1$s: Number of total Tickets. %2$s: Tickets label, in plural.
			esc_html__( '%1$s %2$s Total', 'event_tickets' ),
			count( $tickets ),
			tribe_get_ticket_label_plural( 'tec_tickets_email_ticket_total' )
		);
		?>
	</td>
</tr>
