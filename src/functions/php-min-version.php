<?php
/**
 * Compares a given version to the required PHP version
 *
 * Normally we use Constant: PHP_VERSION
 *
 * @param  string  $version  Which PHP version we are checking against
 *
 * @since  TBD
 *
 * @return bool
 */
function tribe_tickets_is_not_min_php_version( $version ) {
	return version_compare( $version, tribe_tickets_get_php_min_version(), '<' );
}

/**
 * Which is our required PHP min version
 *
 * @since  TBD
 *
 * @return string
 */
function tribe_tickets_get_php_min_version() {
	return '5.6';
}

/**
 * Returns the error message when php version min doesnt check
 *
 * @since  TBD
 *
 * @return string
 */
function tribe_tickets_not_php_version_message() {
	return wp_kses_post( sprintf(
			__( '<b>Event Tickets</b> requires PHP %1$s or higher, and the plugin has now disabled itself.', 'event-tickets' ),
			tribe_tickets_get_php_min_version()
		) ) .
		'<br />' .
		esc_html__( 'To allow better control over dates, advanced security improvements and performance gain.', 'event-tickets' ) .
		'<br />' .
		esc_attr( sprintf(
			__( 'Contact your Hosting or your system administrator and ask for this Upgrade to version %1$s of PHP.', 'event-tickets' ),
			tribe_tickets_get_php_min_version()
		) );
}

/**
 * Echos out the error for the PHP min version as a WordPress admin Notice
 *
 * @since  TBD
 *
 * @return void
 */
function tribe_tickets_not_php_version_notice() {
	echo '<div id="message" class="error"><p>' . tribe_events_not_php_version_message() . '</p></div>';
}

/**
 * Loads the Text domain for non-compatible PHP versions
 *
 * @since  TBD
 *
 * @return void
 */
function tribe_tickets_not_php_version_textdomain() {
    load_plugin_textdomain(
		'event-tickets',
		false,
		plugin_basename( EVENT_TICKETS_MAIN_PLUGIN_FILE ) . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR
    );
}
