<?php
/**
 * Event Tickets Emails: Main template > Body > Footer > Content.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/footer/content.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @var string $footer_content    HTML of footer content.
 * @var string $header_text_color Header text color.
 */

if ( empty( $footer_content ) ) {
	return;
}
?>
<tr>
	<td style="padding: 20px 0;color:<?php echo esc_attr( $header_text_color ); ?>;">
		<?php echo wp_kses_post( $footer_content ); ?>
	</td>
</tr>
