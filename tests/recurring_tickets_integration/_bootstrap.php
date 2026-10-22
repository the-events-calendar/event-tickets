<?php
/**
 * Bootstraps the Recurring Event Tickets suite: TEC and ECP with custom tables active, and Tickets Commerce.
 */

use Codeception\Util\Autoload;
use TEC\Events\Custom_Tables\V1\Activation as TEC_CT1_Activation;
use TEC\Events\Custom_Tables\V1\Provider as TEC_CT1_Provider;
use TEC\Events_Pro\Custom_Tables\V1\Activation as ECP_CT1_Activation;
use TEC\Events_Pro\Custom_Tables\V1\Provider as ECP_CT1_Provider;
use TEC\Tickets\Commerce\Module as Commerce_Module;
use TEC\Tickets\Commerce\Provider as Commerce_Provider;
use Tribe\Tickets\Promoter\Triggers\Dispatcher;

$plugins_dir = dirname( __DIR__, 3 );
Autoload::addNamespace( 'Tribe\Events\Test', $plugins_dir . '/the-events-calendar/tests/_support' );
Autoload::addNamespace( 'Tribe\Events_Pro\Tests', $plugins_dir . '/events-pro/tests/_support' );

// Every recurring event sits in a Series: make Series ticketable, so Series Passes sell next to recurring event tickets.
$ticketable_post_types   = (array) tribe_get_option( 'ticket-enabled-post-types', [] );
$ticketable_post_types[] = \TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type::POSTTYPE;
tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable_post_types ) ) );

putenv( 'TEC_TICKETS_COMMERCE=1' );
putenv( 'TEC_DISABLE_LOGGING=1' );
putenv( 'TEC_CUSTOM_TABLES_V1_DISABLED=0' );
$_ENV['TEC_CUSTOM_TABLES_V1_DISABLED'] = 0;

tribe()->register( TEC_CT1_Provider::class );
TEC_CT1_Activation::init();
tribe()->register( ECP_CT1_Provider::class );
ECP_CT1_Activation::init();

if ( empty( tribe()->getVar( 'ct1_fully_activated' ) ) ) {
	throw new Exception( 'TEC CT1 is not active' );
}

add_filter( 'tec_tickets_commerce_is_enabled', '__return_true', 100 );
tribe()->register( Commerce_Provider::class );
// Tickets Commerce registers its post types and statuses on `init`, which has run by now.
tribe( Commerce_Provider::class )->run_init_hooks();
tribe( Commerce_Module::class );

// Disconnect Promoter to avoid license-related notices.
remove_action( 'tribe_tickets_promoter_trigger', [ tribe( Dispatcher::class ), 'trigger' ] );

tec_tickets_tests_fake_transactions_enable();

// The WordPress installer does not reset custom tables: start each run without rows a previous run committed.
( new \TEC\Tickets\Recurring_Tickets\Tables\Tickets() )->empty_table();
