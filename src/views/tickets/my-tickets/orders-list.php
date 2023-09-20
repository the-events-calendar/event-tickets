<?php
/**
 * My Tickets: Orders List
 * 
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/tickets/my-tickets/orders-list.php
 * 
 * @since TBD
 * 
 * @version TBD
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
				<div class="user-details">
					<?php
						printf(
							// Translators: 1: order number, 2: count of attendees in the order, 3: ticket label (dynamically singular or plural), 4: purchaser name, 5: linked purchaser email, 6: date of purchase.
							esc_html__( 'Order #%1$s: %2$d %3$s reserved by %4$s (%5$s) on %6$s', 'event-tickets-plus' ),
							esc_html( $order_id ),
							count( $attendees ),
							_n(
								esc_html( tribe_get_ticket_label_singular( 'orders_tickets' ) ),
								esc_html( tribe_get_ticket_label_plural( 'orders_tickets' ) ),
								count( $attendees ),
								'event-tickets-plus'
							),
							esc_attr( $order['purchaser_name'] ),
							'<a href="mailto:' . esc_url( $order['purchaser_email'] ) . '">' . esc_html( $order['purchaser_email'] ) . '</a>',
							date_i18n( tribe_get_date_format( true ), strtotime( esc_attr( $order['purchase_time'] ) ) )
						);
					?>
					<?php
					/**
					 * Inject content into the Tickets User Details block on the orders page
					 *
					 * @param array   $attendees Attendee array.
					 * @param WP_Post $post_id   Post object that the tickets are tied to.
					 */
					do_action( 'event_tickets_user_details_tickets', $attendees, $post_id );
					?>
				</div>
				<?php 
					// @todo Need to determine title based on ticket type. RIght now, it's being passed into the main template.
					$this->template( 'tickets/my-tickets/title' ); 
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
							<div class="tribe-ticket-information">
								<?php
								$price = '';
								if ( ! empty( $provider ) ) {
									$price = $provider->get_price_html( $attendee['product_id'], $attendee );
								}
								?>

								<?php if ( ! empty( $attendee['ticket_exists'] ) ) : ?>
									<span class="ticket-name"><?php echo esc_html( $attendee['ticket'] ); ?></span>
								<?php endif; ?>

								<?php if ( ! empty( $price ) ): ?>
									- <span class="ticket-price"><?php echo $price; ?></span>
								<?php endif; ?>
							</div>
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
			</li>
		<?php endforeach; ?>
	</ul>