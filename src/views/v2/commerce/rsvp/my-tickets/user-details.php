<?php
/**
 * RSVP V2: My Tickets - User Details
 *
 * Renders the "Reserved by" details line for a TC-RSVP order on the My Tickets page, replacing
 * `tickets/my-tickets/user-details.php` for RSVP orders (see
 * `TEC\Tickets\RSVP\V2\Frontend::render_my_tickets_user_details()`).
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/rsvp/my-tickets/user-details.php
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var array $order     The order data.
 * @var array $attendees The attendees for the current order.
 * @var int   $order_id  The order ID.
 * @var int   $post_id   The ID of the post the tickets are for.
 */

defined( 'ABSPATH' ) || exit;

$purchaser_name  = $order && ! empty( $order['purchaser_name'] ) ? $order['purchaser_name'] : __( 'Unknown Name (invalid order)', 'event-tickets' );
$purchaser_email = $order && ! empty( $order['purchaser_email'] ) ? $order['purchaser_email'] : __( 'Unknown Email (invalid order)', 'event-tickets' );
$purchase_time   = $order && ! empty( $order['purchase_time'] ) ? $order['purchase_time'] : null;

?>
<div class="user-details">
	<?php
		printf(
			// Translators: 1: attendee name, 2: linked attendee email, 3: date of RSVP.
			esc_html__( 'Reserved by %1$s (%2$s) on %3$s', 'event-tickets' ),
			esc_html( $purchaser_name ),
			'<a href="' . esc_url( 'mailto:' . $purchaser_email ) . '">' . esc_html( $purchaser_email ) . '</a>',
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

