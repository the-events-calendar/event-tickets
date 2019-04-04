<?php

/**
 * The ORM/Repository class for Tribe Commerce (PayPal) attendees.
 *
 * @since TBD
 */
class Tribe__Tickets__Repositories__Attendee__Commerce extends Tribe__Tickets__Attendee_Repository {

	/**
	 * {@inheritdoc}
	 */
	public function attendee_types() {
		return [
			'tribe_tpp_attendees',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_to_event_keys() {
		return [
			'tribe-commerce' => '_tribe_tpp_event',
		];
	}

}
