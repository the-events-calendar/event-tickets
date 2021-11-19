<?php

use \TEC\Tickets\Provider as Tickets_Provider;

$tec_support = dirname( __DIR__, 3 ) . '/the-events-calendar/tests/_support';
Codeception\Util\Autoload::addNamespace( 'Tribe\Events\Test', $tec_support );

// Let's  make sure Tickets Commerce is active.
putenv( 'TEC_TICKETS_COMMERCE=1' );
tribe_register_provider( Tickets_Provider::class );

// Let's make sure to set rewrite rules.
global $wp_rewrite;
$wp_rewrite->permalink_structure = '/%postname%/';
$wp_rewrite->rewrite_rules();

update_option( 'theme', 'twentytwenty' );
update_option( 'stylesheet', 'twentytwenty' );