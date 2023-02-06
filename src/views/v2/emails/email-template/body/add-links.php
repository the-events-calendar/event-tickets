<?php
/**
 * Tickets Emails Email Template 'Add' links
 *
 * @since  5.5.7   Add links for email template that allows recipient to subscribe to events.
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
