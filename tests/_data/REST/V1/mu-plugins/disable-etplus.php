<?php
/**
 * Plugin Name: Test Disable ET Plus
 */

// disable et-plus during this request
add_filter( 'option_active_plugins', function ( $plugins ) {
	$plugins = array_flip( $plugins );

	unset( $plugins['event-tickets-plus/event-tickets-plus.php'] );

	return array_flip( $plugins );
} );