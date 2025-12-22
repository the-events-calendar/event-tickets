<?php
/**
 * Bootstrap file for RSVP V2 integration tests.
 *
 * This suite tests the V2 (TC-based) RSVP implementation.
 *
 * @since TBD
 */

use TEC\Tickets\RSVP\Controller;

// Enable RSVP V2 for all tests in this suite.
add_filter( 'tec_tickets_rsvp_version', static fn() => Controller::VERSION_2 );
