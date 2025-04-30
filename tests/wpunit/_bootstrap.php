<?php
// Here you can initialize variables that will be available to your tests

use Tribe\Tickets\Promoter\Triggers\Dispatcher;

$tec_support = dirname( __DIR__, 3 ) . '/the-events-calendar/tests/_support';
Codeception\Util\Autoload::addNamespace( 'Tribe\Events\Test', $tec_support );

/*
 * We're using this one function from PRO in the tests; to avoid requiring and loading PRO
 * we stub it here.
 */
if ( ! function_exists( 'tribe_is_recurring_event' ) ) {
	function tribe_is_recurring_event( $post_id ) {
		return apply_filters( 'tribe_is_recurring_event', false, $post_id );
	}
}

update_option( 'theme', 'twentytwenty' );
update_option( 'stylesheet', 'twentytwenty' );

// Disable Promoter.
remove_action( 'tribe_tickets_promoter_trigger', [ tribe( Dispatcher::class ), 'trigger' ] );

// Start the posts auto-increment from a high number to make it easier to replace the post IDs in HTML snapshots.
global $wpdb;
$wpdb->query( "ALTER TABLE $wpdb->posts AUTO_INCREMENT = 5096" );
