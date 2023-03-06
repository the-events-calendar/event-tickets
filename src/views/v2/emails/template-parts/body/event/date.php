<?php
/**
 * Event Tickets Emails: Main template > Body > Event > Date.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/event/date.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 */

if ( empty( $tickets[0]['event']['date'] ) ) {
	return;
}
?>
<tr>
	<td style="padding:0;">
		<p style="font-size: 14px;font-weight: 400;line-height: 23px;letter-spacing: 0px;text-align: left;">
			<?php echo esc_html( $tickets[0]['event']['date'] ); ?>
		</p>
	</td>
</tr>
