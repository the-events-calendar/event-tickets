<?php
/**
 * Tickets Emails Email Template Header Image
 *
 * @since  5.5.7   Header image.
 *
 * @var string $header_image_url URL of header image.
 */

if ( empty( $header_image_url ) ) {
	return;
}
?>
<img style="max-height:100px;max-width:100%;margin:0px;display:inline-block" src="<?php echo esc_url( $header_image_url ); ?>" />
