<?php

/**
 * Checks whether out new Tickets Emails system should load.
 *
 * In order the function will check the `TEC_TICKETS_EMAILS` constant,
 * the `TEC_TICKETS_EMAILS` environment variable and, finally, the `Manager::$option_enabled` option.
 *
 * @since 5.5.6
 *
 * @return bool Whether Tickets Emails is enabled or not.
 */
function tec_tickets_emails_is_enabled(): bool {
	if ( defined( 'TEC_TICKETS_EMAILS' ) ) {
		return (bool) TEC_TICKETS_EMAILS;
	}

	$env_var = getenv( 'TEC_TICKETS_EMAILS' );
	if ( false !== $env_var ) {
		return (bool) $env_var;
	}

	/**
	 * Allows filtering of the Tickets Emails provider.
	 *
	 * @since 5.5.6
	 *
	 * @param boolean $enabled Determining if Tickets Emails is enabled
	 */
	return apply_filters( 'tec_tickets_emails_is_enabled', false );
}