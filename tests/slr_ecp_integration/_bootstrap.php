<?php

// Build the Service façade now to make sure it will be correctly injected in the other Controllers.
use Codeception\Util\Autoload;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Provider as Commerce_Provider;
use TEC\Tickets\Seating\Service\Service_Status;
use TEC\Tickets\Seating\Tables\Layouts;
use TEC\Tickets\Seating\Tables\Maps;
use TEC\Tickets\Seating\Tables\Seat_Types;
use TEC\Tickets\Seating\Tables\Sessions;
use Tribe\Tickets\Promoter\Triggers\Dispatcher;
use TEC\Common\Monolog\Logger;
use TEC\Events\Custom_Tables\V1\Activation as TEC_CT1_Activation;
use TEC\Events\Custom_Tables\V1\Migration\State as CT1_State;
use TEC\Events_Pro\Custom_Tables\V1\Activation as ECP_CT1_Activation;
use TEC\Tickets\Commerce\Module as Commerce_Module;
use TEC\Tickets\Provider;

$tec_support = dirname( __DIR__, 3 ) . '/the-events-calendar/tests/_support';
Autoload::addNamespace( 'Tribe\Events\Test', $tec_support );
$ecp_dir = dirname( __DIR__, 3 ) . '/events-pro';
Autoload::addNamespace( 'Tribe\Events_Pro\Tests', $ecp_dir . '/tests/_support' );

// Let's make sure Commerce is enabled.
putenv( 'TEC_TICKETS_COMMERCE=1' );
putenv( 'TEC_DISABLE_LOGGING=1' );

// Let's  make sure Views v2 are activated if not.
putenv( 'TEC_CUSTOM_TABLES_V1_DISABLED=0' );
$_ENV['TEC_CUSTOM_TABLES_V1_DISABLED'] = 0;
add_filter( 'tec_events_custom_tables_v1_enabled', '__return_true' );
$state = tribe( CT1_State::class );
$state->set( 'phase', CT1_State::PHASE_MIGRATION_COMPLETE );
$state->save();
tribe()->register( TEC\Events\Custom_Tables\V1\Provider::class );
tribe()->register( TEC\Events_Pro\Custom_Tables\V1\Provider::class );
tribe()->register( Provider::class );

// Run the activation routine to ensure the tables will be set up independently of the previous state.
TEC_CT1_Activation::activate();
ECP_CT1_Activation::activate();
tribe()->register( TEC\Events\Custom_Tables\V1\Full_Activation_Provider::class );
// The logger has already been set up at this point, remove all handlers to silence it.
$logger = tribe( Logger::class );
$logger->setHandlers( [] );
// Disable the Promoter trigger to avoid Promoter-related errors.
remove_action( 'tribe_tickets_promoter_trigger', [ tribe( Dispatcher::class ), 'trigger' ] );
// Ensure Ticket Commerce is enabled.
add_filter( 'tec_tickets_commerce_is_enabled', '__return_true', 100 );
tribe()->register( Commerce_Provider::class );
tribe( Commerce_Module::class );


// Ensure `post` is a ticketable post type.
$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
$ticketable[] = 'post';
tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );

tribe()->get( \TEC\Tickets\Seating\Service\Service::class );

tribe()->get( Maps::class )->update();
tribe()->get( Maps::class )->truncate();
tribe()->get( Layouts::class )->update();
tribe()->get( Layouts::class )->truncate();
tribe()->get( Seat_Types::class )->update();
tribe()->get( Seat_Types::class )->truncate();
tribe()->get( Sessions::class )->update();
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

function test_return_ok_service_status( $_status, $backend_base_url ) {
	return new Service_Status( $backend_base_url, Service_Status::OK );
};

function test_add_service_status_ok_callback() {
	add_filter( 'tec_tickets_seating_service_status', 'test_return_ok_service_status', 10, 2 );
}
function test_remove_service_status_ok_callback() {
	remove_filter( 'tec_tickets_seating_service_status', 'test_return_ok_service_status' );
}

// In the contest of tests, assume the Service connection is OK.
test_add_service_status_ok_callback();
