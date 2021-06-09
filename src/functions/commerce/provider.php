<?php

use TEC\Tickets\Settings;

/**
 * Checks whether out new Tickets system should load.
 *
 * In order the function will check the `TEC_TICKETS_COMMERCE` constant,
 * the `TEC_TICKETS_COMMERCE` environment variable and, finally, the `Manager::$option_enabled` option.
 *
 * @since TBD
 *
 * @return bool Whether Tickets Commerce is enabled or not.
 */
function tec_tickets_commerce_is_enabled() {
	if ( defined( 'TEC_TICKETS_COMMERCE' ) ) {
		return (bool) TEC_TICKETS_COMMERCE;
	}

	$env_var = getenv( 'TEC_TICKETS_COMMERCE' );
	if ( false !== $env_var ) {
		return (bool) $env_var;
	}

	$enabled = (bool) tribe_get_option( Settings::$tickets_commerce_enabled, false );

	/**
	 * Allows filtering of the Tickets Commerce provider, doing so will render
	 * the methods and classes no longer load-able so keep that in mind.
	 *
     * @since TBD
	 *
	 * @param boolean $enabled Determining if Tickets Commerce is enabled..
	 */
	return apply_filters( 'tec_tickets_commerce_is_enabled', $enabled );
}