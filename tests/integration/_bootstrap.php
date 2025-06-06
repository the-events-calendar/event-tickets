<?php

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Module as Commerce_Module;
use TEC\Tickets\Commerce\Provider as Commerce_Provider;
use Tribe\Tickets\Promoter\Triggers\Dispatcher;

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
