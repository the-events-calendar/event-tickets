<?php

/**
 * Class Tribe__Tickets__Ticket_Repository
 *
 * The basic ticket repository.
 *
 * @since TBD
 */
class Tribe__Tickets__Ticket_Repository extends Tribe__Repository {


	/**
	 * Tribe__Tickets__Ticket_Repository constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		parent::__construct();
		$this->default_args = array(
			'post_type' => $this->ticket_types(),
			'orderby'   => array( 'date', 'ID' ),
		);
		$this->schema = array_merge( $this->schema, array(
			'event'        => array( $this, 'filter_by_event' ),
			'is_available' => array( $this, 'filter_by_availability' ),
			'provider'     => array( $this, 'filter_by_provider' ),
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
	public function ticket_types() {
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
	public function ticket_to_event_keys() {
		return array(
			'rsvp'           => '_tribe_rsvp_for_event',
			'tribe-commerce' => '_tribe_tpp_for_event',
		);
	}

	/**
	 * Sets up the query to filter tickets by availability.
	 *
	 * @since TBD
	 *
	 * @param bool $is_available
	 */
	public function filter_by_availability( $is_available ) {
		$want_available = (bool) $is_available;

		if ( $want_available ) {
			$this->where( 'meta_gt', '_capacity', 0 );
		} else {
			$this->where( 'meta_equals', '_capacity', 0 );
		}
	}

	/**
	 * Sets up the query to filter tickets by provider.
	 *
	 * @since TBD
	 *
	 * @param string|array $provider
	 */
	public function filter_by_provider( $provider ) {
		$providers = Tribe__Utils__Array::list_to_array( $provider );
		$meta_keys = Tribe__Utils__Array::map_or_discard( (array) $providers, $this->ticket_to_event_keys() );

		$this->by( 'meta_exists', $meta_keys );
	}
}
