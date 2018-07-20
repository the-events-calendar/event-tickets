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
		parent::__construct();
		$this->default_args = array_merge( $this->default_args, array(
			'post_type'   => $this->attendee_types(),
			'orderby'     => array( 'date', 'title', 'ID' ),
			'post_status' => 'any',
		) );
		$this->schema = array_merge( $this->schema, array(
			'event'             => array( $this, 'filter_by_event' ),
			'ticket'            => array( $this, 'filter_by_ticket' ),
			'event__not_in'     => array( $this, 'filter_by_event_not_in' ),
			'ticket__not_in'    => array( $this, 'filter_by_ticket_not_in' ),
			'optout'            => array( $this, 'filter_by_optout' ),
			'rsvp_status'       => array( $this, 'filter_by_rsvp_status' ),
			'provider'          => array( $this, 'filter_by_provider' ),
			'event_status'      => array( $this, 'filter_by_event_status' ),
			'order_status'      => array( $this, 'filter_by_order_status' ),
			'price_min'         => array( $this, 'filter_by_price_min' ),
			'price_max'         => array( $this, 'filter_by_price_max' ),
			'has_attendee_meta' => array( $this, 'filter_by_attendee_meta_existence' ),
			'checkedin'         => array( $this, 'filter_by_checkedin' ),
		) );
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
	public function attendee_types() {
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
		return Tribe__Repository__Query_Filters::meta_in(
			$this->attendee_to_event_keys(),
			$event_id,
			'by-related-event'
		);
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
	public function attendee_to_event_keys() {
		return array(
			'rsvp'           => '_tribe_rsvp_event',
			'tribe-commerce' => '_tribe_tpp_event',
		);
	}

	/**
	 * Provides arguments to get attendees that are not related to an event.
	 *
	 * @since TBD
	 *
	 * @param int|array $event_id A post ID or an array of post IDs.
	 *
	 * @return array
	 */
	public function filter_by_event_not_in( $event_id ) {
		return Tribe__Repository__Query_Filters::meta_not_in(
			$this->attendee_to_event_keys(),
			$event_id,
			'by-event-not-in'
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
		return Tribe__Repository__Query_Filters::meta_in(
			$this->attendee_to_ticket_keys(),
			$ticket_id,
			'by-ticket'
		);
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
	public function attendee_to_ticket_keys() {
		return array(
			'rsvp'           => '_tribe_rsvp_product',
			'tribe-commerce' => '_tribe_tpp_product',
		);
	}

	/**
	 * Provides arguments to get attendees that are not related to a ticket.
	 *
	 * @since TBD
	 *
	 * @param int|array $ticket_id A ticket post ID or an array of ticket post IDs.
	 *
	 * @return array
	 */
	public function filter_by_ticket_not_in( $ticket_id ) {
		return Tribe__Repository__Query_Filters::meta_not_in(
			$this->attendee_to_ticket_keys(),
			$ticket_id,
			'by-ticket-not-in'
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
		$args = array(
			'meta_query' => array(
				'by-optout-status' => array(),
			),
		);

		switch ( $optout ) {
			case 'any':
				$args = array();
				break;
			case 'no':
				$args = Tribe__Repository__Query_Filters::meta_not_in_or_not_exists(
					$this->attendee_optout_keys(),
					'yes',
					'did-not-optout'
				);
				break;
			case'yes':
				$args = Tribe__Repository__Query_Filters::meta_in(
					$this->attendee_optout_keys(),
					'yes',
					'did-optout'
				);
				break;
		}

		return $args;
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
	public function attendee_optout_keys() {
		return array(
			'rsvp'           => '_tribe_rsvp_attendee_optout',
			'tribe-commerce' => '_tribe_tpp_attendee_optout',
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
		return Tribe__Repository__Query_Filters::meta_in_or_not_exists(
			Tribe__Tickets__RSVP::ATTENDEE_RSVP_KEY,
			$rsvp_status,
			'by-rsvp-status'
		);
	}

	/**
	 * Provides arguments to filter attendees by the ticket provider.
	 *
	 * To avoid lengthy queries we check if a provider specific meta
	 * key relating the Attendee to the event (a post) is set.
	 *
	 * @since TBD
	 *
	 * @param string|array $provider A provider supported slug or an
	 *                               array of supported provider slugs.
	 *
	 * @return array
	 */
	public function filter_by_provider( $provider ) {
		$meta_keys = Tribe__Utils__Array::map_or_discard( (array) $provider, $this->attendee_to_event_keys() );

		return Tribe__Repository__Query_Filters::meta_exists( $meta_keys, 'by-provider' );
	}

	/**
	 * @param $event_status
	 *
	 * @return array
	 */
	public function filter_by_event_status( $event_status ) {
		$this->by( 'meta_related', $this->attendee_to_event_keys(), 'post_status', $event_status );
	}

	public function filter_by_order_status( $order_status ) {
		$this->by( 'meta_related', $this->attendee_to_order_keys(), 'post_status', $order_status );
	}

	/**
	 * Filters Attendees by a minimum paid price.
	 *
	 * @since TBD
	 *
	 * @param int $price_min
	 */
	public function filter_by_price_min( $price_min ) {
		$this->by( 'meta_gte', '_paid_price', (int) $price_min );
	}

	/**
	 * Filters Attendees by a maximum paid price.
	 *
	 * @since TBD
	 *
	 * @param int $price_max
	 */
	public function filter_by_price_max( $price_max ) {
		$this->by( 'meta_lte', '_paid_price', (int) $price_max );
	}

	/**
	 * Filters attendee depending on them having additional
	 * information or not.
	 *
	 * @since TBD
	 *
	 * @param bool $exists
	 */
	public function filter_by_attendee_meta_existence( $exists ) {
		if ( $exists ) {
			$this->by( 'meta_exists', '_tribe_tickets_meta' );
		} else {
			$this->by( 'meta_not_exists', '_tribe_tickets_meta' );
		}
	}

	/**
	 * Filters attendees depending on their checkedin status.
	 *
	 * @since TBD
	 *
	 * @param bool $checkedin
	 *
	 * @return array
	 */
	public function filter_by_checkedin( $checkedin ) {
		$meta_keys = $this->checked_in_keys();

		if ( tribe_is_truthy( $checkedin ) ) {
			return Tribe__Repository__Query_Filters::meta_in( $meta_keys, '1', 'is-checked-in' );
		}

		return Tribe__Repository__Query_Filters::meta_in_or_not_exists( $meta_keys, '0', 'is-not-checked-in' );
	}

	/**
	 * Returns a list of meta keys indicating an attendee checkin status.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	protected function checked_in_keys() {
		return array(
			'rsvp'           => '_tribe_rsvp_checkedin',
			'tribe-commerce' => '_tribe_tpp_checkedin',
		);
	}

	/**
	 * Returns a list of meta keys relating an attendee to the order
	 * that generated it.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	protected function attendee_to_order_keys() {
		return array(
			'tribe-commerce' => '_tribe_tpp_order',
		);
	}
}
