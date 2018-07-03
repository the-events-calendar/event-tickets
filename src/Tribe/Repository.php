<?php

/**
 * Class Tribe__Tickets__Repository
 *
 * The basic ticket repository.
 *
 * @since TBD
 */
class Tribe__Tickets__Repository extends Tribe__Repository {

	/**
	 * Tribe__Tickets__Repository constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		$this->default_args = array(
			'post_type' => $this->ticket_types(),
			'orderby'   => array( 'date', 'ID' ),
		);

		$this->read_schema = new Tribe__Repository__Schema( array(
			'event' => array( $this, 'filter_by_event' ),
		) );
	}

	/**
	 * Returns an array of the ticket types handled by this repository.
	 *
	 * Extending repository classes should override this to add more ticket types.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	protected function ticket_types() {
		return array( 'tribe_rsvp_tickets', 'tribe_tpp_tickets' );
	}

	/**
	 * Provides arguments to filter tickets by a specific event.
	 *
	 * @since TBD
	 *
	 * @param int|array $event_id
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

		foreach ( $this->ticket_to_event_keys() as $key ) {
			$return['meta_query']['by-related-event'][] = array( 'key' => $key, 'value' => $event_id );
		}

		return $return;
	}

	/**
	 * Returns the list of meta keys relating a Ticket to a Post (Event).
	 *
	 * Extending repository classes should override this to add more keys.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	protected function ticket_to_event_keys() {
		return array(
			'_tribe_rsvp_for_event',
			'_tribe_tpp_for_event',
		);
	}
}
