<?php

/**
 * Class Tribe__Tickets__Attendee_Registration__View
 */
class Tribe__Tickets__Attendee_Registration__View extends Tribe__Template {
	/**
	 * Tribe__Tickets__Attendee_Registration__View constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		$this->set_template_origin( tribe( 'tickets.main' ) );
		$this->set_template_folder( 'src/views/registration' );
		$this->set_template_context_extract( true );
		$this->set_template_folder_lookup( true );
	}

	/**
	 * Display the Attendee Info page when the correct permalink is loaded.
	 *
	 * @since TBD
	 */
	public function display_attendee_registration_page( $content = '' ) {
		global $wp_query;

		// Bail if we don't have the flag to be in the registration page
		if ( empty( $wp_query->query_vars['attendee-registration'] ) ) {
			return $content;
		}

		$cart_tickets = apply_filters( 'event_tickets_in_cart', array() );
		$tickets      = array();
		$events       = array();
		$tick         = array();


		foreach ( $cart_tickets as $ticket_id => $quantity ) {
			// Only include those who have meta
			$has_meta = get_post_meta( $ticket_id, '_tribe_tickets_meta', true );

			if ( ! is_array( $has_meta ) || empty( $has_meta ) ) {
				continue;
			}

			$ticket     = get_post( $ticket_id );
			$ticket_obj = tribe( 'tickets.handler' )->get_object_connections( $ticket_id );
			$events[ $ticket_obj->event ][] = array( 'id' => $ticket_id, 'qty' => $quantity, 'provider' => $ticket_obj->provider );
			for ( $i = 0; $i < $quantity; $i++ ) {
				$tickets[] = $ticket;
			}
		}


		// enqueue styles and scripts for this page
		tribe_asset_enqueue( 'event-tickets-registration-page-styles' );
		tribe_asset_enqueue( 'event-tickets-registration-page-scripts' );

		wp_enqueue_style( 'dashicons' );

		/** @var Tribe__Tickets__Attendee_Registration__View $view */
		$view     = tribe( 'tickets.attendee_registration.view' );
		$template = $view->template( 'content', array( 'tickets' => $tickets, 'events' => $events ), false );

		return $template;
	}
}
