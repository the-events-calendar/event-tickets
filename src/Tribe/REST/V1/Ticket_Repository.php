<?php

/**
 * Class Tribe__Tickets__REST__V1__Post_Repository
 *
 * The base Ticket object repository, a decorator of the base one.
 *
 * @since TBD
 */
class Tribe__Tickets__REST__V1__Ticket_Repository extends Tribe__Tickets__Ticket_Repository {

	/**
	 * {@inheritdoc}
	 */
	public function found() {
		$query = $this->build_query();
		$query->set( 'fields', 'ids' );
		$query->set( 'posts_per_page', - 1 );
		$query->set( 'no_found_rows', true );
		$all_ids = $query->get_posts();

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
	public function count() {
		$query = $this->build_query();
		$query->set( 'fields', 'ids' );
		$query->set( 'no_found_rows', true );
		$all_ids = $query->get_posts();

		// @todo standardize the meta key used to link ticket -> event to allow for an efficient query

		/**
		 * Make sure we are not returning orphaned tickets.
		 * This implementation is not really efficient but is a
		 * first draft. Meh.
		 */
		$count = 0;
		foreach ( $all_ids as $ticket_id ) {
			if ( ! tribe_events_get_ticket_event( $ticket_id ) ) {
				continue;
			}
			$count ++;
		}

		return $count;
	}

	/**
	 * Returns the ticket in the REST API format.
	 *
	 * @since TBD
	 *
	 * @param int|WP_Post $id
	 *
	 * @return array|null The ticket information in the REST API format or
	 *                    `null` if the ticket is invalid.
	 */
	protected function format_item( $id ) {
		/**
		 * For the time being we use **another** repository to format
		 * the tickets objects to the REST API format.
		 * If this implementation gets a thumbs-up this class and the
		 * `Tribe__Tickets__REST__V1__Post_Repository` should be merged.
		 */
		/** @var Tribe__Tickets__REST__V1__Post_Repository $repository */
		$repository = tribe( 'tickets.rest-v1.repository' );

		$formatted = $repository->get_ticket_data( $id );

		return $formatted instanceof WP_Error ? null : $formatted;
	}

}
