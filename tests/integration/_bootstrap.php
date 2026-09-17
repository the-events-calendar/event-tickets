<?php

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Module as Commerce_Module;
use TEC\Tickets\Commerce\Provider as Commerce_Provider;
use Tribe\Tickets\Promoter\Triggers\Dispatcher;

/*
 * Turn a `tribe_exit()` into a failing test rather than a dead run.
 *
 * Codeception's shutdown handler cannot tell a deliberate exit from a crash: it finds no fatal
 * error, prints "COMMAND DID NOT FINISH PROPERLY." and exits 255, with no test name and no stack.
 * Registered below the default priority so the tests that already install their own handler keep
 * winning.
 */
add_filter(
	'tribe_exit',
	static function () {
		return static function ( $status = '' ) {
			throw new RuntimeException( 'tribe_exit() was called during a test: ' . var_export( $status, true ) );
		};
	},
	5
);

/*
 * Activating a plugin leaves a redirect flag behind, and the guided setup screens consume it on the
 * first admin page load to send the user to onboarding. In tests that page load is whichever test
 * first calls set_current_screen() as a capable user, and the redirect ends in tribe_exit().
 */
foreach ( [ '_tribe_events_activation_redirect', '_tec_tickets_activation_redirect', '_tec_tickets_wizard_redirect' ] as $tec_activation_redirect ) {
	delete_transient( $tec_activation_redirect );
}

// Start the posts auto-increment from a high number to make it easier to replace the post IDs in HTML snapshots.
global $wpdb;
DB::query( "ALTER TABLE $wpdb->posts AUTO_INCREMENT = 5096" );

// Disconnect Promoter to avoid license-related notices.
remove_action( 'tribe_tickets_promoter_trigger', [ tribe( Dispatcher::class ), 'trigger' ] );

// Ensure Ticket Commerce is enabled.
if ( ! tec_tickets_commerce_is_enabled() ) {
	add_filter( 'tec_tickets_commerce_is_enabled', '__return_true', 100 );
	tribe()->register( Commerce_Provider::class );
	$commerce_provider = tribe( 'tickets.commerce.provider' );
	$commerce_provider->run_init_hooks();
}
tribe( Commerce_Module::class );

tec_tickets_tests_fake_transactions_enable();

// Populate the gateway order ID for the gateways during tests.
tec_tickets_tests_enable_gateway_id_generation();
