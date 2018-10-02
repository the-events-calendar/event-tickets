<?php

/**
 * Class Tribe__Tickets__Attendee_Info
 */
class Tribe__Tickets__Attendee_Info {

	public function hook() {
		// Attendee Info rewrites
		add_action( 'plugins_loaded', array( Tribe__Tickets__Rewrite::instance(), 'hooks' ) );
		add_action( 'template_redirect', array( $this, 'display_attendee_info_page' ) );
	}

	public function display_attendee_info_page() {
		global $wp_query;

		if ( empty( $wp_query->query_vars['attendeeInfo'] ) ) {
			return;
		}

		// @todo: get actual tickets here from the various carts
		$tickets = array();

		/** @var Tribe__Tickets__Attendee_Info_View $view */
		$view = tribe( 'tickets.attendees.view' );
		$view->template( 'content', array( 'tickets' => $tickets ) );
		die();
	}

}
