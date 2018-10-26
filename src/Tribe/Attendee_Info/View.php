<?php

/**
 * Class Tribe__Tickets__Attendee_Info__View
 */
class Tribe__Tickets__Attendee_Info__View extends Tribe__Template {
	/**
	 * Tribe__Tickets__Attendee_Info__View constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		$this->set_template_origin( tribe( 'tickets.main' ) );
		$this->set_template_folder( 'src/views/registration/attendees' );
		$this->set_template_context_extract( true );
		$this->set_template_folder_lookup( true );
	}

	/**
	 * Display the Attendee Info page when the correct permalink is loaded.
	 *
	 * @since TBD
	 */
	public function display_attendee_info_page( $content = '' ) {
		global $wp_query;

		if ( empty( $wp_query->query_vars['attendee-info'] ) ) {
			return $content;
		}

		$cart_tickets = apply_filters( 'event_tickets_in_cart', [] );
		$tickets      = array();

		foreach ( $cart_tickets as $ticket_id => $quantity ) {
			$ticket = get_post( $ticket_id );
			for ( $i = 0; $i < $quantity; $i++ ) {
				$tickets[] = $ticket;
			}
		}

		// enqueue styles for this page
		tribe_asset_enqueue( 'event-tickets-registration-page' );

		/** @var Tribe__Tickets__Attendee_Info__View $view */
		$view     = tribe( 'tickets.attendee_info.view' );
		$template = $view->template( 'content', array( 'tickets' => $tickets ), false );

		return $template;
	}
}
