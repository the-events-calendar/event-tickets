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
				$this->template( 'tickets/my-tickets/user-details', [
					'order'     => $order,
					'attendees' => $attendees,
					'order_id'  => $order_id,
				] );

				$this->template( 'tickets/my-tickets/tickets-list', [
					'order'     => $order,
					'attendees' => $attendees,
					'order_id'  => $order_id,
				] );
			?>
		</li>
	<?php endforeach; ?>
</ul>