<?php
/**
 * Event Tickets uninstall script.
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

// Ensure the uninstall script is not called directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Ensure that uninstallation happens deliberately, via an option or a constant.
$option_value   = get_option( 'event_tickets_uninstall', false );
$constant_value = defined( 'EVENT_TICKETS_UNINSTALL' ) && EVENT_TICKETS_UNINSTALL;
if ( ! $option_value && ! $constant_value ) {
	return;
}

/*
 * Run the uninstallation process.
 *
 * Ideally, this sould make use of objects directly. Due to the way the plugin
 * is structured, we are using the global $wpdb object directly for simplicity.
 *
 * @todo Refactor to use objects directly.
 * @todo Run other uninstallation tasks unrelated to Order Modifiers.
 */

/*
 * Disable PHPCS warnings for this part of the file.
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery
 */

/** @global wpdb $wpdb */
global $wpdb;

// Remove the option for this uninstall process, so it must manually be set again.
delete_option( 'event_tickets_uninstall' );

// Remove the options that indicate what version of the DB tables are installed.
delete_option( 'stellar_schema_version_tec-order-modifiers' );
delete_option( 'stellar_schema_version_tec-order-modifiers-meta' );
delete_option( 'stellar_schema_version_tec-order-modifiers-relationships' );

// Remove the options that indicate the previous version of the DB tables.
delete_option( 'stellar_schema_previous_version_tec-order-modifiers' );
delete_option( 'stellar_schema_previous_version_tec-order-modifiers-meta' );
delete_option( 'stellar_schema_previous_version_tec-order-modifiers-relationships' );

// Disable foreign key checks.
$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0' );

// Drop our custom tables.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tec_order_modifiers" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tec_order_modifiers_meta" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tec_order_modifier_relationships" );

// Re-enable foreign key checks.
$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 1' );
