<?php
/**
 * Plugin Name: Disable Cron and External APIs
 */

// Disable Cron.
use TEC\Common\Monolog\Logger;

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

// Self-invoking function to avoid polluting the global namespace.
( static function () {
	$collected_logs = [];

	$print_debug_data = static function (): void {
		$data = apply_filters( 'tec_debug_data', [] );

		echo '<script type="application/json" id="tec-debug-data">'
		     . json_encode( $data, JSON_PRETTY_PRINT )
		     . '</script>';
	};

	$collect_logs = static function ( $level = Logger::DEBUG, $message = '', $context = null ) use ( &$collected_logs ): void {
		if ( ! isset( $collected_logs[ $level ] ) ) {
			$collected_logs[ $level ] = [];
		}
		$collected_logs[ $level ][] = [ 'message' => $message, 'context' => $context ];
	};

	$add_logs_to_debug_data = static function ( array $debug_data ) use ( &$collected_logs ): array {
		$debug_data['logs'] = $collected_logs;

		return $debug_data;
	};

	add_action( 'wp_footer', $print_debug_data );
	add_action( 'admin_footer', $print_debug_data );
	add_action( 'tribe_log', $collect_logs, 10, 3 );
	add_filter( 'tec_debug_data', $add_logs_to_debug_data );
} )();
