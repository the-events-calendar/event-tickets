<?php

use Tribe\Events\Views\V2\Service_Provider as Events_Provider;
use Tribe\Events\Tickets\Views\V2\Service_Provider as Tickets_Provider;
use Codeception\Util\Autoload;

$tec_tests_dir = __DIR__ . '/../../../the-events-calendar/tests';

if ( ! is_dir( $tec_tests_dir ) ) {
	throw new RuntimeException( "Tickets tests require The Events Calendar installed in a \"the-events-calendar\" sibling folder: {$tec_tests_dir} not found." );
}

$tec_support_dir = $tec_tests_dir . '/_support';

/**
 * Manually include the file here as we might need it in a suite configuration file.
 * Suites fire before the autoload below is used so we need to gather what we need without
 * autoloading.
 */
require_once $tec_support_dir . '/Helper/TribeDb.php';

Codeception\Util\Autoload::addNamespace( 'Tribe\Events\Test', $tec_support_dir );

// Let's  make sure Views v2 are activated if not.
putenv( 'TRIBE_EVENTS_V2_VIEWS=1' );
tribe_register_provider( Events_Provider::class );
tribe_register_provider( Tickets_Provider::class );

// Let's make sure to set rewrite rules.
global $wp_rewrite;
$wp_rewrite->permalink_structure = '/%postname%/';
$wp_rewrite->rewrite_rules();

