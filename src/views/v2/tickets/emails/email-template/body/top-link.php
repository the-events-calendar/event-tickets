<?php
/**
 * Tickets Emails Email Template Top Link
 *
 * @since  TBD   Top Link.
 * 
 */


 // @todo Update link URL
 
?>
<td style="padding:10px 15px;text-align:center;" align="center">
	<?php echo sprintf(
		'%s <a href="#" style="color:#3C434A;">%s</a>',
		esc_html__( 'Having trouble viewing this email?', 'event-tickets' ),
		esc_html__( 'Click here', 'event-tickets' ),
	); ?>
</td>