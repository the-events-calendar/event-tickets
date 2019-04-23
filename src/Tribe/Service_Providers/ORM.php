<?php
/**
 * Registers Event Tickets ORM classes.
 *
 * @since 4.10.4
 */

/**
 * Class Tribe__Tickets__Service_Providers__ORM
 *
 * @since 4.10.4
 */
class Tribe__Tickets__Service_Providers__ORM extends tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 4.10.4
	 */
	public function register() {
		// Repositories, not bound as singleton to allow for decoration and injection.
		$this->container->bind( 'tickets.ticket-repository', 'Tribe__Tickets__Ticket_Repository' );
		$this->container->bind( 'tickets.ticket-repository.rsvp', 'Tribe__Tickets__Repositories__Ticket__RSVP' );
		$this->container->bind( 'tickets.ticket-repository.commerce', 'Tribe__Tickets__Repositories__Ticket__Commerce' );
		$this->container->bind( 'tickets.attendee-repository', 'Tribe__Tickets__Attendee_Repository' );
		$this->container->bind( 'tickets.attendee-repository.rsvp', 'Tribe__Tickets__Repositories__Attendee__RSVP' );
		$this->container->bind( 'tickets.attendee-repository.commerce', 'Tribe__Tickets__Repositories__Attendee__Commerce' );
		$this->container->bind( 'tickets.event-repository', 'Tribe__Tickets__Event_Repository' );

		add_filter( 'tribe_events_event_repository_map', array( $this, 'filter_events_repository_map' ) );
	}

	/**
	 * Filters the event repository map to replace the base Event repository with the
	 * tickets decorator.
	 *
	 * @since 4.10.4
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
