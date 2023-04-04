<?php
// Ensure TEC CT1 Feature is active.
use Codeception\Events;
use TEC\Events\Custom_Tables\V1\Activation;
use TEC\Tickets\Flexible_Tickets\Custom_Tables;
use function tad\WPBrowser\addListener;

putenv( 'TEC_CUSTOM_TABLES_V1_DISABLED=1' );
$_ENV['TEC_CUSTOM_TABLES_V1_DISABLED'] = 1;

Activation::init();

$ct1_active = tribe()->getVar( 'ct1_fully_activated' );

if ( empty( $ct1_active ) ) {
	throw new \Exception( 'TEC CT1 is not active' );
}

require_once __DIR__ . '/Controller_Test_Case.php';

// Let's make sure to start from a clean slate, custom-tables wise.
$custom_tables = tribe( Custom_Tables::class );
$custom_tables->drop_tables();
$custom_tables->register_tables();

addListener( Events::TEST_AFTER, function () use ( $custom_tables ) {
	$custom_tables->truncate_tables();
} );