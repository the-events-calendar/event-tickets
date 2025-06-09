<?php
/**
 * Seating iFrame view template.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe/tickets-seating/iframe-view.php
 *
 * @since 5.16.0
 *
 * @version 5.16.0
 *
 * @var string $iframe_url          The URL to the service iframe.
 * @var string $token               The ephemeral token used to secure the iframe communication with the service.
 * @var string $error               The error message returned by the service.
 * @var string $initial_total_text  The initial text of the total; should be "0 Tickets".
 * @var string $initial_total_price The initial price of the tickets; already HTML-escaped.
 * @var int    $post_id             The post ID of the post to purchase tickets for.
 */

?>

<div
	class="tec-tickets-seating__iframe-container"
	data-token="<?php echo esc_attr( $token ); ?>"
	data-error="<?php echo esc_attr( $error ); ?>"
>
	<iframe
		data-src="<?php echo esc_url( $iframe_url ); ?>"
		id="tec-tickets-seating-iframe-tickets-block"
		class="tec-tickets-seating__iframe tec-tickets-seating__iframe--tickets-block"
		title="<?php echo esc_attr__( 'Seat selection', 'event-tickets' ); ?>"
	>
	</iframe>
</div>

<div class="tec-tickets-seating__modal-sidebar_container">
	<aside class="tec-tickets-seating__modal-sidebar">

		<div class="tec-tickets-seating__sidebar-header">
			<h4 class="tribe-common-h4 tec-tickets-seating__title">
				<?php esc_html_e( 'Tickets', 'event-tickets' ); ?>
			</h4>

			<?php
			/**
			 * Render the seat selection timer.
			 *
			 * @since 5.16.0
			 *
			 * @param string $token The ephemeral token used to secure the iframe communication with the service.
			 * @param int    $post_id The post ID of the post to purchase tickets for.
			 */
			do_action( 'tec_tickets_seating_seat_selection_timer', $token, $post_id );
			?>
			<div class="tec-tickets-seating__sidebar-arrow">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
			</div>
		</div>

		<div class="tec-tickets-seating__empty-tickets-message">
			<?php esc_html_e( 'Select a seat from the map to add seated tickets', 'event-tickets' ); ?>
		</div>
		<div class="tec-tickets-seating__tickets-wrapper">
			<div class="tec-tickets-seating__ticket-rows"></div>
		</div>

		<div class="tec-tickets-seating__sidebar-footer">
			<div class="tec-tickets-seating__total tec-tickets-seating__total-hidden">
				<div class="tec-tickets-seating__total-text">
					<?php echo esc_html( $initial_total_text ); ?>
				</div>
				<div class="tec-tickets-seating__total-price">
					<?php echo esc_html( $initial_total_price ); ?>
				</div>
			</div>

			<div class="tec-tickets-seating__sidebar-controls">
				<button class="tec-tickets-seating__sidebar-control tec-tickets-seating__sidebar-control--cancel">
					<?php esc_html_e( 'Cancel', 'event-tickets' ); ?>
				</button>
				<button class="tribe-common-c-btn tribe-common-c-btn--small tec-tickets-seating__sidebar-control tec-tickets-seating__sidebar-control--confirm">
					<?php esc_html_e( 'Continue', 'event-tickets' ); ?>
				</button>
			</div>
		</div>
	</aside>
</div>
