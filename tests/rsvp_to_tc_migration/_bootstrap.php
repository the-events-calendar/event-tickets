<?php
/**
 * Bootstrap file for RSVP to TC Migration tests.
 *
 * This suite tests the migration of legacy V1 RSVPs to Tickets Commerce (V2).
 *
 * @since TBD
 */

use TEC\Common\StellarWP\DB\DB;

// Start the posts auto-increment from a high number to make it easier to track IDs.
DB::query( DB::prepare( 'ALTER TABLE %i AUTO_INCREMENT = 5096', DB::prefix( 'posts' ) ) );

// Ensure `post` and `page` are ticketable post types.
$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
$ticketable[] = 'post';
$ticketable[] = 'page';
tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );

// Enable fake transactions for testing.
tec_tickets_tests_fake_transactions_enable();
