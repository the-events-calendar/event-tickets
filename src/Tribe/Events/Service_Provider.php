<?php
/**
 * Register classes, actions and filters
 * that Event Tickets uses to manage "Events".
 */

namespace Tribe\Tickets\Events;

/**
 * Class Service_Provider
 *
 * @since TBD
 *
 * @package Tribe\Tickets\Events
 */
class Service_Provider extends \tad_DI52_ServiceProvider {

	/**
	 * Register classes in the container that Event Tickets uses
	 * to manage Events.
	 *
	 * @since TBD
	 */
	public function register() {
		tribe_singleton( 'tickets.events.attendee_list', Attendee_List::class );

		$this->hooks();
	}

	/**
	 * Actions and filters that Event Tickets uses to
	 * to manage Events.
	 *
	 * @since TBD
	 */
	protected function hooks() {
		add_action( 'save_post', [ tribe( Attendee_List::class ), 'maybe_update_attendee_list_hide_meta' ], 10 );
		add_filter( 'tribe_tickets_plus_hide_attendees_list_optout', [ tribe( Attendee_List::class ), 'should_hide_optout' ], 1 );
	}

}
