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
		$types = parent::attendee_types();

		$types = [
			'tribe-commerce' => $types['tribe-commerce'],
		];

		return $types;
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_to_event_keys() {
		$keys = parent::attendee_to_event_keys();

		$keys = [
			'tribe-commerce' => $keys['tribe-commerce'],
		];

		return $keys;
	}

}
