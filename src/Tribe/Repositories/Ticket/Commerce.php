<?php

/**
 * The ORM/Repository class for Tribe Commerce (PayPal) tickets.
 *
 * @since TBD
 */
class Tribe__Tickets__Repositories__Ticket__Commerce extends Tribe__Tickets__Ticket_Repository {

	/**
	 * {@inheritdoc}
	 */
	public function ticket_types() {
		return [
			'tribe_tpp_tickets',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function ticket_to_event_keys() {
		return [
			'tribe-commerce' => '_tribe_tpp_for_event',
		];
	}

}
