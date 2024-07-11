<?php
/**
 * Attendees functions.
 */

/**
 * Checks whether our new Tickets Attendees page should load.
 *
 * In order the function will check the `TEC_TICKETS_ATTENDEES_PAGE` constant,
 * the `TEC_TICKETS_ATTENDEES_PAGE` environment variable,
 *
 * @since 5.10.0
 *
 * @return bool Whether "Attendees" page is enabled or not.
 */
function tec_tickets_attendees_page_is_enabled(): bool {
	if ( defined( 'TEC_TICKETS_ATTENDEES_PAGE' ) ) {
		return (bool) TEC_TICKETS_ATTENDEES_PAGE;
	}

	$env_var = getenv( 'TEC_TICKETS_ATTENDEES_PAGE' );
	if ( false !== $env_var ) {
		return (bool) $env_var;
	}

	/**
	 * Allows filtering of the Attendees page provider.
	 *
	 * @since 5.10.0
	 *
	 * @param boolean $enabled Determining if the "Attendees" page is enabled.
	 */
	return apply_filters( 'tec_tickets_attendees_page_is_enabled', true );
}
