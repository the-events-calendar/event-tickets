<?php
/**
 * Event Tickets Emails: Main template > Body > Footer.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/footer.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @var Tribe__Template                    $this               Current template object.
 * @var \TEC\Tickets\Emails\Email_Abstract $email              The email object.
 * @var string                             $heading            The email heading.
 * @var string                             $title              The email title.
 * @var bool                               $preview            Whether the email is in preview mode or not.
 * @var string                             $additional_content The email additional content.
 * @var bool                               $is_tec_active      Whether `The Events Calendar` is active or not.
 * @var string                             $footer_content     HTML of footer content.
 * @var bool                               $footer_credit      Show the footer credit?
 * @var string                             $header_bg_color    Header background color.
 * @var string                             $header_text_color  Header text color.
 * @var string                             $footer_content     HTML of footer content.
 * @var WP_Post|null                       $event              The event post object with properties added by the `tribe_get_event` function.
 * @var WP_Post|null                       $order              The order object.
 *
 * @see tribe_get_event() For the format of the event object.
 */

if ( empty( $footer_content ) && empty( $footer_credit ) ) {
	return;
}
?>
<tr>
	<td class="tec-tickets__email-table-main-footer">
		<table role="presentation" class="tec-tickets__email-table-main-footer-table">
			<?php $this->template( 'template-parts/body/footer/content' ); ?>
			<?php $this->template( 'template-parts/body/footer/credit' ); ?>
		</table>
	</td>
</tr>
