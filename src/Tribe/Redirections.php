<?php

/**
 * Class Tribe__Tickets__Redirections
 *
 * @since TBD
 */
class Tribe__Tickets__Redirections {

	/**
	 * Hooks to WordPress events when and if needed.
	 *
	 * @since TBD
	 */
	public function hook() {
		if ( ! empty( $_GET['tribe_tickets_redirect_to'] ) ) {
			add_filter( 'registration_redirect', array( $this, 'filter_registration_redirect' ) );
		}
	}

	/**
	 * Filters the redirection URL after a user registration.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function filter_registration_redirect() {
		return rawurldecode( $_GET['tribe_tickets_redirect_to'] );
	}
}