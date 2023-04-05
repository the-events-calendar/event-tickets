<?php
// Ensure TEC CT1 Feature is active.
use Codeception\Events;
use TEC\Common\StellarWP\DB\DB;
use TEC\Events\Custom_Tables\V1\Activation;
use \TEC\Events\Custom_Tables\V1\Tables\Events as Events_Table;
use TEC\Events\Custom_Tables\V1\Tables\Occurrences as Occurrences_Table;
use TEC\Events_Pro\Custom_Tables\V1\Tables\Series_Relationships as Series_Relationships_Table;
use TEC\Tickets\Flexible_Tickets\Custom_Tables;
use function tad\WPBrowser\addListener;

putenv( 'TEC_CUSTOM_TABLES_V1_DISABLED=1' );
$_ENV['TEC_CUSTOM_TABLES_V1_DISABLED'] = 1;

Activation::init();

$ct1_active = tribe()->getVar( 'ct1_fully_activated' );

if ( empty( $ct1_active ) ) {
	throw new Exception( 'TEC CT1 is not active' );
}

// Let's make sure to start from a clean slate, custom-tables wise.
$custom_tables = tribe( Custom_Tables::class );
$custom_tables->drop_tables();
$custom_tables->register_tables();

// Start the posts auto-increment from a high number to make it easier to replace the post IDs in HTML snapshots.
global $wpdb;
DB::query( "ALTER TABLE $wpdb->posts AUTO_INCREMENT = 5096" );

// After each test truncate Event Ticket and TEC CT1 custom tables.
addListener( Events::TEST_AFTER, function () use ( $custom_tables ) {
	$custom_tables->truncate_tables();

	DB::query( 'SET FOREIGN_KEY_CHECKS=0' );
	foreach (
		[
			Events_Table::table_name(),
			Occurrences_Table::table_name(),
			Series_Relationships_Table::table_name()
		] as $table_name
	) {
		DB::query( "TRUNCATE TABLE {$table_name}" );
	}
	DB::query( 'SET FOREIGN_KEY_CHECKS=0' );
} );