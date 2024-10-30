<?php
/**
 * Template for the Series Pass ticket icon.
 *
 * @version 5.8.0
 */
?>

<img
	class="tribe-tickets-svgicon tec-tickets-icon tec-tickets-icon__ticket-type"
	src="<?php echo esc_url( tribe_resource_url( 'icons/ticket-series-pass-icon.svg', false, null, \Tribe__Tickets__Main::instance() ) ); ?>"
	alt="<?php echo esc_html( tec_tickets_get_series_pass_singular_uppercase( 'icon_alt_text' ) ); ?>"
/>
