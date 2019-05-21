<?php

/**
 * The ORM/Repository class for RSVP attendees.
 *
 * @since 4.10.6
 */
class Tribe__Tickets__Repositories__Attendee__RSVP extends Tribe__Tickets__Attendee_Repository {

	/**
	 * {@inheritdoc}
	 */
	public function attendee_types() {
		$types = parent::attendee_types();

		$types = [
			'rsvp' => $types['rsvp'],
		];

		return $types;
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_to_event_keys() {
		$keys = parent::attendee_to_event_keys();

		$keys = [
			'rsvp' => $keys['rsvp'],
		];

		return $keys;
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_to_ticket_keys() {
		$keys = parent::attendee_to_ticket_keys();

		$keys = [
			'rsvp' => $keys['rsvp'],
		];

		return $keys;
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_to_order_keys() {
		$keys = parent::attendee_to_order_keys();

		$keys = [
			'rsvp' => $keys['rsvp'],
		];

		return $keys;
	}

	/**
	 * {@inheritdoc}
	 */
	public function purchaser_name_keys() {
		$keys = parent::purchaser_name_keys();

		$keys = [
			'rsvp' => $keys['rsvp'],
		];

		return $keys;
	}

	/**
	 * {@inheritdoc}
	 */
	public function purchaser_email_keys() {
		$keys = parent::purchaser_email_keys();

		$keys = [
			'rsvp' => $keys['rsvp'],
		];

		return $keys;
	}

	/**
	 * {@inheritdoc}
	 */
	public function security_code_keys() {
		$keys = parent::security_code_keys();

		$keys = [
			'rsvp' => $keys['rsvp'],
		];

		return $keys;
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_optout_keys() {
		$keys = parent::attendee_optout_keys();

		$keys = [
			'rsvp' => $keys['rsvp'],
		];

		return $keys;
	}

	/**
	 * {@inheritdoc}
	 */
	public function checked_in_keys() {
		$keys = parent::checked_in_keys();

		$keys = [
			'rsvp' => $keys['rsvp'],
		];

		return $keys;
	}

}
