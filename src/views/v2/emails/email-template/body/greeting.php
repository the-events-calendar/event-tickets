<?php
/**
 * Tickets Emails Email Template Greeting
 *
 * @since  TBD   Email template greeting.
 *
 * @var string $recipient_name Email recipient's first name.
 */

$greeting_text = empty( $recipient_name ) ?
	__( 'Here\'s your ticket!', 'event-tickets' ) :
	sprintf(
		// Translators: %s - First name of email recipient.
		__( 'Here\'s your ticket, %s!', 'event-tickets' ),
		$recipient_name
	);

?>
<tr>
	<td style="padding:10px 0;">
		<h1 style="font-size: 28px;font-weight: 700;line-height: 30px;letter-spacing: 0px;text-align:left;color:#141827;">
			<?php echo esc_html( $greeting_text ); ?>
		</h1>
	</td>
</tr>
