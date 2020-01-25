<?php

use Tribe\Events\Views\V2\Service_Provider as Events_Provider;
use Tribe\Tickets\Events\Views\V2\Service_Provider as Tickets_Provider;

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

/**
 * Codeception will regenerate snapshots on `--debug`, while the `spatie/snapshot-assertions`
 * library will do the same on `--update-snapshots`.
 * Since Codeception has strict check on the CLI arguments appending `--update-snapshots` to the
 * `vendor/bin/codecept run` command will throw an error.
 * We handle that intention here.
 */
if ( in_array( '--debug', $_SERVER['argv'], true ) ) {
	$_SERVER['argv'][] = '--update-snapshots';
}

