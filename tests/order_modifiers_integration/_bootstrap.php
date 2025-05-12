<?php

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Provider as Commerce_Provider;
use Tribe\Tickets\Promoter\Triggers\Dispatcher;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Events\Custom_Tables\V1\Activation as TEC_CT1_Activation;
use TEC\Events\Custom_Tables\V1\Provider as TEC_CT1_Provider;
use TEC\Events_Pro\Custom_Tables\V1\Provider as ECP_CT1_Provider;
use TEC\Events_Pro\Custom_Tables\V1\Activation as ECP_CT1_Activation;
use TEC\Tickets\Flexible_Tickets\Custom_Tables;

$tec_support = dirname( __DIR__, 3 ) . '/the-events-calendar/tests/_support';
$ea_support  = dirname( __DIR__, 2 ) . '/common/tests/_support/Traits';
$ecp_support = dirname( __DIR__, 3 ) . '/events-pro/tests/_support';
Codeception\Util\Autoload::addNamespace( 'Tribe\Events\Test', $tec_support );
Codeception\Util\Autoload::addNamespace( 'TEC\Event_Automator\Tests\Traits', $ea_support );
Codeception\Util\Autoload::addNamespace( 'Tribe\Events_Pro\Tests', $ecp_support );

putenv( 'TEC_DISABLE_LOGGING=1' );
putenv( 'TEC_CUSTOM_TABLES_V1_DISABLED=0' );

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

// Drop the custom tables if they exist.
DB::query( DB::prepare( "DROP TABLE IF EXISTS %i", DB::prefix( 'tec_order_modifiers' ) ) );
DB::query( DB::prepare( "DROP TABLE IF EXISTS %i", DB::prefix( 'tec_order_modifiers_meta' ) ) );
DB::query( DB::prepare( "DROP TABLE IF EXISTS %i", DB::prefix( 'tec_order_modifier_relationships' ) ) );

// Ensure `post` is a ticketable post type.
$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
$ticketable[] = 'post';
$ticketable[] = Series_Post_Type::POSTTYPE;
tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );

define( 'JSON_SNAPSHOT_OPTIONS', JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

// Start the posts auto-increment from a high number to make it easier to replace the post IDs in HTML snapshots.
global $wpdb;
DB::query( "ALTER TABLE $wpdb->posts AUTO_INCREMENT = 5096" );
DB::query( DB::prepare( "ALTER TABLE %i AUTO_INCREMENT = 9687", DB::prefix( 'tec_order_modifiers' ) ) );
DB::query( DB::prepare( "ALTER TABLE %i AUTO_INCREMENT = 9687", DB::prefix( 'tec_order_modifiers_meta' ) ) );
DB::query( DB::prepare( "ALTER TABLE %i AUTO_INCREMENT = 9687", DB::prefix( 'tec_order_modifier_relationships' ) ) );

// Disconnect Promoter to avoid license-related notices.
remove_action( 'tribe_tickets_promoter_trigger', [ tribe( Dispatcher::class ), 'trigger' ] );
