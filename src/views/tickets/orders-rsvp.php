<?php
/**
 * List of RSVP Orders
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/tickets/orders-rsvp.php
 *
 * @package TribeEventsCalendar
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
$view = Tribe__Tickets__Tickets_View::instance();

$events_label_singular = tribe_get_event_label_singular();
$event_id = get_the_ID();
$user_id = get_current_user_id();
$attendees = $view->get_event_rsvp_attendees( $event_id, $user_id );

if ( ! $view->has_rsvp_attendees( $event_id, $user_id ) ) {
	return;
}
?>

<h2><?php echo sprintf( esc_html__( 'My RSVPs for This %s', 'event-tickets' ), $events_label_singular ); ?></h2>
<ul class="tribe-rsvp-list">
<?php foreach ( $attendees as $i => $attendee ): ?>
	<?php $key = $attendee['order_id']; ?>
	<li class="tribe-item <?php echo $view->is_rsvp_restricted( $event_id, $attendee['product_id'] ) ? 'tribe-disabled' : ''; ?>" <?php echo $view->get_restriction_attr( $event_id, $attendee['product_id'] ); ?> id="attendee-<?php echo $attendee['order_id']; ?>">
		<p>
			<span class="tribe-answer">
				<?php esc_html_e( 'RSVP: ', 'event-tickets' ); ?>
				<?php $view->render_rsvp_selector( "attendee[{$key}][order_status]", $attendee['order_status'], $event_id, $attendee['product_id'] ); ?>
			</span>
			<?php echo sprintf( esc_html__( 'Attendee %d', 'event-tickets' ), $i + 1 ); ?>
		</p>
		<table>
			<tr class="tribe-tickets-full-name-row">
				<td>
					<label for="tribe-tickets-full-name-<?php echo $key; ?>"><?php esc_html_e( 'Full Name', 'event-tickets' ); ?>:</label>
				</td>
				<td colspan="3">
					<input <?php echo $view->get_restriction_attr( $event_id, $attendee['product_id'] ); ?> type="text" name="attendee[<?php echo $key; ?>][full_name]" id="tribe-tickets-full-name-<?php echo $key; ?>" value="<?php echo esc_attr( $attendee['purchaser_name'] ) ?>">
				</td>
			</tr>
			<tr class="tribe-tickets-email-row">
				<td>
					<label for="tribe-tickets-email-<?php echo $key; ?>"><?php esc_html_e( 'Email', 'event-tickets' ); ?>:</label>
				</td>
				<td colspan="3">
					<input <?php echo $view->get_restriction_attr( $event_id, $attendee['product_id'] ); ?> type="email" name="attendee[<?php echo $key; ?>][email]" id="tribe-tickets-email-<?php echo $key; ?>" value="<?php echo esc_attr( $attendee['purchaser_email'] ) ?>">
				</td>
			</tr>
			<tr class="tribe-tickets-attendees-list-optout">
				<td colspan="4">
					<input <?php echo $view->get_restriction_attr( $event_id, $attendee['product_id'] ); ?> type="checkbox" name="attendee[<?php echo $key; ?>][optout]" id="tribe-tickets-attendees-list-optout-<?php echo $key; ?>" <?php checked( true, $attendee['optout'] ) ?>>
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
		do_action( 'tribe_tickets_orders_rsvp_item', $attendee, $i ); ?>
	</li>
<?php endforeach; ?>
</ul>
