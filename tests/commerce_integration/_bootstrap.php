<?php

use \TEC\Tickets\Commerce\Provider as Commerce_Provider;
use Tribe\Tickets\Promoter\Triggers\Dispatcher;

$tec_support = dirname( __DIR__, 3 ) . '/the-events-calendar/tests/_support';
Codeception\Util\Autoload::addNamespace( 'Tribe\Events\Test', $tec_support );

// Let's make sure Commerce is enabled.
putenv( 'TEC_TICKETS_COMMERCE=1' );
putenv( 'TEC_DISABLE_LOGGING=1' );
tribe_register_provider( Commerce_Provider::class );

// Let's make sure to set rewrite rules.
global $wp_rewrite;
$wp_rewrite->permalink_structure = '/%postname%/';
$wp_rewrite->rewrite_rules();

update_option( 'theme', 'twentytwenty' );
update_option( 'stylesheet', 'twentytwenty' );

// Start the posts auto-increment from a high number to make it easier to replace the post IDs in HTML snapshots.
global $wpdb;
$wpdb->query( "ALTER TABLE $wpdb->posts AUTO_INCREMENT = 5096" );

tec_tickets_tests_fake_transactions_enable();
