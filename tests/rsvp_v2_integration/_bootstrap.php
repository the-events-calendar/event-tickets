<?php
/**
 * Bootstrap file for RSVP V2 integration tests.
 *
 * This suite tests the V2 (TC-based) RSVP implementation.
 *
 * @since TBD
 */

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\RSVP\Controller;

define( 'JSON_SNAPSHOT_OPTIONS', JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

// Enable RSVP V2 for all tests in this suite.
add_filter( 'tec_tickets_rsvp_version', static fn() => Controller::VERSION_2 );

// Start the posts auto-increment from a high number to make it easier to replace the post IDs in HTML snapshots.
DB::query( DB::prepare( 'ALTER TABLE %i AUTO_INCREMENT = 5096', DB::prefix( 'posts' ) ) );

// Ensure `post` and `page` are ticketable post types.
$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
$ticketable[] = 'post';
$ticketable[] = 'page';
tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );

// Disable experimental endpoint acknowledgement requirement for testing.
add_filter( 'tec_rest_experimental_endpoint', '__return_false' );

// Enable fake transactions for testing.
tec_tickets_tests_fake_transactions_enable();
