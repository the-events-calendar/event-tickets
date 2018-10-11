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
	}

	/**
	 * Display the Attendee Info page when the correct permalink is loaded.
	 *
	 * @since TBD
	 */
	public function display_attendee_info_page() {
		global $wp_query;

		if ( empty( $wp_query->query_vars['attendeeInfo'] ) ) {
			return;
		}

		$default_module = Tribe__Tickets__Tickets::get_event_ticket_provider();

		/**
		 * @var Tribe__Tickets__Tickets $module
		 */
		$module = $default_module::get_instance();

		// @todo: get actual tickets here from the various carts
		$cart_tickets = $module->get_tickets_in_cart();
		$tickets = [];

		foreach ( $cart_tickets as $ticket_id => $quantity ) {
			$ticket = get_post( $ticket_id );
			for ( $i = 0; $i < $quantity; $i++ ) {
				$tickets[] = $ticket;
			}
		}

		/** @var Tribe__Tickets__Attendee_Info__View $view */
		$view = tribe( 'tickets.attendee_info.view' );
		$view->template( 'content', array( 'tickets' => $tickets ) );
		tribe_exit();
	}
}
