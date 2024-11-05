<?php
/**
 * Event Tickets uninstall script.
 *
 * @since TBD
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
 * For example, the custom tables should be dropped by using \TEC\Tickets\Commerce\Order_Modifiers\Controller::drop_tables().
 *
 * @todo Refactor to use objects directly.
 * @todo Run other uninstallation tasks unrelated to Order Modifiers.
 */

/*
 * Disable PHPCS warnings for this part of the file.
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery
 */

global $wpdb;

// Drop our custom tables.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tec_order_modifiers" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tec_order_modifiers_meta" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tec_order_modifier_relationships" );
