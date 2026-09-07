<?php
/**
 * Configuration file for RSVP V2 integration tests.
 *
 * @since TBD
 */

putenv( 'TEC_DISABLE_LOGGING=1' );

$GLOBALS['wp_tests_options']                       = $GLOBALS['wp_tests_options'] ?? [];
$GLOBALS['wp_tests_options']['permalink_structure'] = '/%postname%/';
