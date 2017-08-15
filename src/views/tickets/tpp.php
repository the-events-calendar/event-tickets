<?php
/**
 * This template renders the Tribe Commerce ticket form
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/tickets/tpp.php
 *
 * @version 4.5
 *
 * @var bool $must_login
 */

$is_there_any_product         = false;
$is_there_any_product_to_sell = false;
$are_products_available       = false;
if ( isset( $_GET['bacon'] ) ) {
	do_action( 'debug_robot', 'posted data :: ' . print_r( $_POST, true ) );
}

ob_start();
$commerce       = tribe( 'tickets.commerce.paypal' );
$messages       = $commerce->get_messages();
$messages_class = $messages ? 'tribe-tpp-message-display' : '';
$now            = current_time( 'timestamp' );
//$cart_url       = tribe( 'tickets.commerce.paypal.gateway' )->get_cart_url();
$cart_url = '';
?>
<form
	id="tpp-buy-tickets"
	action="<?php echo esc_url( $cart_url ); ?>"
	class="tribe-tickets-tpp cart <?php echo esc_attr( $messages_class ); ?>"
	method="post"
	enctype='multipart/form-data'
>
	<input type="hidden" name="provider" value="Tribe__Tickets__Commerce__PayPal__Main">
	<input type="hidden" name="add" value="1">
	<h2 class="tribe-events-tickets-title tribe--tpp">
		<?php echo esc_html_x( 'Tickets', 'form heading', 'event-tickets' ) ?>
	</h2>

	<div class="tribe-tpp-messages">
		<?php
		if ( $messages ) {
			foreach ( $messages as $message ) {
				?>
				<div class="tribe-tpp-message tribe-tpp-message-<?php echo esc_attr( $message->type ); ?>">
					<?php echo esc_html( $message->message ); ?>
				</div>
				<?php
			}//end foreach
		}//end if
		?>

		<div
			class="tribe-tpp-message tribe-tpp-message-error tribe-tpp-message-confirmation-error" style="display:none;">
			<?php esc_html_e( 'Please fill in the ticket confirmation name and email fields.', 'event-tickets' ); ?>
		</div>
	</div>

	<table class="tribe-events-tickets tribe-events-tickets-tpp">
		<?php
		$item_counter = 1;
		foreach ( $tickets as $ticket ) {
			// if the ticket isn't a Tribe Commerce ticket, then let's skip it
			if ( 'Tribe__Tickets__Commerce__PayPal__Main' !== $ticket->provider_class ) {
				continue;
			}

			if ( ! $ticket->date_in_range( $now ) ) {
				continue;
			}

			$is_there_any_product = true;
			$is_there_any_product_to_sell = $ticket->is_in_stock();
			?>
			<tr>
				<td class="tribe-ticket quantity" data-product-id="<?php echo esc_attr( $ticket->ID ); ?>">
					<input type="hidden" name="product_id[]" value="<?php echo absint( $ticket->ID ); ?>">
					<?php if ( $is_there_any_product_to_sell ) : ?>
						<input
							type="number"
							class="tribe-ticket-quantity"
							min="0"
							max="<?php echo esc_attr( $ticket->remaining() ); ?>"
							name="quantity_<?php echo absint( $ticket->ID ); ?>"
							value="0"
							<?php disabled( $must_login ); ?>
						>
						<?php if ( $ticket->managing_stock() ) : ?>
							<span class="tribe-tickets-remaining">
								<?php echo sprintf( esc_html__( '%1$s out of %2$s available', 'event-tickets' ), $ticket->remaining(), $ticket->original_stock() ); ?>
							</span>
						<?php endif; ?>
					<?php else: ?>
						<span class="tickets_nostock"><?php esc_html_e( 'Out of stock!', 'event-tickets' ); ?></span>
					<?php endif; ?>
				</td>
				<td class="tickets_name">
					<?php echo esc_html( $ticket->name ); ?>
				</td>
				<td class="tickets_price">
					<?php echo $this->get_price_html( $ticket->ID ); ?>
				</td>
				<td class="tickets_description" colspan="2">
					<?php echo esc_html( $ticket->description ); ?>
				</td>
				<td class="tickets_submit">
					<?php if ( ! $must_login ): ?>
						<button type="submit" class="tpp-submit tribe-button"><?php esc_html_e( 'Buy now', 'event-tickets' );?></button>
					<?php endif; ?>
				</td>
			</tr>
			<?php

			/**
			 * Allows injection of HTML after an Tribe Commerce ticket table row
			 *
			 * @var Event ID
			 * @var Tribe__Tickets__Ticket_Object
			 */
			do_action( 'event_tickets_tpp_after_ticket_row', tribe_events_get_ticket_event( $ticket->id ), $ticket );

		}
		?>

		<?php if ( $is_there_any_product_to_sell ) : ?>
			<tr>
				<td colspan="5" class="tpp-add">
					<?php if ( $must_login ): ?>
						<?php include Tribe__Tickets__Main::instance()->get_template_hierarchy( 'login-to-purchase' ); ?>
					<?php endif; ?>
				</td>
			</tr>
		<?php endif ?>

		<noscript>
			<tr>
				<td class="tribe-link-tickets-message">
					<div class="no-javascript-msg"><?php esc_html_e( 'You must have JavaScript activated to purchase tickets. Please enable JavaScript in your browser.', 'event-tickets' ); ?></div>
				</td>
			</tr>
		</noscript>
	</table>
</form>

<?php
$content = ob_get_clean();
if ( $is_there_any_product ) {
	echo $content;

	// If we have rendered tickets there is generally no need to display a 'tickets unavailable' message
	// for this post
	$this->do_not_show_tickets_unavailable_message();
} else {
	// Indicate that we did not render any tickets, so a 'tickets unavailable' message may be
	// appropriate (depending on whether other ticket providers are active and have a similar
	// result)
	$this->maybe_show_tickets_unavailable_message( $tickets );
}
