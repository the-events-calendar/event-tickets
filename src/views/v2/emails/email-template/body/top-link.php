<?php
/**
 * Tickets Emails Email Template Top Link.
 *
 * @since  5.5.7   Top Link.
 */

if ( empty( $web_view_url ) ) {
	return;
}
?>
<td style="padding:10px 15px;text-align:center;font-size:11px;" align="center">
	<?php
	echo sprintf(
		// Translators: %1$s: Opening `<a>` tag for the email web view. %2$s: Closing `</a>`.
		esc_html__( 'Having trouble viewing this email? %1$sClick here%2$s', 'event-tickets' ),
		'<a href="' . esc_url( $web_view_url ) . '" target="_blank" rel="noopener noreferrer">',
		'</a>'
	);
	?>
</td>
