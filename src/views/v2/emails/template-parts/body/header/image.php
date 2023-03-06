<?php
/**
 * Event Tickets Emails: Main template > Body > Header > Image.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/header/image.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @var string $header_image_url URL of header image.
 */

if ( empty( $header_image_url ) ) {
	return;
}
?>
<img style="max-height:100px;max-width:100%;margin:0px;display:inline-block" src="<?php echo esc_url( $header_image_url ); ?>" />
