<?php
// Here you can initialize variables that will be available to your tests

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
