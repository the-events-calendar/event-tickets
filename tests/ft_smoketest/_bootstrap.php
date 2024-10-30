<?php

// Before the suite runs, place a mu-plugin that will disable Cron and external API calls.
use Codeception\Events;
use function tad\WPBrowser\addListener;

$wp_root = getenv( 'WP_ROOT_FOLDER' );

if ( empty( $wp_root ) || ! is_dir( $wp_root ) ) {
	throw new RuntimeException( 'WP_ROOT_FOLDER environment variable is not set or is not a valid directory.' );
}

$mu_plugins_dir = $wp_root . '/wp-content/mu-plugins';
$mu_plugin_path = $mu_plugins_dir . '/ft-smoketest.php';

if ( ! is_dir( $mu_plugins_dir ) ) {
	codecept_debug( 'Creating mu-plugins directory.' );
	if ( ! mkdir( $mu_plugins_dir, 0777, true ) && is_dir( $mu_plugins_dir ) ) {
		throw new RuntimeException( 'Could not create mu-plugins directory.' );
	}
}

if ( file_exists( $mu_plugin_path ) ) {
	codecept_debug( 'Deleting ft-smoketest.php mu-plugin file.' );
	if ( ! unlink( $mu_plugin_path ) ) {
		throw new RuntimeException( 'Could not delete mu-plugin file.' );
	}
}

if ( ! copy( codecept_data_dir( 'mu-plugins/ft-smoketest.php' ), $mu_plugin_path ) ) {
	throw new RuntimeException( 'Could not place ft-smoketest.php mu-plugin file.' );
}
codecept_debug( 'Placed ft-smoketest mu-plugin file.' );

// Remove the plugin after the suite is done.
addListener( Events::SUITE_AFTER, function () use ( $mu_plugin_path ) {
	if ( ! unlink( $mu_plugin_path ) ) {
		throw new RuntimeException( 'Could not delete ft-smoketest mu-plugin file.' );
	}
} );

