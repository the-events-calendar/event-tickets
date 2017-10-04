<?php
/**
 * PayPal Tickets Success content
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/tickets/tpp-success.php
 *
 * @package TribeEventsCalendar
 * @version TBD
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
$view = Tribe__Tickets__Tickets_View::instance();
$event_id = get_the_ID();
$event = get_post( $event_id );
$post_type = get_post_type_object( $event->post_type );

$is_event_page = class_exists( 'Tribe__Events__Main' ) && Tribe__Events__Main::POSTTYPE === $event->post_type ? true : false;
?>

<div id="tribe-events-content" class="tribe-events-single">
	Success!
</div><!-- #tribe-events-content -->
