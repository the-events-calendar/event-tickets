<?php
/**
 * Plugin Name: Test Attendees in REST Response
 */

// set always show attendee's to true, to include attendees in response
add_action( 'rest_api_init', function () {
	add_filter( 'tribe_tickets_rest_api_always_show_attendee_data', '__return_true', 99 );
}, 99 );
