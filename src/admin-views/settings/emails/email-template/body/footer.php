<?php
/**
 * Tickets Emails Email Template Footer
 *
 * @since  TBD   Email template footer.
 * 
 * @var string $footer_content    HTML of footer content.
 * @var bool   $footer_credit     Show the footer credit?
 * @var string $header_bg_color   Header background color.
 * @var string $header_text_color Header text color.
 * 
 */

if ( empty( $footer_content ) && ! tribe_is_truthy( $footer_credit ) ) {
	return;
}
?>
<tr>
	<td style="padding:0px 20px 10px 20px;border-top:1px solid #efefef;background:<?php echo esc_attr( $header_bg_color ); ?>;">
		<table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;">
			<?php if ( ! empty( $footer_content ) ) : ?>
				<tr>
					<td style="padding:10px 0px 0px 0px;color:<?php echo esc_attr( $header_text_color ); ?>;">
						<?php echo wp_kses( $footer_content, 'post' ); ?>
					</td>
				</tr>
			<?php endif; ?>
			<?php if ( tribe_is_truthy( $footer_credit ) ) : ?>
				<tr>
					<td style="padding:10px 0px 0px 0px;text-align:right;color:<?php echo $header_text_color; ?>;" align="right">
					<?php
						$et_link = sprintf( 
							'<a href="%s" style="color:%s;">%s</a>',
							'#', // @todo Update link to ET.
							esc_attr( $header_text_color ),
							esc_html__( 'Event Tickets', 'event-tickets' )
						);
						echo sprintf(
							// Translators: %s - HTML link to `Event Tickets` website.
							__( 'Ticket powered by %s', 'event-tickets' ),
							$et_link
						);
					?>
					</td>
				</tr>
			<?php endif; ?>
		</table>
	</td>
</tr>