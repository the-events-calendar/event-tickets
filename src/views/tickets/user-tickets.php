<?php
/**
 * Single Event Template
 * A single event. This displays the event title, description, meta, and
 * optionally, the Google map for the event.
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events//single-event.php
 *
 * @package TribeEventsCalendar
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$events_label_singular = tribe_get_event_label_singular();
$event_id = get_the_ID();
$ticket_orders = Tribe__Tickets__Tickets_View::get_event_attendees_by_order( $event_id );
$rsvp_orders = Tribe__Tickets__Tickets_View::get_event_rsvp_attendees( $event_id );

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

	<form>
	<?php if ( ! empty( $rsvp_orders ) ): ?>
		<h2><?php echo sprintf( esc_html__( 'RSVP attendees on this %s', 'event-tickets' ), $events_label_singular ); ?></h2>
		<ul class="tribe-edit-rsvp">
		<?php foreach ( $rsvp_orders as $key => $attendee ): ?>
			<li class="tribe-rsvp-item" id="attendee-<?php echo $attendee['order_id']; ?>">
				<p>
					<span class="tribe-rsvp-answer">
						<?php esc_html_e( 'RSVP: ', 'event-tickets' ); ?>
						<select name="attendee[<?php echo $key; ?>][status]">
							<option <?php selected( $attendee['order_status'], 'yes' ); ?> value="yes"><?php esc_html_e( 'Going', 'event-tickets' ); ?></option>
							<option <?php selected( $attendee['order_status'], 'no' ); ?> value="no"><?php esc_html_e( 'Not Going', 'event-tickets' ); ?></option>
						</select>
					</span>
					<?php echo sprintf( esc_html__( 'Attendee %d (Order #%d)', 'event-tickets' ), $key + 1, $attendee['order_id'] ); ?>
				</p>
				<table>
					<tr class="tribe-tickets-full-name-row">
						<td>
							<label for="tribe-tickets-full-name"><?php esc_html_e( 'Full Name', 'event-tickets' ); ?>:</label>
						</td>
						<td colspan="3">
							<input type="text" name="attendee[<?php echo $key; ?>][full_name]" id="tribe-tickets-full-name" value="<?php echo esc_attr( $attendee['purchaser_name'] ) ?>">
						</td>
					</tr>
					<tr class="tribe-tickets-email-row">
						<td>
							<label for="tribe-tickets-email"><?php esc_html_e( 'Email', 'event-tickets' ); ?>:</label>
						</td>
						<td colspan="3">
							<input type="email" name="attendee[<?php echo $key; ?>][email]" id="tribe-tickets-email" value="<?php echo esc_attr( $attendee['purchaser_email'] ) ?>">
						</td>
					</tr>
					<tr class="tribe-tickets-attendees-list-optout">
						<td colspan="4">
							<input type="checkbox" name="attendee[<?php echo $key; ?>][optout]" id="tribe-tickets-attendees-list-optout" <?php checked( true, $attendee['optout'] ) ?>>
							<label for="tribe-tickets-attendees-list-optout"><?php esc_html_e( 'Don\'t list me on the public attendee list', 'event-tickets' ); ?></label>
						</td>
					</tr>
				</table>
			</li>
		<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( ! empty( $rsvp_orders ) || ! empty( $ticket_orders ) ): ?>
		<?php
			$what_to_update = (array) esc_html__( 'RSVP', 'event-tickets' );
			if ( ! empty( $ticket_orders ) ) {
				$what_to_update[] = esc_html__( 'Tickets', 'event-tickets' );
			}

			$what_to_update = implode( esc_html__( ' and ', 'event-tickets' ), $what_to_update );
		?>
		<div class="tribe-submit-tickets-form">
			<button type="submit" name="process-tickets" value="1" class="button alt"><?php echo sprintf( esc_html__( 'Update %s', 'event-tickets' ), $what_to_update ); ?></button>
		</div>
	<?php endif; ?>

	</form>

</div><!-- #tribe-events-content -->
