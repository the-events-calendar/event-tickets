<?php
/**
 * Link to Tickets
 * Included on the Events Single Page after the meta
 * The Message that will link to the Tickets page
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/tickets/orders-link.php
 *
 * @since   4.2
 * @since   4.10.8 Renamed template from order-links.php to view-link.php. Updated to not use the now-deprecated third
 *                 parameter of `get_description_rsvp_ticket()`.
 * @since   4.10.9  Use customizable ticket name functions.
 * @since   4.11.0 Made template more like new blocks-based template in terms of logic.
 * @since 4.12.1 Account for empty post type object, such as if post type got disabled. Fix typo in sprintf placeholders.
 *
 * @version 4.12.1
 *
 * @var Tribe__Tickets__Tickets_View $this
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$view      = Tribe__Tickets__Tickets_View::instance();
$event_id  = get_the_ID();
$event     = get_post( $event_id );
$post_type = get_post_type_object( $event->post_type );
$user_id   = get_current_user_id();

$is_event_page = class_exists( 'Tribe__Events__Main' ) && Tribe__Events__Main::POSTTYPE === $event->post_type;

$post_type_singular = $post_type ? $post_type->labels->singular_name : _x( 'Post', 'fallback post type singular name', 'event-tickets' );
$counters           = [];
$rsvp_count         = $view->count_rsvp_attendees( $event_id, $user_id );
$ticket_count       = $view->count_ticket_attendees( $event_id, $user_id );

if ( 1 === $rsvp_count ) {
	// Translators: 1: the number one, 2: singular RSVP label.
	$counters[] = sprintf( _x( '%1$d %2$s', 'RSVP count singular', 'event-tickets' ), $rsvp_count, tribe_get_rsvp_label_singular( basename( __FILE__ ) ) );
} elseif ( 1 < $rsvp_count ) {
	// Translators: 1: the plural number of RSVPs, 2: plural RSVP label.
	$counters[] = sprintf( _x( '%1$d %2$s', 'RSVP count plural', 'event-tickets' ), $rsvp_count, tribe_get_rsvp_label_plural( basename( __FILE__ ) ) );
}

if ( 1 === $ticket_count ) {
	// Translators: 1: the number one, 2: singular Ticket label.
	$counters[] = sprintf( _x( '%1$d %2$s', 'Ticket count singular', 'event-tickets' ), $ticket_count, tribe_get_ticket_label_singular( basename( __FILE__ ) ) );
} elseif ( 1 < $ticket_count ) {
	// Translators: 1: the plural number of Tickets, 2: plural Ticket label.
	$counters[] = sprintf( _x( '%1$d %2$s', 'Ticket count plural', 'event-tickets' ), $ticket_count, tribe_get_ticket_label_plural( basename( __FILE__ ) ) );
}

if ( empty( $counters ) ) {
	return false;
}

$link = $view->get_tickets_page_url( $event_id, $is_event_page );

// Translators: 1: number of RSVPs and/or Tickets with accompanying ticket type text, 2: post type label.
$message = esc_html( sprintf( __( 'You have %1s for this %2s.', 'event-tickets' ), implode( _x( ' and ', 'separator if there are both RSVPs and Tickets', 'event-tickets' ), $counters ), $post_type_singular ) );
?>

<div class="tribe-link-view-attendee">
	<?php echo $message ?>
	<a href="<?php echo esc_url( $link ) ?>"><?php echo sprintf( esc_html__( 'View your %s', 'event-tickets' ), $view->get_description_rsvp_ticket( $event_id, $user_id ) ) ?></a>
</div>