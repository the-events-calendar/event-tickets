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

$site_link   = sprintf(
	'<a href="%1$s" class="tec-tickets__email-table-main-footer-credit-link">%2$s</a>',
	esc_url( site_url() ),
	get_bloginfo( 'name' )
);
$et_link     = sprintf(
	'<a href="%1$s" class="tec-tickets__email-table-main-footer-credit-link">%2$s</a>',
	'https://evnt.is/et-in-app-email-credit',
	esc_html__( 'Event Tickets', 'event-tickets' )
);
$credit_html = sprintf(
	// Translators: %1$s - HTML link to origin website; %2$s - HTML link to `Event Tickets` website.
	__( '%1$s tickets are powered by %2$s', 'event-tickets' ),
	$site_link,
	$et_link
);

?>
<tr>
	<td class="tec-tickets__email-table-main-footer-credit-container" align="right">
		<?php echo wp_kses_post( $credit_html ); ?>
	</td>
</tr>
