<?php

namespace Tribe\Tickets\Events;

class Events_Service_Provider extends \tad_DI52_ServiceProvider {

	/**
	 * Register classes in the container that Event Tickets uses
	 * to manage Events.
	 *
	 * @since TBD
	 */
	public function register() {
		tribe_singleton( Attendee_List_Display::class, Attendee_List_Display::class );

		$this->hooks();
	}

	/**
	 * Actions and filters that Event Tickets uses to
	 * to manage Events.
	 *
	 * @since TBD
	 */
	protected function hooks() {
		add_action( 'save_post_tribe_events', [ tribe( Attendee_List_Display::class ), 'maybe_update_attendee_list_hide_meta' ], 10 );
		add_filter( 'tribe_tickets_plus_hide_attendees_list_optout', [ tribe( Attendee_List_Display::class ), 'should_hide_optout' ], 1 );
	}

}
