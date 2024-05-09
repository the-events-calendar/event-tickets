<?php
/********************************************************************************
 *
 *
 * IMPORTANT NOTE
 *
 * This file uses a global namespace since we will share it on all plugins!
 * This file is only loaded when the plugin does a "soft-disable" due to an incompatibility,
 * to prevent fatal errors where internal functions are called from other plugins.
 *
 *
 ********************************************************************************/

 if ( ! function_exists( 'tribe_tickets_new_views_is_enabled' ) ) {
	function tribe_tickets_new_views_is_enabled() {
		return false;
	}
}

if ( ! function_exists( 'tribe_tickets_rsvp_new_views_is_enabled' ) ) {
	function tribe_tickets_rsvp_new_views_is_enabled() {
		return false;
	}
}

if ( ! function_exists( 'tribe_get_ticket_label_plural_lowercase' ) ) {
	function tribe_get_ticket_label_plural_lowercase() {
		return false;
	}
}

if ( ! function_exists( 'tribe_get_rsvp_label_plural' ) ) {
	function tribe_get_rsvp_label_plural() {
		return false;
	}
}
