<?php

/**
 * Tickets block Setup
 */
class Tribe__Tickets__Editor__Blocks__Tickets_Item extends Tribe__Editor__Blocks__Abstract {

	/**
	 * Which is the name/slug of this block
	 *
	 * @since 4.9.2
	 *
	 * @return string
	 */
	public function slug() {
		return 'tickets-item';
	}

	/**
	 * Since we are dealing with a Dynamic type of Block we need a PHP method to render it
	 *
	 * @since 4.9.2
	 *
	 * @param  array $attributes
	 *
	 * @return string
	 */
	public function render( $attributes = array() ) {
		/** @var Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		if (
			empty( $attributes['ticketId'] )
			|| empty( $attributes['hasBeenCreated'] )
			|| $attributes['hasBeenCreated'] === false
		) {
			return;
		}

		$ticket_post = get_post( $attributes['ticketId'] );

		// Prevent to attach blocks with tickets removed or under trash
		if ( ! $ticket_post instanceof WP_Post || 'publish' !== $ticket_post->post_status ) {
			return;
		}

		$tickets  = $template->get( 'tickets', array(), false );
		$ticket   = Tribe__Tickets__Tickets::load_ticket_object( $attributes['ticketId'] );

		// Bail if for some reason there was an RSVP here
		if ( null === $ticket || 'Tribe__Tickets__RSVP' === $ticket->provider_class ) {
			return;
		}

		// Bail if the ticket dates are not in range
		if ( ! $ticket->date_in_range() ) {
			return;
		}

		$existing_tickets = wp_list_pluck( $tickets, 'ID' );

		// Prevent adding tickets that are already in the list
		if ( in_array( $ticket->ID, $existing_tickets ) ) {
			return;
		}

		$args = array(
			'tickets' => array_merge( $tickets, array( $ticket ) ),
		);

		// Add the rendering attributes into global context
		$template->add_template_globals( $args );
	}
}
