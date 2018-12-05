<?php

/**
 * Tickets block Setup
 */
class Tribe__Tickets__Editor__Blocks__Tickets_Item extends Tribe__Editor__Blocks__Abstract {

	/**
	 * Which is the name/slug of this block
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function slug() {
		return 'tickets-item';
	}

	/**
	 * Since we are dealing with a Dynamic type of Block we need a PHP method to render it
	 *
	 * @since TBD
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
		$provider = $template->get( 'provider' );

		if ( empty( $provider ) ) {
			return;
		}

		if ( $ticket->provider_class !== $provider->class_name ) {
			return;
		}

		$args = array(
			'tickets' => array_merge( $tickets, array( $ticket ) ),
		);

		// Add the rendering attributes into global context
		$template->add_template_globals( $args );
	}
}
