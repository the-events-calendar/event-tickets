<?php
/**
 * My Tickets: Orders List
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe/tickets/tickets/my-tickets/orders-list.php
 *
 * @since 5.6.7
 * @since 5.9.1 Corrected template override filepath
 *
 * @version 5.9.1
 *
 * @var  array  $orders  The orders for the current user.
 * @var  int    $post_id The ID of the post the tickets are for.
 */

use TEC\Tickets\Commerce\RSVP\Constants;

?>
<ul class="tribe-orders-list">
	<input type="hidden" name="event_id" value="<?php echo absint( $post_id ); ?>">
	<?php foreach ( $orders as $order_id => $attendees ) : ?>
		<?php
		// Get provider from first attendee.
		$first_attendee = reset( $attendees );

		/** @var $provider Tribe__Tickets__Tickets */
		$provider = Tribe__Tickets__Tickets::get_ticket_provider_instance( $first_attendee['provider'] );
		if ( empty( $provider ) || ! method_exists( $provider, 'get_order_data' ) ) {
			continue;
		}
		$order = $provider->get_order_data( $order_id );

		?>
		<li class="tribe-item" id="order-<?php echo esc_html( $order_id ); ?>">
			<?php
			// Check if this is a TC-RSVP order.
			$is_tc_rsvp = false;
			if ( ! empty( $first_attendee['product_id'] ) ) {
				$ticket = Tribe__Tickets__Tickets::load_ticket_object( $first_attendee['product_id'] );
				if ( $ticket && Constants::TC_RSVP_TYPE === $ticket->type() ) {
					$is_tc_rsvp = true;
				}
			}

			// Use RSVP template for TC-RSVP orders, regular template for others.
			$template_path = $is_tc_rsvp ? 'tickets/my-tickets/rsvp-user-details' : 'tickets/my-tickets/user-details';
			$this->template(
				$template_path,
				[
					'order'     => $order,
					'attendees' => $attendees,
					'order_id'  => $order_id,
				]
			);

			$this->template(
				'tickets/my-tickets/tickets-list',
				[
					'order'     => $order,
					'attendees' => $attendees,
					'order_id'  => $order_id,
				]
			);
			?>
		</li>
	<?php endforeach; ?>
</ul>
