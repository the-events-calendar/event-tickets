<?php
/**
 * Tickets Emails Email Template Event Image
 *
 * @since  TBD   Event image.
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
