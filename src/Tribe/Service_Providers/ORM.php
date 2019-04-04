<?php
/**
 * Registers Event Tickets ORM classes.
 *
 * @since TBD
 */

/**
 * Class Tribe__Tickets__Service_Providers__ORM
 *
 * @since TBD
 */
class Tribe__Tickets__Service_Providers__ORM extends tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		// Repositories, not bound as singleton to allow for decoration and injection.
		$this->container->bind( 'tickets.ticket-repository', 'Tribe__Tickets__Ticket_Repository' );
		$this->container->bind( 'tickets.attendee-repository', 'Tribe__Tickets__Attendee_Repository' );
		$this->container->bind( 'tickets.event-repository', 'Tribe__Tickets__Event_Repository' );

		add_filter( 'tribe_events_event_repository_map', array( $this, 'filter_events_repository_map' ) );
	}

	/**
	 * Filters the event repository map to replace the base Event repository with the
	 * tickets decorator.
	 *
	 * @since TBD
	 *
	 * @param array $map The repository map to filter.
	 *
	 * @return array The filtered repository map.
	 */
	public function filter_events_repository_map( array $map ) {
		$map['default'] = 'tickets.event-repository';

		return $map;
	}
}
