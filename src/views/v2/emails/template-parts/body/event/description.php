<?php
/**
 * Event Tickets Emails: Main template > Body > Event > Description.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/event/description.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version TBD
 *
 * @since TBD
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
