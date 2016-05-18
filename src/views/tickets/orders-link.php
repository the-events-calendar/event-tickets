<?php
/**
 * Link to Tickets
 * Included on the Events Single Page after the meta
 * the Message that Will link to the Tickets Page
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/tickets/orders-link.php
 *
 * @package TribeEventsCalendar
 * @version 4.2
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$view = Tribe__Tickets__Tickets_View::instance();
$event_id = get_the_ID();
$event = get_post( $event_id );
$post_type = get_post_type_object( $event->post_type );

$user_id = get_current_user_id();

$is_event_page = class_exists( 'Tribe__Events__Main' ) && Tribe__Events__Main::POSTTYPE === $event->post_type ? true : false;

$events_label_singular = $post_type->labels->singular_name;
$counters = array();
$rsvp_count = $view->count_rsvp_attendees( $event_id, $user_id );
$ticket_count = $view->count_ticket_attendees( $event_id, $user_id );

if ( 0 !== $rsvp_count ) {
	$counters[] = sprintf( _n( '%d RSVP', '%d RSVPs', $rsvp_count, 'event-tickets' ), $rsvp_count );
}

if ( 0 !== $ticket_count ) {
	$counters[] = sprintf( _n( '%d Ticket', '%d Tickets', $ticket_count, 'event-tickets' ), $ticket_count );
}

if ( $is_event_page ) {
	$link = trailingslashit( get_permalink( $event_id ) ) . 'tickets';
} else {
	$link = home_url( '/tickets/' . $event_id );
}
$message = sprintf( esc_html__( 'You have %s for this %s.', 'event-tickets' ), implode( __( ' and ', 'event-tickets' ), $counters ), $events_label_singular );
$message .= ' <a href="' . esc_url( $link ) . '">' . sprintf( esc_html__( 'View your %s', 'event-tickets' ), $this->get_description_rsvp_ticket( $event_id, $user_id, true ) ) . '</a>';
?>

<div class="tribe-link-tickets-message">
	<?php echo $message; ?>
</div>
