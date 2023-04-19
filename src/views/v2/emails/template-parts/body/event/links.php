<?php
/**
 * Event Tickets Emails: Main template > Body > Event > Links.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/event/links.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 */

// @todo @codingmusician @juanfra @rafsuntaskin: We need to move this to TEC, and the calendar links should be coming from there.
?>
<tr>
	<td style="padding:0;">
		<table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;">
			<tr>
				<td style="padding:30px 10px;text-align:center;width:100%" align="center">
					<a href="#" style="padding:0 8px;">
						<?php esc_html_e( 'Add event to iCal', 'event-tickets' ); ?>
					</a>
					<a href="#" style="padding:0 8px;">
						<?php esc_html_e( 'Add event to Google Calendar', 'event-tickets' ); ?>
					</a>
				</td>
			</tr>
		</table>
	</td>
</tr>
