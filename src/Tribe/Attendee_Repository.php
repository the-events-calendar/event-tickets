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
			'event'       => array( $this, 'filter_by_event' ),
			'ticket'      => array( $this, 'filter_by_ticket' ),
			'optout'      => array( $this, 'filter_by_optout' ),
			'rsvp_status' => array( $this, 'filter_by_rsvp_status' ),
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
		return array( 'tribe_rsvp_attendees', 'tribe_tpp_attendees' );
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

		foreach ( $this->attendee_to_ticket_keys() as $key ) {
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

	/**
	 * Provides arguments to filter attendees by their optout status.
	 *
	 * @since TBD
	 *
	 * @param string $optout An optout option, supported 'yes','no','any'.
	 *
	 * @return array
	 */
	public function filter_by_optout( $optout ) {
		$return = array(
			'meta_query' => array(
				'by-optout-status' => array(),
			),
		);

		switch ( $optout ) {
			case 'any':
				$return = array();
				break;
			case 'no':
				foreach ( $this->attendee_optout_keys() as $key ) {
					$return['meta_query']['by-optout-status']['relation'] = 'AND';
					// does not exist or exists and is not 'yes'
					$return['meta_query']['by-optout-status'][] = array(
						'does-not-exist'        => array( 'key' => $key, 'compare' => 'NOT EXISTS' ),
						'relation'              => 'OR',
						'exists-and-is-not-yes' => array( 'key' => $key, 'value' => 'yes', 'compare' => '!=' ),

					);
				}
				break;
			case'yes':
				foreach ( $this->attendee_optout_keys() as $key ) {
					$return['meta_query']['by-optout-status']['relation'] = 'OR';
					// exists and is 'yes'
					$return['meta_query']['by-optout-status'][] = array( 'key' => $key, 'value' => 'yes' );
				}
				break;
		}

		return $return;
	}

	/**
	 * Returns the list of meta keys denoting an Attendee optout choice.
	 *
	 * Extending repository classes should override this to add more keys.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	protected function attendee_optout_keys() {
		return array(
			'_tribe_rsvp_attendee_optout',
			'_tribe_tpp_attendee_optout',
		);
	}

	/**
	 * Provides arguments to filter attendees by a specific RSVP status.
	 *
	 * Mind that we allow tickets not to have an RSVP status at all and
	 * still match. This assumes that all RSVP tickets will have a status
	 * assigned (which is the default behaviour).
	 *
	 * @since TBD
	 *
	 * @param string $rsvp_status
	 *
	 * @return array
	 */
	public function filter_by_rsvp_status( $rsvp_status ) {
		return array(
			'meta_query' => array(
				'by-rsvp-status' => array(
					'exists-and-equals' => array(
						'key'     => Tribe__Tickets__RSVP::ATTENDEE_RSVP_KEY,
						'value'   => $rsvp_status,
						'compare' => '=',
					),
					'relation'          => 'OR',
					'does-not-exist'    => array(
						'key'     => Tribe__Tickets__RSVP::ATTENDEE_RSVP_KEY,
						'compare' => 'NOT EXISTS',
					),
				),
			),
		);
	}
}
