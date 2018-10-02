<?php

/**
 * Class Tribe__Tickets__Attendee_Info_View
 */
class Tribe__Tickets__Attendee_Info_View extends Tribe__Template {
	/**
	 * Tribe__Tickets__Attendee_Info_View constructor.
	 */
	public function __construct() {
		$this->set_template_origin( Tribe__Tickets__Main::instance() );
		$this->set_template_folder( 'src/views/registration/attendees' );
		$this->set_template_context_extract( true );
	}
}
