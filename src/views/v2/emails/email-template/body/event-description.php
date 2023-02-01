<?php
/**
 * Tickets Emails Email Template Event Description
 *
 * @since  TBD   Event description.
 *
 * @var string $event_description HTML of event description.
 */

if ( empty( $event_description ) ) {
	return;
}

?>
<tr>
	<td style="padding:0;">
		<?php echo wp_kses_post( $event_description ); ?>
	</td>
</tr>
