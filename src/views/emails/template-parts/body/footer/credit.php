<?php
/**
 * Event Tickets Emails: Main template > Body > Footer.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/footer/credit.php
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
 * @var bool                               $preview            Whether the email is in preview mode or not.
 * @var string                             $additional_content The email additional content.
 * @var bool                               $is_tec_active      Whether `The Events Calendar` is active or not.
 * @var bool                               $footer_credit      Show the footer credit?
 * @var string                             $header_text_color  Header text color.
 * @var WP_Post|null                       $event              The event post object with properties added by the `tribe_get_event` function.
 * @var WP_Post|null                       $order              The order object.
 *
 * @see tribe_get_event() For the format of the event object.
 */

if ( ! tribe_is_truthy( $footer_credit ) ) {
	return;
}

$et_link     = sprintf(
	'<a href="%1$s" style="color:%2$s;">%3$s</a>',
	'https://evnt.is/et-in-app-email-credit',
	esc_attr( $header_text_color ),
	esc_html__( 'Event Tickets', 'event-tickets' )
);
$credit_html = sprintf(
	// Translators: %s - HTML link to `Event Tickets` website.
	__( 'Powered by %1$s', 'event-tickets' ),
	$et_link
);

?>
<tr>
	<td style="padding:10px 0px 0px 0px;text-align:right;color:<?php echo esc_attr( $header_text_color ); ?>;" align="right">
		<?php echo wp_kses_post( $credit_html ); ?>
	</td>
</tr>
