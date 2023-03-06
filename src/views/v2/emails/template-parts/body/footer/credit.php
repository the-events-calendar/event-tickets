<?php
/**
 * Event Tickets Emails: Main template > Body > Footer.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/footer/credit.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @var bool   $footer_credit     Show the footer credit?
 * @var string $header_text_color Header text color.
 */

if ( ! tribe_is_truthy( $footer_credit ) ) {
	return;
}

$et_link     = sprintf(
	'<a href="%s" style="color:%s;">%s</a>',
	'#', // @todo Update link to ET.
	esc_attr( $header_text_color ),
	esc_html__( 'Event Tickets', 'event-tickets' )
);
$credit_html = sprintf(
	// Translators: %s - HTML link to `Event Tickets` website.
	__( 'Ticket powered by %s', 'event-tickets' ),
	$et_link
);

?>
<tr>
	<td style="padding:10px 0px 0px 0px;text-align:right;color:<?php echo esc_attr( $header_text_color ); ?>;" align="right">
		<?php echo wp_kses_post( $credit_html ); ?>
	</td>
</tr>
