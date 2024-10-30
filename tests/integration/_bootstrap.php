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
}
tribe( Commerce_Module::class );
