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

	// The version in which Tickets Emails was introduced.
	$should_default_to_on = ! tribe_installed_before( 'Tribe__Tickets__Main', '5.6.0-dev' );

	// Check for settings UI option.
	$enabled = (bool) tribe_get_option( TEC\Tickets\Emails\Admin\Settings::$option_enabled, $should_default_to_on );

	/**
	 * Allows filtering of the Tickets Emails provider.
	 *
	 * @since 5.5.6
	 *
	 * @param boolean $enabled Determining if Tickets Emails is enabled
	 */
	return apply_filters( 'tec_tickets_emails_is_enabled', $enabled );
}
