<?php
/**
 * Tickets Emails Email Template Footer Credit
 *
 * @since  TBD   Email template footer credit.
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
