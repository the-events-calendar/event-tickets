<?php

// Before the suite runs, place a mu-plugin that will disable Cron and external API calls.
$wp_root = getenv( 'WP_ROOT_FOLDER' );

if ( empty( $wp_root ) || ! is_dir( $wp_root ) ) {
	throw new RuntimeException( 'WP_ROOT_FOLDER environment variable is not set or is not a valid directory.' );
}

$mu_plugins_dir = $wp_root . '/wp-content/mu-plugins';
$mu_plugin_path = $mu_plugins_dir . '/disable-cron-and-external-apis.php';

if ( ! is_dir( $mu_plugins_dir ) ) {
	codecept_debug( 'Creating mu-plugins directory.' );
	if ( ! mkdir( $mu_plugins_dir, 0777, true ) && is_dir( $mu_plugins_dir ) ) {
		throw new RuntimeException( 'Could not create mu-plugins directory.' );
	}
}

if ( file_exists( $mu_plugin_path ) ) {
	codecept_debug( 'Deleting mu-plugin file.' );
	if ( ! unlink( $mu_plugin_path ) ) {
		throw new RuntimeException( 'Could not delete mu-plugin file.' );
	}
}

if ( ! copy( codecept_data_dir( 'mu-plugins/disable-cron-and-external-apis.php' ), $mu_plugin_path ) ) {
	throw new RuntimeException( 'Could not create mu-plugin file.' );
}
codecept_debug( 'Created mu-plugin file.' );

