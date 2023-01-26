<?php
/**
 * Tickets Emails Email Template Recipient Name
 *
 * @since  TBD   Recipient Name.
 *
 * @var string $recipient_first_name Recipient's first name.
 * @var string $recipient_last_name  Recipient's last name.
 * @var string $ticket_text_color    Ticket text color.
 */

?>
<h2 style="font-size: 21px;font-weight: 700;line-height: 24px;margin:0;padding:0;background:transparent;color:<?php echo esc_attr( $ticket_text_color ); ?>;">
	<?php echo esc_html( $recipient_first_name ); ?>
	<?php echo esc_html( $recipient_last_name ); ?>
</h2>
