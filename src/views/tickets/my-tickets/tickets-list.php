<?php
/**
 * My Tickets: Tickets List
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe/tickets/tickets/my-tickets/tickets-list.php
 *
 * @since 5.6.7
 * @since 5.8.0 Display the ticket type label for each ticket group.
 * @since 5.9.1 Corrected template override filepath
 *
 * @version 5.9.1
 *
 * @var array   $attendees The attendees for the current order.
 * @var int     $order_id  The ID of the order.
 * @var WP_Post $post      The post object.
 * @var int     $post_id   The current post ID.
 * @var array   $titles    List of ticket type titles.
 */

$attendees_by_ticket_type = [];

foreach ( $attendees as $attendee ) {
	$attendees_by_ticket_type[ $attendee['ticket_type'] ][] = $attendee;
}

// Place the default ticket type first.
if ( isset( $attendees_by_ticket_type['default'] ) ) {
	$attendees_by_ticket_type = array_merge( [ 'default' => $attendees_by_ticket_type['default'] ], $attendees_by_ticket_type );
}
?>
<?php foreach ( $attendees_by_ticket_type as $ticket_type => $attendees ) : ?>
	<?php
	$label = $titles[ $ticket_type ] ?? $titles['default'] ?? tec_tickets_get_default_ticket_type_label_lowercase( 'order list view' );
	$this->template( 'tickets/my-tickets/title', [ 'title' => $label, 'ticket_type' => $ticket_type ] );
	?>
	<div class="tec__tickets-my-tickets-order-tickets-list-wrapper">
		<ul class="tribe-tickets-list tribe-list">
			<?php foreach ( $attendees as $i => $attendee ) : ?>
				<input type="hidden" name="attendee[<?php echo esc_attr( $order_id ); ?>][attendees][]" value="<?php echo esc_attr( $attendee['attendee_id'] ); ?>">
				<li class="tribe-item" id="ticket-<?php echo esc_attr( $order_id ); ?>">
					<input type="hidden" name="attendee[<?php echo esc_attr( $order_id ); ?>][attendees][]" value="<?php echo esc_attr( $attendee['attendee_id'] ); ?>">
					<?php
						$this->template(
							'tickets/my-tickets/attendee-label',
							[
								// Translators: %d is the attendee number.
								'attendee_label' => sprintf( esc_html__( 'Attendee %d', 'event-tickets' ), $i + 1 ),
							]
						);
					?>
					<?php
					$this->template(
						'tickets/my-tickets/ticket-information',
						[
							'attendee' => $attendee,
						]
					);
					?>
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
<?php endforeach; ?>
