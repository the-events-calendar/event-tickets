<?php

/**
 * Class Tribe__Tickets__Redirections
 *
 * @since 4.7
 */
class Tribe__Tickets__Redirections {

	/**
	 * Hooks to WordPress events when and if needed.
	 *
	 * @since 4.7
	 */
	public function hook() {
		if ( ! empty( $_GET['tribe_tickets_redirect_to'] ) ) {
			add_filter( 'registration_redirect', array( $this, 'filter_registration_redirect' ) );
		}
	}

	/**
	 * Filters the redirection URL after a user registration.
	 *
	 * @since 4.7
	 *
	 * @return string
	 */
	public function filter_registration_redirect() {
		return rawurldecode( $_GET['tribe_tickets_redirect_to'] );
	}
}