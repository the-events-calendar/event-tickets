<?php

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Provider as Commerce_Provider;
use Tribe\Tickets\Promoter\Triggers\Dispatcher;


$tec_support = dirname( __DIR__, 3 ) . '/the-events-calendar/tests/_support';
$ea_support  = dirname( __DIR__, 2 ) . '/common/tests/_support/Traits';
Codeception\Util\Autoload::addNamespace( 'Tribe\Events\Test', $tec_support );
Codeception\Util\Autoload::addNamespace( 'TEC\Event_Automator\Tests\Traits', $ea_support );


putenv( 'TEC_TICKETS_COMMERCE=1' );
putenv( 'TEC_DISABLE_LOGGING=1' );
tribe_register_provider( Commerce_Provider::class );

// Ensure `post` is a ticketable post type.
$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
$ticketable[] = 'post';
tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );

define( 'JSON_SNAPSHOT_OPTIONS', JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

// Start the posts auto-increment from a high number to make it easier to replace the post IDs in HTML snapshots.
global $wpdb;
DB::query( "ALTER TABLE $wpdb->posts AUTO_INCREMENT = 5096" );

// Disconnect Promoter to avoid license-related notices.
remove_action( 'tribe_tickets_promoter_trigger', [ tribe( Dispatcher::class ), 'trigger' ] );
