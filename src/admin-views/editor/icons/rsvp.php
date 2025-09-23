<?php
/**
 * Template for the RSVP ticket icon.
 *
 * @version 5.8.0
 */
?>

<img
	class="tribe-tickets-svgicon tec-tickets-icon tec-tickets-icon__ticket-type"
	src="<?php echo esc_url( tribe_resource_url( 'icons/ticket-rsvp-icon.svg', false, null, \Tribe__Tickets__Main::instance() ) ); ?>"
	alt="<?php esc_attr_e( 'RSVP', 'event-tickets' ); ?>"
/>
