<?php

/**
 * Class Tribe__Tickets__Attendee_Repository
 *
 * The basic Attendee repository.
 *
 * @since TBD
 */
class Tribe__Tickets__Attendee_Repository extends Tribe__Repository {

	/**
	 * Tribe__Tickets__Attendee_Repository constructor.
	 */
	public function __construct() {
		$this->default_args = array(
			'post_type' => $this->attendee_types(),
			'orderby'   => array( 'date', 'title', 'ID' ),
		);

		$this->read_schema = array(
			'event'  => array( $this, 'filter_by_event' ),
			'ticket' => array( $this, 'filter_by_ticket' ),
		);
	}

	/**
	 * Returns an array of the attendee types handled by this repository.
	 *
	 * Extending repository classes should override this to add more attendee types.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	protected function attendee_types() {
		return array( 'tribe_rsvp_tickets', 'tribe_tpp_tickets' );
	}

	/**
	 * Provides arguments to filter attendees by a specific event.
	 *
	 * @since TBD
	 *
	 * @param int|array $event_id A post ID or an array of post IDs.
	 *
	 * @return array
	 */
	public function filter_by_event( $event_id ) {
		$return = array(
			'meta_query' => array(
				'by-related-event' => array(
					'relation' => 'OR',
				),
			),
		);

		foreach ( $this->attendee_to_event_keys() as $key ) {
			$return['meta_query']['by-related-event'][] = array( 'key' => $key, 'value' => $event_id );
		}

		return $return;
	}

	/**
	 * Returns the list of meta keys relating an Attendee to a Post (Event).
	 *
	 * Extending repository classes should override this to add more keys.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	protected function attendee_to_event_keys() {
		return array(
			'_tribe_rsvp_for_event',
			'_tribe_tpp_for_event',
		);
	}

	/**
	 * Provides arguments to filter attendees by a specific ticket.
	 *
	 * @since TBD
	 *
	 * @param int|array $ticket_id A ticket post ID or an array of ticket post IDs.
	 *
	 * @return array
	 */
	public function filter_by_ticket( $ticket_id ) {
		$return = array(
			'meta_query' => array(
				'by-ticket' => array(
					'relation' => 'OR',
				),
			),
		);

		foreach ( $this->attendee_to_event_keys() as $key ) {
			$return['meta_query']['by-ticket'][] = array( 'key' => $key, 'value' => $ticket_id );
		}

		return $return;
	}

	/**
	 * Returns the list of meta keys relating an Attendee to a Ticket.
	 *
	 * Extending repository classes should override this to add more keys.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	protected function attendee_to_ticket_keys() {
		return array(
			'_tribe_rsvp_product',
			'_tribe_tpp_product',
		);
	}
}
