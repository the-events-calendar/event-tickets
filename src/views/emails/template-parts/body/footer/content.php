<?php
/**
 * Event Tickets Emails: Main template > Body > Footer > Content.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/footer/content.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.11
 *
 * @var Tribe__Template                    $this               Current template object.
 * @var \TEC\Tickets\Emails\Email_Abstract $email              The email object.
 * @var bool                               $preview            Whether the email is in preview mode or not.
 * @var string                             $additional_content The email additional content.
 * @var bool                               $is_tec_active      Whether `The Events Calendar` is active or not.
 * @var string                             $footer_content    HTML of footer content.
 * @var string                             $header_text_color Header text color.
 * @var WP_Post|null                       $event             The event post object with properties added by the `tribe_get_event` function.
 * @var WP_Post|null                       $order             The order object.
 *
 * @see tribe_get_event() For the format of the event object.
 */

if ( empty( $footer_content ) ) {
	return;
}
?>
<tr>
	<td class="tec-tickets__email-table-main-footer-content-container">
		<?php echo wp_kses_post( $footer_content ); ?>
	</td>
</tr>
