<?php
// Let's make sure Commerce is enabled.
putenv( 'TEC_TICKETS_COMMERCE=1' );
putenv( 'TEC_DISABLE_LOGGING=1' );

$GLOBALS['wp_tests_options'] = $GLOBALS['wp_tests_options'] ?? [];
$GLOBALS['wp_tests_options']['permalink_structure'] = '/%postname%/';
