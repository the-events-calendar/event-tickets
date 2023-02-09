<?php
/**
 * Tickets Emails Email Template Header
 *
 * @since  5.5.7   Header.
 *
 * @var string $header_bg_color        Header background color.
 * @var string $header_image_alignment Header image alignment text.
 */

?>
<tr>
	<td
		style="padding:5px 5px 0px 5px;background:<?php echo esc_attr( $header_bg_color ); ?>;text-align:<?php echo esc_attr( $header_image_alignment ); ?>"
		align="<?php echo esc_attr( $header_image_alignment ); ?>"
	>
		<?php $this->template( 'email-template/body/header-image' ); ?>
	</td>
</tr>
