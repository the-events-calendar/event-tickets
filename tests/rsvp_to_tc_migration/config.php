<?php
/**
 * Configuration file for RSVP to TC Migration tests.
 *
 * @since 5.30.0
 */

putenv( 'TEC_DISABLE_LOGGING=1' );

$GLOBALS['wp_tests_options']                        = $GLOBALS['wp_tests_options'] ?? [];
$GLOBALS['wp_tests_options']['permalink_structure'] = '/%postname%/';
