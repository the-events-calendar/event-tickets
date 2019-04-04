<?php

/**
 * The ORM/Repository class for RSVP tickets.
 *
 * @since TBD
 */
class Tribe__Tickets__Repositories__Ticket__RSVP extends Tribe__Tickets__Ticket_Repository {

	/**
	 * {@inheritdoc}
	 */
	public function ticket_types() {
		return [
			'tribe_rsvp_tickets',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function ticket_to_event_keys() {
		return [
			'rsvp' => '_tribe_rsvp_for_event',
		];
	}

}
