<?php
/**
 * Block: Attendees List
 *
 * Link to Tickets
 * Included on the Events Single Page after the meta
 * the Message that Will link to the Tickets Page
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/attendees/description.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @since TBD Updated to not use the now-deprecated third parameter of `get_description_rsvp_ticket()`
 *
 * @version TBD
 */


if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$view      = Tribe__Tickets__Tickets_View::instance();
$event_id  = $this->get( 'post_id' );
$event     = get_post( $event_id );
$post_type = get_post_type_object( $event->post_type );
$user_id   = get_current_user_id();

$is_event_page = class_exists( 'Tribe__Events__Main' ) && Tribe__Events__Main::POSTTYPE === $event->post_type;

$events_label_singular = $post_type->labels->singular_name;
$counters              = array();
$rsvp_count            = $view->count_rsvp_attendees( $event_id, $user_id );
$ticket_count          = $view->count_ticket_attendees( $event_id, $user_id );

if ( 0 !== $rsvp_count ) {
	$counters[] = sprintf( _n( '%d %s', '%d %s', $rsvp_count, 'event-tickets' ), $rsvp_count, _nx( 'RSVP', 'RSVPs', $rsvp_count, 'Singular and plural texts for RSVP(s)', 'event-tickets' ) );
}

if ( 0 !== $ticket_count ) {
	$counters[] = sprintf( _n( '%d %s', '%d %s', $ticket_count, 'event-tickets' ), $ticket_count, _nx( 'Ticket', 'Tickets', $ticket_count, 'Singular and plural texts for Ticket(s)', 'event-tickets' ) );
}

if ( empty( $counters ) ) {
	return false;
}

$link = $view->get_tickets_page_url( $event_id, $is_event_page );

$message = sprintf( esc_html__( 'You have %s for this %s.', 'event-tickets' ), implode( __( ' and ', 'event-tickets' ), $counters ), $events_label_singular );
?>

<div class="tribe-link-view-attendee">
	<?php echo $message ?>
	<a href="<?php echo esc_url( $link ) ?>"><?php echo sprintf( esc_html__( 'View your %s', 'event-tickets' ), $view->get_description_rsvp_ticket( $event_id, $user_id ) ) ?></a>
</div>
