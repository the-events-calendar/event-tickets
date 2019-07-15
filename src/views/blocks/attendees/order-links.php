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

$ticket_type = $this->get( 'type' );
$is_ticket = 'ticket' === $ticket_type;
$is_rsvp = 'RSVP' === $ticket_type;

$is_event_page = class_exists( 'Tribe__Events__Main' ) && Tribe__Events__Main::POSTTYPE === $event->post_type ? true : false;

$events_label_singular = $post_type->labels->singular_name;
$counters              = array();
$rsvp_count            = $view->count_rsvp_attendees( $event_id, $user_id );
$ticket_count          = $view->count_ticket_attendees( $event_id, $user_id );

$has_rsvps = $is_rsvp && 0 !== $rsvp_count;
$has_tickets = $is_ticket && 0 !== $ticket_count;

if ( $has_rsvps ) {
	$counters[] = sprintf( _n( '%d %s', '%d %ss', $rsvp_count, 'event-tickets' ), $rsvp_count, $ticket_type );
}

if ( $has_tickets ) {
	$counters[] = sprintf( _n( '%d %s', '%d %ss', $ticket_count, 'event-tickets' ), $ticket_count, $ticket_type );
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
