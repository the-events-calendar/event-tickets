<?php
/**
 * Event Tickets Emails: Main template > Body > Event > Image.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/event/image.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version TBD
 *
 * @since TBD
 *
 * @var string $event_image_url URL for the event image.
 */

if ( empty( $event_image_url ) ) {
	return;
}
?>
<tr>
	<td style="padding:0;">
		<img src="<?php echo esc_url( $event_image_url ); ?>" style="display:block;margin:0;width:100%;" />
	</td>
</tr>
