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
	 * @var array
	 */
	protected $default_args = array(
		'post_type' => array( 'tribe_rsvp_tickets', 'tribe_tpp_tickets' ),
		'orderby'   => array( 'date', 'ID' ),
	);

	/**
	 * Tribe__Tickets__Repository constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		$this->read_schema = new Tribe__Repository__Schema( array(
			'event' => array( $this, 'filter_by_event' ),
		) );
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
		return array(
			'meta_query' => array(
				'by-related-event' => array(
					'relation' => 'OR',
					array(
						'key'   => '_tribe_rsvp_for_event',
						'value' => $event_id,
					),
					array(
						'key'   => '_tribe_tpp_for_event',
						'value' => $event_id,
					),
				),
			),
		);
	}
}