<?php

use TEC\Events\Custom_Tables\V1\Activation;
use TEC\Events\Custom_Tables\V1\Updates\Provider as TEC_Provider;
use Monolog\Logger;

if ( ! tribe()->isBound( TEC_Provider::class ) ) {
	tribe_register_provider( TEC_Provider::class );
}

// Ensure the CT1 code branch is enabled.
putenv( 'TEC_CUSTOM_TABLES_V1_DISABLED=0' );
$_ENV['TEC_CUSTOM_TABLES_V1_DISABLED'] = 0;
add_filter( 'tec_events_custom_tables_v1_enabled', '__return_true' );
// Register the CT1 providers.
tribe()->register( TEC\Events\Custom_Tables\V1\Provider::class );
//tribe()->register( TEC\Events_Pro\Custom_Tables\V1\Provider::class );
// Run the activation routine to ensure the tables will be set up independently of the previous state.
Activation::activate();
tribe()->register( TEC\Events\Custom_Tables\V1\Full_Activation_Provider::class );
//tribe()->register( TEC\Events_Pro\Custom_Tables\V1\Full_Activation_Provider::class );
// The logger has already been set up, remove the current loggers to silence the logger in tests.
$logger = tribe( Logger::class );
$current_handlers = $logger->setHandlers( [] );

