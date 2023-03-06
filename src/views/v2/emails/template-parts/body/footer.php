<?php
/**
 * Event Tickets Emails: Main template > Body > Footer.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/footer.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @var Tribe_Template  $this  Current template object.
 * @var string $footer_content    HTML of footer content.
 * @var bool   $footer_credit     Show the footer credit?
 * @var string $header_bg_color   Header background color.
 * @var string $header_text_color Header text color.
 */

if ( empty( $footer_content ) && empty( $footer_credit ) ) {
	return;
}
?>
<tr>
	<td class="tec-tickets__email-table-main-footer">
		<table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;">
			<?php $this->template( 'template-parts/body/footer/content' ); ?>
			<?php $this->template( 'template-parts/body/footer/credit' ); ?>
		</table>
	</td>
</tr>
