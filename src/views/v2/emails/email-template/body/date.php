<?php
/**
 * Tickets Emails Email Template Date String
 *
 * @since  5.5.7   Add links for email template that allows recipient to subscribe to events.
 *
 * @var string $date_string Textual representation of event date and time. Format will vary based on user settings/language.
 */

if ( empty( $date_string ) ) {
	return;
}
?>
<tr>
	<td style="padding:0;">
		<p style="font-size: 14px;font-weight: 400;line-height: 23px;letter-spacing: 0px;text-align: left;">
			<?php echo esc_html( $date_string ); ?>
		</p>
	</td>
</tr>
