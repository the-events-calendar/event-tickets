<?php
/**
 * List of RSVP Orders.
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe/tickets/orders-rsvp.php
 *
 * @since 4.2
 * @since 4.10.9 Uses new functions to get singular and plural texts.
 * @since 4.12.1 Account for empty post type object, such as if post type got disabled.
 * @since 5.9.1 Corrected template override filepath
 *
 * @version 5.9.1
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

if ( ! $view->has_rsvp_attendees( $post_id, $user_id ) ) {
	return;
}

$post_type_singular = $post_type ? $post_type->labels->singular_name : _x( 'Post', 'fallback post type singular name', 'event-tickets' );

$attendee_groups = $view->get_event_rsvp_attendees_by_purchaser( $post_id, $user_id );
?>
<div class="tribe-rsvp">
	<?php foreach ( $attendee_groups as $attendee_group ): ?>
		<?php
		$first_attendee = reset( $attendee_group );
		?>
		<div class="user-details">
				<?php
				printf(
					// Translators: 1: attendee name, 2: linked attendee email, 3: date of RSVP.
					esc_html__( 'Reserved by %1$s (%2$s) on %s', 'event-tickets' ),
					esc_html( $first_attendee['purchaser_name'] ),
					'<a href="' . esc_url( 'mailto:' . $first_attendee['purchaser_email'] ) .'">' . esc_html( $first_attendee['purchaser_email'] ) . '</a>',
					date_i18n( Tribe__Date_Utils::DATEONLYFORMAT, strtotime( esc_attr( $first_attendee['purchase_time'] ) ) )
				);
				?>
			<?php
				/**
				* Inject content into the RSVP User Details block on the orders page
				*
				* @param array $attendee_group Attendee array
				* @param int $post_id
				*/
				do_action( 'event_tickets_user_details_rsvp', $attendee_group, $post_id );
			?>
		</div>
		<?php
			$this->template( 'tickets/my-tickets/title', [
				'title'  => tribe_get_rsvp_label_plural( basename( __FILE__ ) ),
			] );
		?>
		<div class="tec__tickets-my-tickets-rsvp-attendee-list-wrapper">
			<ul class="tribe-rsvp-list tribe-list">
				<?php foreach ( $attendee_group as $i => $attendee ): ?>
					<?php $key = $attendee['order_id']; ?>
					<li class="tribe-item<?php echo $view->is_rsvp_restricted( $post_id, $attendee['product_id'] ) ? 'tribe-disabled' : ''; ?>" <?php echo $view->get_restriction_attr( $post_id, $attendee['product_id'] ); ?> id="attendee-<?php echo $attendee['order_id']; ?>">
						<?php
							$this->template( 'tickets/my-tickets/attendee-label', [
								// Translators: %d is the attendee number.
								'attendee_label' => sprintf( esc_html__( 'Attendee %d', 'event-tickets' ), $i + 1 )
							] );
						?>
						<div class="tribe-answer">
							<!-- Wrapping <label> around both the text and the <select> will implicitly associate the text with the label. -->
							<!-- See https://www.w3.org/WAI/tutorials/forms/labels/#associating-labels-implicitly -->
							<label>
								<?php echo esc_html_x( 'RSVP: ', 'order status label', 'event-tickets' ); ?>
								<?php
								if ( ! empty( $attendee['ticket_exists'] ) ) {
									$view->render_rsvp_selector(
										"attendee[{$key}][order_status]",
										$attendee['order_status'],
										$post_id,
										$attendee['product_id']
									);
								} else {
									$view->render_rsvp_status(
										"attendee[{$key}][order_status]",
										$attendee['order_status'],
										$post_id,
										$attendee['product_id']
									);
								}
								?>
							</label>
							<div class="ticket-type"><span class="type-label"><?php esc_html_e( 'Type: ', 'event-tickets' );?></span><?php esc_html_e( $attendee['ticket'] );?></div>
						</div>
						<?php
						/**
						 * Inject content into an RSVP attendee block on the RVSP orders page
						 *
						 * @param array $attendee Attendee array
						 * @param WP_Post $post Post object that the tickets are tied to
						 */
						do_action( 'event_tickets_orders_attendee_contents', $attendee, $post );
						?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
			/**
			 * Inject content after the RSVP Attendees List on the My Tickets page
			 *
			 * @since 5.6.7
			 *
			 * @param array   $attendee_group Attendee array.
			 * @param WP_Post $post_id        Post object that the tickets are tied to.
			 */
			do_action( 'tec_tickets_my_tickets_after_rsvps_list', $attendee_group, $post_id );
			?>
		</div>
	<?php endforeach; ?>
</div>
