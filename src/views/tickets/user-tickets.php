<?php
/**
 * Edit Event Tickets
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/tickets/user-tickets.php
 *
 * @package TribeEventsCalendar
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$events_label_singular = tribe_get_event_label_singular();
$event_id = get_the_ID();
$user_id = get_current_user_id();
$ticket_orders = Tribe__Tickets__Tickets_View::get_event_attendees_by_order( $event_id, $user_id );
$rsvp_orders = Tribe__Tickets__Tickets_View::get_event_rsvp_attendees( $event_id, $user_id );

?>

<div id="tribe-events-content" class="tribe-events-single">

	<p class="tribe-events-back">
		<a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>">
			<?php printf( '&laquo; ' . esc_html__( 'View %s', 'event-tickets' ), $events_label_singular ); ?>
		</a>
	</p>

	<!-- Notices -->
	<?php tribe_the_notices() ?>

	<?php the_title( '<h1 class="tribe-events-single-event-title">', '</h1>' ); ?>

	<div class="tribe-events-schedule tribe-clearfix">
		<?php echo tribe_events_event_schedule_details( $event_id, '<h2>', '</h2>' ); ?>
		<?php if ( tribe_get_cost() ) : ?>
			<span class="tribe-events-divider">|</span>
			<span class="tribe-events-cost"><?php echo tribe_get_cost( null, true ) ?></span>
		<?php endif; ?>
	</div>

	<form method="post">
	<?php if ( ! empty( $rsvp_orders ) ): ?>
		<h2><?php echo sprintf( esc_html__( 'RSVP on this %s', 'event-tickets' ), $events_label_singular ); ?></h2>
		<ul class="tribe-edit-rsvp">
		<?php foreach ( $rsvp_orders as $i => $attendee ): ?>
			<?php $key = $attendee['order_id']; ?>
			<li class="tribe-rsvp-item" id="attendee-<?php echo $attendee['order_id']; ?>">
				<p>
					<span class="tribe-rsvp-answer">
						<?php esc_html_e( 'RSVP: ', 'event-tickets' ); ?>
						<?php Tribe__Tickets__Tickets_View::instance()->render_rsvp_selector( "attendee[{$key}][order_status]", $attendee['order_status'] ); ?>
					</span>
					<?php echo sprintf( esc_html__( 'Attendee %d (Order #%d)', 'event-tickets' ), $i + 1, $attendee['order_id'] ); ?>
				</p>
				<table>
					<tr class="tribe-tickets-full-name-row">
						<td>
							<label for="tribe-tickets-full-name-<?php echo $key; ?>"><?php esc_html_e( 'Full Name', 'event-tickets' ); ?>:</label>
						</td>
						<td colspan="3">
							<input type="text" name="attendee[<?php echo $key; ?>][full_name]" id="tribe-tickets-full-name-<?php echo $key; ?>" value="<?php echo esc_attr( $attendee['purchaser_name'] ) ?>">
						</td>
					</tr>
					<tr class="tribe-tickets-email-row">
						<td>
							<label for="tribe-tickets-email-<?php echo $key; ?>"><?php esc_html_e( 'Email', 'event-tickets' ); ?>:</label>
						</td>
						<td colspan="3">
							<input type="email" name="attendee[<?php echo $key; ?>][email]" id="tribe-tickets-email-<?php echo $key; ?>" value="<?php echo esc_attr( $attendee['purchaser_email'] ) ?>">
						</td>
					</tr>
					<tr class="tribe-tickets-attendees-list-optout">
						<td colspan="4">
							<input type="checkbox" name="attendee[<?php echo $key; ?>][optout]" id="tribe-tickets-attendees-list-optout-<?php echo $key; ?>" <?php checked( true, $attendee['optout'] ) ?>>
							<label for="tribe-tickets-attendees-list-optout-<?php echo $key; ?>"><?php esc_html_e( 'Don\'t list me on the public attendee list', 'event-tickets' ); ?></label>
						</td>
					</tr>
				</table>
				<?php
				/**
				 * Used to Include More fields to each Item
				 *
				 * @param array $attendee The Attendee Data
				 * @param int   $i        Order in Which this item appears
				 */
				do_action( 'tribe_tickets_user_tickets_item', $attendee, $i ); ?>
			</li>
		<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( ! empty( $rsvp_orders ) || ! empty( $ticket_orders ) ): ?>
		<div class="tribe-submit-tickets-form">
			<button type="submit" name="process-tickets" value="1" class="button alt"><?php echo sprintf( esc_html__( 'Update %s', 'event-tickets' ), Tribe__Tickets__Tickets_View::instance()->get_description_rsvp_ticket( $event_id, get_current_user_id(), true ) ); ?></button>
		</div>
	<?php endif; ?>

	</form>

</div><!-- #tribe-events-content -->
