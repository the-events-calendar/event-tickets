<?php

use Codeception\Events;
use Codeception\Util\Autoload;
use TEC\Common\StellarWP\DB\DB;
use TEC\Events\Custom_Tables\V1\Activation as TEC_CT1_Activation;
use TEC\Events\Custom_Tables\V1\Provider as TEC_CT1_Provider;
use TEC\Events_Pro\Custom_Tables\V1\Provider as ECP_CT1_Provider;
use TEC\Events\Custom_Tables\V1\Tables\Events as Events_Table;
use TEC\Events\Custom_Tables\V1\Tables\Occurrences as Occurrences_Table;
use TEC\Events_Pro\Custom_Tables\V1\Activation as ECP_CT1_Activation;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Events_Pro\Custom_Tables\V1\Tables\Series_Relationships as Series_Relationships_Table;
use TEC\Tickets\Commerce\Module as Commerce_Module;
use TEC\Tickets\Commerce\Provider as Commerce_Provider;
use TEC\Tickets\Flexible_Tickets\Custom_Tables;
use function tad\WPBrowser\addListener;

// Load utils from ECP
$ecp_dir = dirname( __DIR__, 3 ) . '/events-pro';
Autoload::addNamespace( 'Tribe\Events_Pro\Tests', $ecp_dir . '/tests/_support' );

// Ensure TEC CT1 Feature is active.
putenv( 'TEC_CUSTOM_TABLES_V1_DISABLED=0' );
$_ENV['TEC_CUSTOM_TABLES_V1_DISABLED'] = 0;

// Ensure Series are ticket-able, in most scenarios this is what we want.
$ticketable_post_types   = (array) tribe_get_option( 'ticket-enabled-post-types', [] );
$ticketable_post_types[] = Series_Post_Type::POSTTYPE;
tribe_update_option( 'ticket-enabled-post-types', $ticketable_post_types );

// Activate CT1
tribe()->register( TEC_CT1_Provider::class );
TEC_CT1_Activation::init();
tribe()->register( ECP_CT1_Provider::class );
ECP_CT1_Activation::init();

if ( empty( tribe()->getVar( 'ct1_fully_activated' ) ) ) {
	throw new Exception( 'TEC CT1 is not active' );
}

// Let's make sure to start from a clean slate, custom-tables wise.
$custom_tables = tribe( Custom_Tables::class );
$custom_tables->drop_tables();
$custom_tables->register_tables();

// Ensure Ticket Commerce is enabled.
if ( ! tec_tickets_commerce_is_enabled() ) {
	add_filter( 'tec_tickets_commerce_is_enabled', '__return_true', 100 );
	tribe()->register( Commerce_Provider::class );
}
tribe( Commerce_Module::class );

// Start the posts auto-increment from a high number to make it easier to replace the post IDs in HTML snapshots.
global $wpdb;
DB::query( "ALTER TABLE $wpdb->posts AUTO_INCREMENT = 5096" );

// After each test truncate Event Ticket and TEC CT1 custom tables.
addListener( Events::TEST_BEFORE, function () use ( $custom_tables ) {
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

// Deactivate logging.
global $wp_filter;
$wp_filter['tribe_log'] = new WP_Hook();