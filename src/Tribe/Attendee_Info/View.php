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

		// @todo: get actual tickets here from the various carts
		$tickets = array();

		/** @var Tribe__Tickets__Attendee_Info__View $view */
		$view = tribe( 'tickets.attendee_info.view' );
		$view->template( 'content', array( 'tickets' => $tickets ) );
		tribe_exit();
	}
}
