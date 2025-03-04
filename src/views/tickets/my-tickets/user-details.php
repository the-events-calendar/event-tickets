<?php
/**
 * My Tickets: User Details
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe/tickets/tickets/my-tickets/user-details.php
 *
 * @since 5.6.7
 * @since 5.9.1 Corrected template override filepath
 *
 * @version 5.9.1
 *
 * @var int    $order_id  The ID of the order.
 * @var array  $order     The order data.
 * @var array  $attendees The attendees for the current order.
 * @var int    $post_id   The ID of the post the tickets are for.
 */

$purchaser_name  = $order && ! empty( $order['purchaser_name'] ) ? $order['purchaser_name'] : __( 'Unknown Name (invalid order)', 'event-tickets' );
$purchaser_email = $order && ! empty( $order['purchaser_email'] ) ? $order['purchaser_email'] : __( 'Unknown Email (invalid order)', 'event-tickets' );
$purchase_time   = $order && ! empty( $order['purchase_time'] ) ? $order['purchase_time'] : null;

 ?>
 <div class="user-details">
	<?php
		printf(
			// Translators: 1: order number, 2: count of attendees in the order, 3: ticket label (dynamically singular or plural), 4: purchaser name, 5: linked purchaser email, 6: date of purchase.
			esc_html__( 'Order #%1$s: %2$d %3$s reserved by %4$s (%5$s) on %6$s', 'event-tickets' ),
			(int) $order_id,
			(int) count( $attendees ),
			esc_html(
				_n(
					'Ticket',
					'Tickets',
					count( $attendees ),
					'event-tickets'
				)
			),
			esc_attr( $purchaser_name ),
			'<a href="mailto:' . esc_url( $purchaser_email ) . '">' . esc_html( $purchaser_email ) . '</a>',
			esc_html( $purchase_time ? date_i18n( tribe_get_date_format( true ), strtotime( $purchase_time ) ) : __( 'Unknown Time (invalid order)', 'event-tickets' ) )
		);

		/**
		 * Inject content into the Tickets User Details block on the orders page
		 *
		 * @param array   $attendees Attendee array.
		 * @param WP_Post $post_id   Post object that the tickets are tied to.
		 */
		do_action( 'event_tickets_user_details_tickets', $attendees, $post_id );

		/**
		 * Inject content into the Tickets User Details block on the orders page
		 *
		 * @since 5.6.7
		 *
		 * @param array   $attendees Attendee array.
		 * @param WP_Post $post_id   Post object that the tickets are tied to.
		 */
		do_action( 'tec_tickets_user_details_tickets', $attendees, $post_id );
	?>
</div>
