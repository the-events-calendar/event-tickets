<?php
/**
 * Event Tickets Emails: RSVP "Not Going" > Body.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/rsvp-not-going/body.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version TBD
 *
 * @since 5.5.10
 *
 * @var Tribe_Template  $this  Current template object.
 */

$this->template( 'template-parts/body/title' );

?>
<tr>
	<td>
		<?php echo esc_html( __( 'Thank you for confirming that you will not be attending.', 'event-tickets' ) ); ?>
	</td>
</tr>
