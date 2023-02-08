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
 * @version TBD
 *
 * @since TBD
 */

// @todo Update add links.
?>
<tr>
	<td style="padding:0;">
		<table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;">
			<tr>
				<td style="padding:30px 10px;text-align:right;width:50%" align="right">
					<a href="#" style="color:#3C434A">
						<?php esc_html_e( 'Add event to iCal', 'event-tickets' ); ?>
					</a>
				</td>
				<td style="padding:30px 10px;text-align:left;width:50%" align="left">
					<a href="#" style="color:#3C434A">
						<?php esc_html_e( 'Add event to Google Calendar', 'event-tickets' ); ?>
					</a>
				</td>
			</tr>
		</table>
	</td>
</tr>
