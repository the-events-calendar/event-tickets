<?php

// Build the Service façade now to make sure it will be correctly injected in the other Controllers.
use Codeception\Util\Autoload;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Provider as Commerce_Provider;
use TEC\Tickets\Seating\Commerce\Controller as Seating_Commerce_Controller;
use TEC\Tickets\Seating\Service\Service_Status;
use TEC\Tickets\Seating\Tables\Layouts;
use TEC\Tickets\Seating\Tables\Maps;
use TEC\Tickets\Seating\Tables\Seat_Types;
use TEC\Tickets\Seating\Tables\Sessions;
use Tribe\Tickets\Promoter\Triggers\Dispatcher;

$tec_support = dirname( __DIR__, 3 ) . '/the-events-calendar/tests/_support';
Codeception\Util\Autoload::addNamespace( 'Tribe\Events\Test', $tec_support );

putenv( 'TEC_DISABLE_LOGGING=1' );
// Ensure `post` is a ticketable post type.
$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
$ticketable[] = 'post';
tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );

tribe()->get( \TEC\Tickets\Seating\Service\Service::class );

tribe()->get( Maps::class )->update();
tribe()->get( Maps::class )->empty_table();
tribe()->get( Layouts::class )->update();
tribe()->get( Layouts::class )->empty_table();
tribe()->get( Seat_Types::class )->update();
tribe()->get( Seat_Types::class )->empty_table();
tribe()->get( Sessions::class )->update();
tribe()->get( Sessions::class )->empty_table();

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
}

function test_return_seating_license_key(): string {
	return 'valid-license-key';
}

function test_add_seating_license_key_callback(): void {
	add_filter( 'stellarwp/uplink/tec/license_get_key', 'test_return_seating_license_key' );
}

function test_remove_seating_license_key_callback(): void {
	remove_filter( 'stellarwp/uplink/tec/license_get_key', 'test_return_seating_license_key' );
}

function test_add_service_status_ok_callback() {
	add_filter( 'tec_tickets_seating_service_status', 'test_return_ok_service_status', 10, 2 );
}

function test_remove_service_status_ok_callback() {
	remove_filter( 'tec_tickets_seating_service_status', 'test_return_ok_service_status' );
}

// In the contest of tests, assume the Service connection is OK.
test_add_service_status_ok_callback();
test_add_seating_license_key_callback();

// The Seating Commerce controller might not be registered yet: do it now.
tribe_register_provider( Seating_Commerce_Controller::class );

tec_tickets_tests_fake_transactions_enable();
