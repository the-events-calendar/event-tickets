<?php

/**
 * The ORM/Repository class for RSVP attendees.
 *
 * @since TBD
 */
class Tribe__Tickets__Repositories__Attendee__RSVP extends Tribe__Tickets__Attendee_Repository {

	/**
	 * {@inheritdoc}
	 */
	public function attendee_types() {
		return [
			'tribe_rsvp_attendees',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_to_event_keys() {
		return [
			'rsvp' => '_tribe_rsvp_event',
		];
	}

}
