<?php
use TEC\Common\StellarWP\DB\DB;

define( 'JSON_SNAPSHOT_OPTIONS', JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

DB::query( DB::prepare( 'ALTER TABLE %i AUTO_INCREMENT = 76945', DB::prefix( 'posts' ) ) );

// Ensure `post` and `page` are ticketable post types.
$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
$ticketable[] = 'post';
$ticketable[] = 'page';
tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );

add_filter( 'tec_rest_experimental_endpoint', '__return_false' );

// Enable fake transactions for testing
tec_tickets_tests_fake_transactions_enable();
