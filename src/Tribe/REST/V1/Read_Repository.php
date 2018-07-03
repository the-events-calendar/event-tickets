<?php

/**
 * Class Tribe__Tickets__REST__V1__Read_Repository
 *
 * @since TBD
 *
 * A minimal ovverride of the default Read Repository to decorate results
 * for the REST API.
 */
class Tribe__Tickets__REST__V1__Read_Repository extends Tribe__Repository__Read {

	/**
	 * {@inheritdoc}
	 */
	public function found() {
		$this->fields( 'ids' );
		$all_ids = parent::all();

		// @todo standardize the meta key used to link ticket -> event to allow for an efficient query

		/**
		 * Make sure we are not returning orphaned tickets.
		 * This implementation is not really efficient but is a
		 * first draft. Meh.
		 */
		$found = 0;
		foreach ( $all_ids as $ticket_id ) {
			if ( ! tribe_events_get_ticket_event( $ticket_id ) ) {
				continue;
			}
			$found ++;
		}

		return $found;
	}

	/**
	 * {@inheritdoc}
	 */
	public function all() {
		$this->fields( 'ids' );
		$all_ids = parent::all();

		// @todo merge this class and Tribe__Tickets__REST__V1__Post_Repository

		/**
		 * For the time being we **another** repository to format
		 * the tickets objects to the REST API format.
		 * If this implementation gets a thumbs-up this class and the
		 * `Tribe__Tickets__REST__V1__Post_Repository` should be merged.
		 */
		/** @var Tribe__Tickets__REST__V1__Post_Repository $repository */
		$repository = tribe( 'tickets.rest-v1.repository' );

		$context = current_user_can( 'read_private_posts' )
			? Tribe__Tickets__REST__V1__Post_Repository::CONTEXT_EDITOR
			: Tribe__Tickets__REST__V1__Post_Repository::CONTEXT_PUBLIC;

		$repository->set_context( $context );

		$all = array();

		foreach ( $all_ids as $ticket_id ) {
			$formatted = $repository->get_ticket_data( $ticket_id );

			if ( $formatted instanceof WP_Error ) {
				continue;
			}

			$all[] = $formatted;
		}

		return $all;
	}
}
