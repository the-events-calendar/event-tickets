<?php
/**
 * Event Tickets Emails: Main template > Body > Event > Title.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/event/title.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @var string $event_title Text for event title.
 */

if ( empty( $tickets[0]['event']['title'] ) ) {
	return;
}
?>
<tr>
	<td style="padding:0;">
		<h3 class="tec-tickets__email-table-content-event-title"><?php echo esc_html( $tickets[0]['event']['title'] ); ?></h3>
	</td>
</tr>
