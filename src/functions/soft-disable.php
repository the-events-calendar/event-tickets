<?php
/**
 * IMPORTANT NOTE
 *
 * This file uses a global namespace since we will share it on all plugins!
 * This file is only loaded when the plugin does a "soft-disable" due to an incompatibility,
 * to prevent fatal errors where internal functions are called from other plugins.
 */

if ( ! function_exists( 'tribe_tickets_new_views_is_enabled' ) ) {
	/**
	 * Determine whether the new Tickets views are enabled.
	 *
	 * In order: the function will check the constant, the environment variable, the settings UI option, and then
	 * allow filtering.
	 *
	 * @since 5.0.3
	 * @since 5.9.3 Copied to the soft-disable functions.
	 *
	 * @return bool Whether the tickets block views is enabled.
	 */
	function tribe_tickets_new_views_is_enabled() {
		return false;
	}
}

if ( ! function_exists( 'tribe_tickets_rsvp_new_views_is_enabled' ) ) {
	/**
	 * Determine whether new RSVP views are enabled.
	 *
	 * In order the function will check the `TRIBE_TICKETS_RSVP_NEW_VIEWS` constant,
	 * the `TRIBE_TICKETS_RSVP_NEW_VIEWS` environment variable and, finally, the `tickets_rsvp_use_new_views` option.
	 *
	 * @since 4.12.3
	 * @since 5.9.3 Copied to the soft-disable functions.
	 *
	 * @return bool Whether new RSVP views are enabled.
	 */
	function tribe_tickets_rsvp_new_views_is_enabled() {
		return false;
	}
}

if ( ! function_exists( 'tribe_get_ticket_label_plural_lowercase' ) ) {
	/**
	 * Get the lowercase plural version of the Ticket label. May also be used as a verb.
	 *
	 * @since 4.10.9
	 * @since 5.9.3 Copied to the soft-disable functions.
	 *
	 * @param string $context Allows passing additional context to this function's filter, e.g. 'verb' or 'template.php'.
	 *
	 * @return string
	 */
	function tribe_get_ticket_label_plural_lowercase( $context = '' ) {
		/**
		 * Allows customization of the lowercase plural version of the Ticket label.
		 *
		 * @since 4.10.9
		 * @since 5.9.3 Copied to the soft-disable functions.
		 *
		 * @param string $label   The lowercase plural version of the Ticket label, defaults to "tickets".
		 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
		 */
		return apply_filters(
			'tribe_get_ticket_label_plural_lowercase',
			_x( 'tickets', 'lowercase plural label for Tickets', 'event-tickets' ),
			$context
		);
	}
}

if ( ! function_exists( 'tribe_get_rsvp_label_plural' ) ) {
	/**
	 * Get the plural version of the RSVP label. May also be used as a verb.
	 *
	 * @since 4.10.9
	 * @since 5.9.3 Copied to the soft-disable functions.
	 *
	 * @param string $context Allows passing additional context to this function's filter, e.g. 'verb' or 'template.php'.
	 *
	 * @return string
	 */
	function tribe_get_rsvp_label_plural( $context = '' ) {
		/**
		 * Allows customization of the plural version of the RSVP label.
		 *
		 * @since 4.10.9
		 * @since 5.9.3 Copied to the soft-disable functions.
		 *
		 * @param string $label   The plural version of the RSVP label, defaults to "RSVPs".
		 * @param string $context The context in which this string is filtered, e.g. 'verb' or 'template.php'.
		 */
		return apply_filters(
			'tribe_get_rsvp_label_plural',
			_x( 'RSVPs', 'plural label for RSVPs', 'event-tickets' ),
			$context
		);
	}
}
