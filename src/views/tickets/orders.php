<?php
/**
 * Edit Event Tickets
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/tickets/orders.php
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

$is_event_page = class_exists( 'Tribe__Events__Main' ) && Tribe__Events__Main::POSTTYPE === $event->post_type ? true : false;
?>

<div id="tribe-events-content" class="tribe-events-single">
	<p class="tribe-back">
		<a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>">
			<?php printf( '&laquo; ' . esc_html__( 'View %s', 'event-tickets' ), $post_type->labels->singular_name ); ?>
		</a>
	</p>

	<?php if ( $is_event_page ): ?>
	<?php the_title( '<h1 class="tribe-events-single-event-title">', '</h1>' ); ?>

	<div class="tribe-events-schedule tribe-clearfix">
		<?php echo tribe_events_event_schedule_details( $event_id, '<h2>', '</h2>' ); ?>
		<?php if ( tribe_get_cost() ) : ?>
			<span class="tribe-events-cost"><?php echo tribe_get_cost( null, true ) ?></span>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<!-- Notices -->
	<?php tribe_the_notices() ?>

	<form method="post">

	<?php tribe_tickets_get_template_part( 'tickets/orders-rsvp' ); ?>

	<?php
	/**
	 * Fires before the process tickets submission button is rendered
	 */
	do_action( 'tribe_tickets_orders_before_submit' );
	?>

	<?php if ( $view->has_rsvp_attendees( $event_id ) || $view->has_ticket_attendees( $event_id ) ): ?>
		<div class="tribe-submit-tickets-form">
			<button type="submit" name="process-tickets" value="1" class="button alt"><?php echo sprintf( esc_html__( 'Update %s', 'event-tickets' ), $view->get_description_rsvp_ticket( $event_id, get_current_user_id(), true ) ); ?></button>
		</div>
	<?php endif; ?>

	</form>

</div><!-- #tribe-events-content -->
