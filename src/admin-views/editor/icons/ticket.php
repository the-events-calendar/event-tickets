<?php
/**
 * Template for the default Ticket icon.
 *
 * @version 5.8.0
 */
?>

<img
	class="tribe-tickets-svgicon tec-tickets-icon tec-tickets-icon__ticket-type"
	src="<?php echo esc_url( tribe_resource_url( 'icons/ticket-default-icon.svg', false, null, \Tribe__Tickets__Main::instance() ) ); ?>"
	alt="<?php echo esc_html( tribe_get_ticket_label_singular( 'icon_alt_text' ) ); ?>"
/>
