<?php

/**
 * Class Tribe__Tickets_Plus__Commerce__PayPal__Views
 *
 * @since 4.7
 */
class Tribe__Tickets__Attendee_Info_View extends Tribe__Template {
	/**
	 * Tribe__Tickets_Plus__Commerce__PayPal__Views constructor.
	 *
	 * @since 4.7
	 */
	public function __construct() {
		$this->set_template_origin( Tribe__Tickets__Main::instance() );
		$this->set_template_folder( 'src/views/registration/attendees' );
		$this->set_template_context_extract( true );
	}
}
