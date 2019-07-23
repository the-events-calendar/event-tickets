<?php
/**
 * Link to Tickets
 * Included on the Events Single Page after the meta
 * The Message that will link to the Tickets page
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/tickets/orders-link.php
 *
 * @since 4.2
 * @since TBD Renamed template from order-links.php to view-link.php. Updated to not use the now-deprecated third
 *            parameter of `get_description_rsvp_ticket()`. Uses new functions to get singular and plural texts.
 *
 * @version TBD
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * @var Tribe__Tickets__Tickets_View $this
 */

$view     = Tribe__Tickets__Tickets_View::instance();
$event_id = get_the_ID();
$user_id  = get_current_user_id();

/** @var WP_Post $event */
$event = get_post( $event_id );

/** @var WP_Post_Type $post_type */
$post_type = get_post_type_object( $event->post_type );

$is_event_page = class_exists( 'Tribe__Events__Main' ) && Tribe__Events__Main::POSTTYPE === $event->post_type;

$events_label_singular = $post_type->labels->singular_name;

$rsvp_count   = $view->count_rsvp_attendees( $event_id, $user_id );
$ticket_count = $view->count_ticket_attendees( $event_id, $user_id );

$counters = [];

if ( 1 === $rsvp_count ) {
	$counters[] = sprintf( _x( '%d %s', 'RSVP count singular', 'event-tickets' ), $rsvp_count, tribe_get_rsvp_label_singular( basename( __FILE__ ) ) );
}

if ( 1 < $rsvp_count ) {
	$counters[] = sprintf( _x( '%d %s', 'RSVP count plural', 'event-tickets' ), $rsvp_count, tribe_get_rsvp_label_plural( basename( __FILE__ ) ) );
}

if ( 1 === $ticket_count ) {
	$counters[] = sprintf( _x( '%d %s', 'Ticket count singular', 'event-tickets' ), $ticket_count, tribe_get_ticket_label_singular( basename( __FILE__ ) ) );
}

if ( 1 < $ticket_count ) {
	$counters[] = sprintf( _x( '%d %s', 'Ticket count plural', 'event-tickets' ), $ticket_count, tribe_get_ticket_label_plural( basename( __FILE__ ) ) );
}

$link = $view->get_tickets_page_url( $event_id, $is_event_page );

$message  = sprintf( esc_html__( 'You have %s for this %s.', 'event-tickets' ), implode( __( ' and ', 'event-tickets' ), $counters ), $events_label_singular );
$message .= ' <a href="' . esc_url( $link ) . '">' . sprintf( esc_html__( 'View your %s', 'event-tickets' ), $this->get_description_rsvp_ticket( $event_id, $user_id ) ) . '</a>';
?>

<div class="tribe-link-view-attendee">
	<?php echo $message; ?>
</div>
