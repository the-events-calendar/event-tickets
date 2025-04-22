<?php

require_once __DIR__ . '/Test_Case.php';

use Codeception\Util\Autoload;
use TEC\Events\Custom_Tables\V1\Activation as TEC_Activation;
use TEC\Events\Custom_Tables\V1\Tables\Events;
use TEC\Events\Custom_Tables\V1\Tables\Occurrences;
use TEC\Events_Pro\Custom_Tables\V1\Activation;
use TEC\Events_Pro\Custom_Tables\V1\Tables\Series_Relationships;
use function tad\WPBrowser\addListener;
use function tad\WPBrowser\importDumpWithMysqlBin;

$tec_dir = dirname( __DIR__, 3 ) . '/the-events-calendar';
Autoload::addNamespace( 'TEC\Events', $tec_dir . '/src/Events' );
$ecp_dir = dirname( __DIR__, 3 ) . '/events-pro';
Autoload::addNamespace( 'TEC\Events_Pro\Custom_Tables\V1', $ecp_dir . '/tests/_support/ct1' );
Autoload::addNamespace( 'TEC\Events_Pro', $ecp_dir . '/src/Events_Pro' );
Autoload::addNamespace( 'Tribe\Events_Pro\Tests', $ecp_dir . '/tests/_support' );
Autoload::addNamespace( 'TEC\Tickets\Tests\FT_CT1_Migration', __DIR__ );

// If the `uopz` extension is installed, let's make sure to `exit` and `die` will work properly.
if ( function_exists( 'uopz_allow_exit' ) ) {
	uopz_allow_exit( true );
}

// Since we do not drop and import the DB dump after each test, let's do a lighter cleanup here.
$clean_after_test = static function () {
	global $wpdb;
	$last_error        = $wpdb->last_error;
	$occurrences_table = Occurrences::table_name( true );
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$occurrences_table}'" ) === $occurrences_table ) {
		$wpdb->query( "TRUNCATE TABLE {$occurrences_table}" );
		if ( ! empty( $wpdb->last_error ) && $wpdb->last_error !== $last_error ) {
			throw new RuntimeException( "There was an issue cleaning the Occurrences table: $wpdb->last_error" );
		}
	}
	$events_table = Events::table_name( true );
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$events_table}'" ) === $events_table ) {
		$wpdb->query( "DELETE FROM {$events_table}" );// To skip FOREIGN KEY constraints.
		if ( ! empty( $wpdb->last_error ) && $wpdb->last_error !== $last_error ) {
			throw new RuntimeException( "There was an issue cleaning the Events table: $wpdb->last_error" );
		}
	}
	$series_relationships_table = Series_Relationships::table_name( true );
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$series_relationships_table}'" ) === $series_relationships_table ) {
		$wpdb->query( "TRUNCATE TABLE {$series_relationships_table}" );// To skip FOREIGN KEY constraints.
		if ( ! empty( $wpdb->last_error ) && $wpdb->last_error !== $last_error ) {
			throw new RuntimeException( "There was an issue cleaning the Series Relationships table: $wpdb->last_error" );
		}
	}
	$wpdb->query( "TRUNCATE TABLE $wpdb->postmeta" );
	if ( ! empty( $wpdb->last_error ) && $wpdb->last_error !== $last_error ) {
		throw new RuntimeException( "There was an issue cleaning the postmeta table: $wpdb->last_error" );
	}

	$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0;' );
	$wpdb->query( "TRUNCATE TABLE $wpdb->posts" );
	$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 1;' );
	if ( ! empty( $wpdb->last_error ) && $wpdb->last_error !== $last_error ) {
		throw new RuntimeException( "There was an issue cleaning the posts table: $wpdb->last_error" );
	}

	// Drop and re-import the options table.
	$wpdb->query( "TRUNCATE TABLE $wpdb->options" );
	if ( ! empty( $wpdb->last_error ) && $wpdb->last_error !== $last_error ) {
		throw new RuntimeException( "There was an issue cleaning the options table: $wpdb->last_error" );
	}

	// Leverage the `options` only dump.
	importDumpWithMysqlBin( __DIR__ . '/../_data/ft_ct1_migration/options_dump.sql', DB_NAME, DB_USER, DB_PASSWORD, DB_HOST );

	// Empty all Action Scheduler tables.
	$all_tables = (array) $wpdb->get_col( 'SHOW TABLES' );
	foreach ( $all_tables as $table ) {
		if ( 0 === strpos( $table, $wpdb->prefix . 'actionscheduler_' ) ) {
			$wpdb->query( 'TRUNCATE TABLE ' . $table );
		}
	}
};
addListener( Codeception\Events::TEST_AFTER, $clean_after_test );

// Set environment variables early
putenv( 'TEC_CUSTOM_TABLES_V1_DISABLED=0' );
$_ENV['TEC_CUSTOM_TABLES_V1_DISABLED'] = 0;

// Move the CT1 setup to plugins_loaded action
addListener( Codeception\Events::SUITE_BEFORE, static function () {
	add_action('plugins_loaded', static function() {
		add_filter( 'tec_events_custom_tables_v1_enabled', '__return_true' );
		tribe()->register( TEC\Events\Custom_Tables\V1\Provider::class );
		tribe()->register( TEC\Events_Pro\Custom_Tables\V1\Provider::class );
		tribe()->register( TEC\Events_Pro\Custom_Tables\V1\Models\Provider::class );

		delete_transient( 'tec_custom_tables_v1_initialized' );
		wp_cache_delete( 'tec_custom_tables_v1_initialized' );

		// Run the activation routines
		TEC_Activation::init();
		Activation::init();
		do_action( 'tec_events_custom_tables_v1_load_action_scheduler' );

		global $wpdb;
		// Increase the posts table auto increment value
		if ( $wpdb->query( "ALTER TABLE $wpdb->posts AUTO_INCREMENT = 12389" ) === false ) {
			throw new RuntimeException( 'Failed to set the posts table auto increment value.' );
		}
	}, 20); // Run after TEC and ET are loaded
});
