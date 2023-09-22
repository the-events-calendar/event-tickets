<?php
/**
 * My Tickets: Tickets List
 * 
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/tickets/my-tickets/tickets-list.php
 * 
 * @since TBD
 * 
 * @version TBD
 * 
 * @var array   $attendees The attendees for the current order.
 * @var int     $order_id  The ID of the order.
 * @var WP_Post $post      The post object.
 */

?>
<ul class="tribe-tickets-list tribe-list">
	<?php foreach ( $attendees as $i => $attendee ) : ?>
		<li class="tribe-item" id="ticket-<?php echo esc_attr( $order_id ); ?>">
			<input type="hidden" name="attendee[<?php echo esc_attr( $order_id ); ?>][attendees][]" value="<?php echo esc_attr( $attendee['attendee_id'] ); ?>">
			<?php 
				$this->template( 'tickets/my-tickets/attendee-label', [ 
					'attendee_label' => sprintf( esc_html__( 'Attendee %d', 'event-tickets' ), $i + 1 )
				] );
			?>
			<?php $this->template( 'tickets/my-tickets/ticket-information', [
				'attendee' => $attendee,
			] ); ?>
			<?php
			/**
			 * Inject content into a Ticket's attendee block on the Tickets orders page.
			 *
			 * @param array   $attendee Attendee array.
			 * @param WP_Post $post     Post object that the tickets are tied to.
			 */
			do_action( 'event_tickets_orders_attendee_contents', $attendee, $post );
			?>
		</li>
	<?php endforeach; ?>
</ul>