<?php
/**
 * Seating iFrame view template.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe/tickets-seating/iframe-view.php
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var string $iframe_url The URL to the service iframe.
 * @var string $token      The ephemeral token used to secure the iframe communication with the service.
 * @var string $error      The error message returned by the service.
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
		title="<?php esc_html_e( 'Seat selection', 'event-tickets' ); ?>"
	>
	</iframe>
</div>

<aside class="tec-tickets-seating__modal-sidebar">
	<div class="tec-tickets-seating__sidebar-content">
		<h4 class="tribe-common-h4 tec-tickets-seating__title">
			Tickets
		</h4>

		<div class="tec-tickets-seating__timer">
			<div class="dashicons dashicons-clock"></div>
			<div class="tec-tickets-seating__message">
			<span>
				<span class="tec-tickets-seating__message-text">Seat selections reserved for </span>
				<span class="tec-tickets-seating__message-time">9:55</span>
			</span>
			</div>
		</div>

		<div class="tec-tickets-seating__ticket-rows">

			<div class="tec-tickets-seating__ticket-row">
				<div class="tec-tickets-seating__seat-color"> </div>

				<div class="tec-tickets-seating__label">
					<div class="tec-tickets-seating__ticket-name">VIP seat</div>
					<div class="tec-tickets-seating__seat-label">C7</div>
				</div>

				<div class="tec-tickets-seating__ticket-price">$40.00</div>
			</div>

		</div>
	</div>

	<div class="tec-tickets-seating__sidebar-footer">
		<div class="tec-tickets-seating__total">
			<div class="tec-tickets-seating__total-text">
				1 Ticket
			</div>
			<div class="tec-tickets-seating__total-price">
				$40.00
			</div>
		</div>

		<div class="tec-tickets-seating__sidebar-controls">
			<button class="tec-tickets-seating__sidebar-control tec-tickets-seating__sidebar-control--cancel">
				Cancel
			</button>
			<button class="tribe-common-c-btn tribe-common-c-btn--small tec-tickets-seating__sidebar-control">
				Check Out
			</button>
		</div>
	</div>
</aside>
