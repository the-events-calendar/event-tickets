<?php
/**
 * Tickets Emails Email Template Event Title
 *
 * @since  5.5.7   Event title.
 *
 * @var string $event_title Text for event title.
 */

if ( empty( $event_title ) ) {
	return;
}
?>
<tr>
	<td style="padding:0;">
		<?php echo esc_html( $event_title ); ?>
	</td>
</tr>
