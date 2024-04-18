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

 ?>
 <div class="user-details">
	<?php
		printf(
			// Translators: 1: order number, 2: count of attendees in the order, 3: ticket label (dynamically singular or plural), 4: purchaser name, 5: linked purchaser email, 6: date of purchase.
			esc_html__( 'Order #%1$s: %2$d %3$s reserved by %4$s (%5$s) on %6$s', 'event-tickets' ),
			esc_html( $order_id ),
			count( $attendees ),
			_n(
				esc_html( tribe_get_ticket_label_singular( 'orders_tickets' ) ),
				esc_html( tribe_get_ticket_label_plural( 'orders_tickets' ) ),
				count( $attendees ),
				'event-tickets'
			),
			esc_attr( $order['purchaser_name'] ),
			'<a href="mailto:' . esc_url( $order['purchaser_email'] ) . '">' . esc_html( $order['purchaser_email'] ) . '</a>',
			date_i18n( tribe_get_date_format( true ), strtotime( esc_attr( $order['purchase_time'] ) ) )
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