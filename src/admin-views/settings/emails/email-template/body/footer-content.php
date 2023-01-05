<?php
/**
 * Tickets Emails Email Template Footer Content
 *
 * @since  TBD   Email template footer content.
 * 
 * @var string $footer_content    HTML of footer content.
 * @var string $header_text_color Header text color.
 * 
 */

if ( empty( $footer_content ) ) {
	return;
}

?><tr>
	<td style="padding:10px 0px 0px 0px;color:<?php echo esc_attr( $header_text_color ); ?>;">
		<?php echo wp_kses_post( $footer_content ); ?>
	</td>
</tr>