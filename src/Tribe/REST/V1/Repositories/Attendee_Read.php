<?php

/**
 * Class Tribe__Tickets__REST__V1__Repositories__Attendee_Read
 *
 * @since TBD
 *
 * A minimal ovverride of the default Read Repository to decorate results
 * for the REST API.
 */
class Tribe__Tickets__REST__V1__Repositories__Attendee_Read extends Tribe__Repository__Read {
	// @todo merge this class and Tribe__Tickets__REST__V1__Post_Repository

	public function by_primary_key( $primary_key ) {
		/** @var Tribe__Tickets__REST__V1__Messages $messages */
		$messages = tribe( 'tickets.rest-v1.messages' );

		$post = get_post( $primary_key );

		if ( ! $post instanceof WP_Post ) {
			return new WP_Error(
				'attendee-not-found',
				$messages->get_message( 'attendee-not-found' ),
				array( 'status' => 404 )
			);
		}

		$pto = get_post_type_object( get_post_type( $primary_key ) );

		if ( ! $pto instanceof WP_Post_Type ) {
			// if we're here and we're trying to fetch a non-existing attendee
			// then this is an internal error
			return new WP_Error(
				'error-attendee-post',
				$messages->get_message( 'error-attendee-post' ),
				array( 'status' => 500 )
			);
		}

		$can_read_private_posts = current_user_can( $pto->cap->read_post, $primary_key );

		if (
			! $can_read_private_posts
			&& 'publish' !== get_post_status( $primary_key )
		) {
			return new WP_Error(
				'attendee-not-accessible',
				$messages->get_message( 'attendee-not-accessible' ),
				array( 'status' => 401 )
			);
		}

		/**
		 * If the user cannot read private posts then let's only show
		 * attendees that did not opt out.
		 */
		if ( ! $can_read_private_posts ) {
			$this->by( 'optout', 'no' );
			/**
			 * If it's an RSVP ticket then only return attendees that are going
			 * if the user cannot read private posts; will not apply if not an
			 * RSVP ticket.
			 */
			$this->by( 'rsvp_status', 'yes' );
		} else {
			$this->by( 'optout', 'any' );
		}

		$found = parent::by_primary_key( $primary_key );

		if ( null === $found ) {
			return new WP_Error(
				'attendee-not-accessible',
				$messages->get_message( 'attendee-not-accessible' ),
				array( 'status' => 401 )
			);
		}

		return $found;
	}

	/**
	 * Returns the number of posts found matching the query.
	 *
	 * This method overrides the parent implementation to limit
	 * the results if the user cannot read private posts.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	public function found() {
		if ( ! current_user_can( 'read_private_posts' ) ) {
			$this->by( 'optout', 'no' )
			     ->by( 'post_status', 'publish' )
			     ->by( 'rsvp_status', 'yes' );
		}

		return parent::found();
	}

	/**
	 * Returns the number of posts found matching the query in the current page.
	 *
	 * This method overrides the parent implementation to limit
	 * the results if the user cannot read private posts.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	public function count() {
		if ( ! current_user_can( 'read_private_posts' ) ) {
			$this->by( 'optout', 'no' )
			     ->by( 'post_status', 'publish' )
			     ->by( 'rsvp_status', 'yes' );
		}

		return parent::count();
	}

	/**
	 * Returns the attendee in the REST API format.
	 *
	 * @since TBD
	 *
	 * @param int|WP_Post $id
	 *
	 * @return array|null The attendee information in the REST API format or
	 *                    `null` if the attendee is invalid.
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

		$formatted = $repository->get_attendee_data( $id );

		return $formatted instanceof WP_Error ? null : $formatted;
	}
}
