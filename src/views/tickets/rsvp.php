<?php

$is_there_any_product         = false;
$is_there_any_product_to_sell = false;

ob_start();
?>
<form action="" class="cart" method="post" enctype='multipart/form-data'>
	<h2 class="tribe-events-tickets-title"><?php esc_html_e( 'RSVP', 'tribe-tickets' ) ?></h2>
	<?php
	$messages = Tribe__Tickets__RSVP::get_instance()->get_messages();

	if ( $messages ) {
		?>
		<div class="tribe-rsvp-messages">
			<?php
			foreach ( $messages as $message ) {
				?>
				<div class="tribe-rsvp-message tribe-rsvp-message-<?php echo esc_attr( $message->type ); ?>">
					<?php echo esc_html( $message->message ); ?>
				</div>
				<?php
			}//end foreach
			?>
		</div>
		<?php
	}//end if
	?>
	<table width="100%" class="tribe-events-tickets tribe-events-tickets-rsvp">
		<?php
		foreach ( $tickets as $ticket ) {
			// if the ticket isn't an RSVP ticket, then let's skip it
			if ( 'Tribe__Tickets__RSVP' !== $ticket->provider_class ) {
				continue;
			}

			if ( $ticket->date_in_range( time() ) ) {
				$is_there_any_product = true;

				?>
				<tr>
					<td class="tribe-ticket">
						<input type="hidden" name="product_id[]" value="<?php echo absint( $ticket->ID ); ?>">
						<?php
						if ( $ticket->is_in_stock() ) {
							$is_there_any_product_to_sell = true;
							?>
							<input type="number" class="tribe-ticket-quantity" min="0" name="quantity_<?php echo absint( $ticket->ID ); ?>" value="0">
							<?php
						}//end if
						else {
							?>
							<span class="tickets_nostock"><?php esc_html_e( 'Out of stock!', 'tribe-tickets' ); ?></span>
							<?php
						}
						?>
					</td>
					<td nowrap="nowrap" class="tickets_name">
						<?php echo esc_html( $ticket->name ); ?>
					</td>
					<td class="tickets_price">
						<?php echo tribe_format_currency( $ticket->price ); ?>
					</td>
					<td class="tickets_description">
						<?php echo esc_html( $ticket->description ); ?>
					</td>
				</tr>
				<?php
			}
		}//end foreach

		if ( $is_there_any_product_to_sell ) {
			?>
			<tr class="tribe-tickets-meta-row">
				<td colspan="4" class="tribe-tickets-attendees">
					<table>
						<tr class="tribe-tickets-full-name-row">
							<td>
								<label for="tribe-tickets-full-name"><?php esc_html_e( 'Full Name:', 'tribe-tickets' ); ?></label>
							</td>
							<td colspan="3">
								<input type="text" name="attendee[full_name]" id="tribe-tickets-full-name">
							</td>
						</tr>
						<tr class="tribe-tickets-email-row">
							<td>
								<label for="tribe-tickets-email"><?php esc_html_e( 'Email:', 'tribe-tickets' ); ?></label>
							</td>
							<td colspan="3">
								<input type="email" name="attendee[email]" id="tribe-tickets-email">
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan="4" class="add-to-cart">
					<button type="submit" name="tickets_process" value="1" class="button alt"><?php esc_html_e( 'Confirm RSVP', 'tribe-tickets' );?></button>
				</td>
			</tr>
			<?php
		}
		?>
	</table>
</form>

<?php
$content = ob_get_clean();
if ( $is_there_any_product ) {
	echo $content;
}
