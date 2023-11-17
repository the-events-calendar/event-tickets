<?php
/**
 * My Tickets: Tickets List
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/tickets/tickets/my-tickets/tickets-list.php
 *
 * @since 5.6.7
 *
 * @version 5.6.7
 *
 * @var array   $attendees The attendees for the current order.
 * @var int     $order_id  The ID of the order.
 * @var WP_Post $post      The post object.
 */

?>
<div class="tec__tickets-my-tickets-order-tickets-list-wrapper">
	<ul class="tribe-tickets-list tribe-list">
		<?php foreach ( $attendees as $i => $attendee ) : ?>
			<input type="hidden" name="attendee[<?php echo esc_attr( $order_id ); ?>][attendees][]" value="<?php echo esc_attr( $attendee['attendee_id'] ); ?>">
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

				/**
				 * Inject content into a Ticket's attendee block on the Tickets orders page.
				 *
				 * @since 5.6.7
				 *
				 * @param array   $attendee Attendee array.
				 * @param WP_Post $post     Post object that the tickets are tied to.
				 */
				do_action( 'tec_tickets_orders_attendee_contents', $attendee, $post );
				?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
	/**
	 * Inject content after the Order Tickets List on the My Tickets page
	 *
	 * @since 5.6.7
	 *
	 * @param array   $attendees Attendee array.
	 * @param WP_Post $post_id   Post object that the tickets are tied to.
	 */
	do_action( 'tec_tickets_my_tickets_after_tickets_list', $attendees, $post_id );
	?>
</div>
