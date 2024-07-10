<?php

// Build the Service façade now to make sure it will be correctly injected in the other Controllers.
use Codeception\Util\Autoload;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Provider as Commerce_Provider;
use TEC\Tickets\Seating\Tables\Layouts;
use TEC\Tickets\Seating\Tables\Maps;
use TEC\Tickets\Seating\Tables\Seat_Types;
use TEC\Tickets\Seating\Tables\Sessions;
use Tribe\Tickets\Promoter\Triggers\Dispatcher;

$tec_support = dirname( __DIR__, 3 ) . '/the-events-calendar/tests/_support';
Codeception\Util\Autoload::addNamespace( 'Tribe\Events\Test', $tec_support );

// Let's make sure Commerce is enabled.
putenv( 'TEC_TICKETS_COMMERCE=1' );
putenv( 'TEC_DISABLE_LOGGING=1' );
tribe_register_provider( Commerce_Provider::class );

tribe()->get( \TEC\Tickets\Seating\Service\Service::class );

tribe()->get( Maps::class )->truncate();
tribe()->get( Layouts::class )->truncate();
tribe()->get( Seat_Types::class )->truncate();
tribe()->get( Sessions::class )->truncate();

define( 'JSON_SNAPSHOT_OPTIONS', JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

Autoload::addNamespace( 'TEC\Tickets\Seating\Tests\Integration', __DIR__ );

// Start the posts auto-increment from a high number to make it easier to replace the post IDs in HTML snapshots.
global $wpdb;
DB::query( "ALTER TABLE $wpdb->posts AUTO_INCREMENT = 5096" );

// Disconnect Promoter to avoid license-related notices.
remove_action( 'tribe_tickets_promoter_trigger', [ tribe( Dispatcher::class ), 'trigger' ] );

if ( ! defined( 'SECURE_AUTH_KEY' ) ) {
	define( 'SECURE_AUTH_KEY', 'HG&R(f/h#K5{n:,4@swG~1Fc*aQGd@?T,T+zlTR)IsF5ET{SvvwBkI|zq6E}xjxy' );
}
