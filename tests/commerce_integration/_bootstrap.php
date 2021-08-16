<?php

use \TEC\Tickets\Provider as Tickets_Provider;

// Let's  make sure Views v2 are activated if not.
putenv( 'TEC_TICKETS_COMMERCE=1' );
tribe_register_provider( Tickets_Provider::class );

// Let's make sure to set rewrite rules.
global $wp_rewrite;
$wp_rewrite->permalink_structure = '/%postname%/';
$wp_rewrite->rewrite_rules();
