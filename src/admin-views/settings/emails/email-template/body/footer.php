<?php


if ( empty( $footer_content ) && ! tribe_is_truthy( $footer_credit ) ) {
	return;
}
?>
<tr>
	<td style="padding:0px 20px 10px 20px;border-top:1px solid #efefef;background:<?php echo $header_bg_color; ?>;">
		<table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;">
			<?php if ( ! empty( $footer_content ) ) : ?>
				<tr>
					<td style="padding:10px 0px 0px 0px;color:<?php echo $header_text_color; ?>;">
						<?php echo $footer_content; ?>
					</td>
				</tr>
			<?php endif; ?>
			<?php if ( tribe_is_truthy( $footer_credit ) ) : ?>
				<tr>
					<td style="padding:10px 0px 0px 0px;text-align:right;font-size:11px;color:<?php echo $header_text_color; ?>;" align="right">
						Ticket powered by <a href="#" style="color:<?php echo $header_text_color; ?>;">Event Tickets</a>
					</td>
				</tr>
			<?php endif; ?>
		</table>
	</td>
</tr>