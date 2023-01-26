<?php
/**
 * Tickets Emails Email Template Recipient Name
 *
 * @since  TBD   Recipient Name.
 *
 * @var string $ticket_name       Ticket name.
 * @var string $ticket_id         Ticket ID.
 * @var string $ticket_text_color Ticket text color.
 * @var string $ticket_bg_color   Ticket background color.
 */

?>
<tr>
	<td style="padding:20px 25px;background:<?php echo esc_attr( $ticket_bg_color ); ?>">
		<?php $this->template( 'email-template/body/recipient-name' ); ?>
		<p style="font-size: 16px;margin:0;padding:0;color:<?php echo esc_attr( $ticket_text_color ); ?>;">
			<?php echo esc_html( $ticket_name ); ?>
		</p>
		<p style="font-size: 14px;font-weight: 400;margin:0;padding:15px 0 0 0;color:<?php echo esc_attr( $ticket_text_color ); ?>;">
			<?php echo esc_html( $ticket_id ); ?>
		</p>
	</td>
</tr>
