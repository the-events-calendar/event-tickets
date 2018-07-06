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
