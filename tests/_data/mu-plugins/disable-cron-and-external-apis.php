<?php
/**
 * Plugin Name: Disable Cron and External APIs
 */

// Disable Cron.
if ( ! defined( 'DISABLE_WP_CRON' ) ) {
	define( 'DISABLE_WP_CRON', true );
}

// Disable external API calls.
if ( ! defined( 'WP_HTTP_BLOCK_EXTERNAL' ) ) {
	define( 'WP_HTTP_BLOCK_EXTERNAL', true );
}

// Disable auto-updates.
if ( ! defined( 'AUTOMATIC_UPDATER_DISABLED' ) ) {
	define( 'AUTOMATIC_UPDATER_DISABLED', true );
}

// Disable fatal error handler.
if ( ! defined( 'WP_DISABLE_FATAL_ERROR_HANDLER' ) ) {
	define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );
}

if ( ! defined( 'TRIBE_NO_FREEMIUS' ) ) {
	define( 'TRIBE_NO_FREEMIUS', true );
}

add_filter( 'tec_admin_update_page_bypass', '__return_true' );
