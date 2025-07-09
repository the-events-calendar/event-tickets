<?php

// Ensure the CT1 code branch is enabled.
use Codeception\Util\Autoload;
use TEC\Common\Monolog\Logger;
use TEC\Events\Custom_Tables\V1\Activation as TEC_CT1_Activation;
use TEC\Events\Custom_Tables\V1\Migration\State as CT1_State;
use TEC\Events_Pro\Custom_Tables\V1\Activation as ECP_CT1_Activation;
use TEC\Tickets\Commerce\Module as Commerce_Module;
use TEC\Tickets\Commerce\Provider as Commerce_Provider;
use TEC\Tickets\Provider;
use Tribe\Tickets\Promoter\Triggers\Dispatcher;

$tec_support = dirname( __DIR__, 3 ) . '/the-events-calendar/tests/_support';
Codeception\Util\Autoload::addNamespace( 'Tribe\Events\Test', $tec_support );
$ecp_dir = dirname( __DIR__, 3 ) . '/events-pro';
Autoload::addNamespace( 'Tribe\Events_Pro\Tests', $ecp_dir . '/tests/_support' );

// Let's  make sure Views v2 are activated if not.
putenv( 'TEC_TICKETS_COMMERCE=1' );
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

tec_tickets_tests_fake_transactions_enable();
