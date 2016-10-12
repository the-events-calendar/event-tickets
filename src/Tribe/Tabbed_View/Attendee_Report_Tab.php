<?php


class Tribe__Tickets__Tabbed_View__Attendee_Report_Tab extends Tribe__Tabbed_View__Tab {

	/**
	 * @var bool
	 */
	protected $visible = true;

	/**
	 * @var string
	 */
	protected $slug = 'tribe-tickets-attendance-report';

	public function get_url( $args = array(), $relative = false ) {
		return 'foo';
	}

	public function get_label() {
		return __( 'Attendees', 'event-tickets' );
	}

}