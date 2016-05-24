<?php
/**
 * List of RSVP Orders
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/tickets/orders-rsvp.php
 *
 * @package TribeEventsCalendar
 * @version 4.2
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
$view      = Tribe__Tickets__Tickets_View::instance();
$post_id   = get_the_ID();
$post      = get_post( $post_id );
$post_type = get_post_type_object( $post->post_type );
$user_id   = get_current_user_id();
$user_info = get_userdata( $user_id );
$attendees = $view->get_event_rsvp_attendees( $post_id, $user_id );

echo '<pre>';
var_dump( $attendees );
echo '</pre>';

$order_id = array_column( $attendees, 'order_id'  );
$name = array_column( $attendees, 'purchaser_name' );
$email = array_column( $attendees, 'purchaser_email' );
$time = array_column( $attendees, 'purchase_time' );


if ( ! $view->has_rsvp_attendees( $post_id, $user_id ) ) {
	return;
}
?>

<h2><?php echo sprintf( esc_html__( 'My RSVPs for This %s', 'event-tickets' ), $post_type->labels->singular_name ); ?></h2>


<p class="reserved-by"><?php echo sprintf( esc_html__( 'Reserved by %s', 'event-tickets' ), $name[0] ); ?><?php echo sprintf( esc_html__( ' on %s', 'event-tickets' ), date_i18n( 'F j, Y', strtotime( $time[0] ) ) ); ?></p>


<ul class="tribe-rsvp-list">
<?php foreach ( $attendees as $i => $attendee ): ?>
	<?php $key = $attendee['order_id']; ?>
	<li class="tribe-item<?php echo $view->is_rsvp_restricted( $post_id, $attendee['product_id'] ) ? 'tribe-disabled' : ''; ?>" <?php echo $view->get_restriction_attr( $post_id, $attendee['product_id'] ); ?> id="attendee-<?php echo $attendee['order_id']; ?>">
		<p class="list-attendee" style="display:inline-block;text-transform: uppercase;color:#999;letter-spacing: 1px;"><?php echo sprintf( esc_html__( 'Attendee %d', 'event-tickets' ), $i + 1 ); ?></p>
		<div class="tribe-answer">
			<!-- Wrapping <label> around both the text and the <select> will implicitly associate the text with the label. -->
			<!-- See https://www.w3.org/WAI/tutorials/forms/labels/#associating-labels-implicitly -->
				<label>
					<?php esc_html_e( 'RSVP: ', 'event-tickets' ); ?>
					<?php $view->render_rsvp_selector( "attendee[{$key}][order_status]", $attendee['order_status'], $post_id, $attendee['product_id'] ); ?>
				</label>
			</div>
			<div class="tribe-tickets attendees-list-optout">
				<input <?php echo $view->get_restriction_attr( $post_id, $attendee['product_id'] ); ?> type="checkbox" name="attendee[<?php echo $key; ?>][optout]" id="tribe-tickets-attendees-list-optout-<?php echo $key; ?>" <?php checked( true, $attendee['optout'] ) ?>>
				<label for="tribe-tickets-attendees-list-optout-<?php echo $key; ?>"><?php esc_html_e( 'Don\'t list me on the public attendee list', 'event-tickets' ); ?></label>
			</div>
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
