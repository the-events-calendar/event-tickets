<?php
/**
 * Tickets Emails Email Template Footer
 *
 * @since  TBD   Email template footer.
 * 
 * @var Tribe__Template  $this  Parent template object.
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
			<?php $this->template( 'email-template/footer-content' ); ?>
			<?php $this->template( 'email-template/footer-credit' ); ?>
		</table>
	</td>
</tr>