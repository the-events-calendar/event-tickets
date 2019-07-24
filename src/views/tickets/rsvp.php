<?php
/**
 * This template renders the RSVP ticket form
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/tickets/rsvp.php
 *
 * @since 4.0
 * @since TBD Uses new functions to get singular and plural texts.
 *
 * @version TBD
 *
 * @var bool $must_login
 */

$is_there_any_product         = false;
$is_there_any_product_to_sell = false;
$are_products_available       = false;

ob_start();
$messages = Tribe__Tickets__RSVP::get_instance()->get_messages();
$messages_class = $messages ? 'tribe-rsvp-message-display' : '';
?>

<form
	id="rsvp-now"
	action=""
	class="tribe-tickets-rsvp cart <?php echo esc_attr( $messages_class ); ?>"
	method="post"
	enctype='multipart/form-data'
>
	<h2 class="tribe-events-tickets-title tribe--rsvp">
		<?php echo esc_html( tribe_get_rsvp_label_singular( 'form_heading' ) ); ?>
	</h2>


	<div class="tribe-rsvp-messages">
		<?php
		if ( $messages ) {
			foreach ( $messages as $message ) {
				?>
				<div class="tribe-rsvp-message tribe-rsvp-message-<?php echo esc_attr( $message->type ); ?>">
					<?php echo esc_html( $message->message ); ?>
				</div>
				<?php
			}//end foreach
		}//end if
		?>

		<div
			class="tribe-rsvp-message tribe-rsvp-message-error tribe-rsvp-message-confirmation-error" style="display:none;">
			<?php esc_html_e( 'Please fill in the RSVP quantity, confirmation name, and email fields.', 'event-tickets' ); ?>
		</div>
	</div>

	<table class="tribe-events-tickets tribe-events-tickets-rsvp">
		<?php
		foreach ( $tickets as $ticket ) {
			// if the ticket isn't an RSVP ticket, then let's skip it
			if (
				! $ticket instanceof Tribe__Tickets__Ticket_Object
				|| 'Tribe__Tickets__RSVP' !== $ticket->provider_class
			) {
				continue;
			}

			if ( ! $ticket->date_in_range() ) {
				continue;
			}

			$is_there_any_product = true;
			$is_there_any_product_to_sell = $ticket->is_in_stock();
			$remaining = $ticket->remaining();

			if ( $is_there_any_product_to_sell ) {
				$are_products_available = true;
			}

			?>
			<tr>
				<td class="tribe-ticket quantity" data-product-id="<?php echo esc_attr( $ticket->ID ); ?>">
					<input type="hidden" name="product_id[]" value="<?php echo absint( $ticket->ID ); ?>">
					<?php if ( $is_there_any_product_to_sell ) : ?>
						<input
							type="number"
							class="tribe-ticket-quantity"
						        step="1"
							min="0"
							<?php if ( -1 !== $remaining ) : ?>
								max="<?php echo esc_attr( $remaining ); ?>"
							<?php endif; ?>
							name="quantity_<?php echo absint( $ticket->ID ); ?>"
							value="0"
							<?php disabled( $must_login ); ?>
						>
						<?php if ( $ticket->managing_stock() ) : ?>
							<span class="tribe-tickets-remaining">
					<?php echo sprintf( esc_html__( '%1$s out of %2$s available', 'event-tickets' ), $ticket->available(), $ticket->capacity() ); ?>
				</span>
						<?php endif; ?>
					<?php else: ?>
						<span class="tickets_nostock"><?php esc_html_e( 'Out of stock!', 'event-tickets' ); ?></span>
					<?php endif; ?>
				</td>
				<td class="tickets_name">
					<?php echo esc_html( $ticket->name ); ?>
				</td>

				<td class="tickets_description" colspan="2">
					<?php echo esc_html( ( $ticket->show_description() ? $ticket->description : '' ) ); ?>
				</td>
			</tr>
			<?php

			/**
			 * Allows injection of HTML after an RSVP ticket table row
			 *
			 * @var Event ID
			 * @var Tribe__Tickets__Ticket_Object
			 */
			do_action( 'event_tickets_rsvp_after_ticket_row', tribe_events_get_ticket_event( $ticket->id ), $ticket );

		}
		?>

		<?php if ( $are_products_available ) : ?>
			<tr class="tribe-tickets-meta-row">
				<td colspan="4" class="tribe-tickets-attendees">
					<header><?php esc_html_e( 'Send RSVP confirmation to:', 'event-tickets' ); ?></header>
					<?php
					/**
					 * Allows injection of HTML before RSVP ticket confirmation fields
					 *
					 * @var array of Tribe__Tickets__Ticket_Object
					 */
					do_action( 'tribe_tickets_rsvp_before_confirmation_fields', $tickets );

					/**
					 * Set the default Full Name for the RSVP form
					 *
					 * @since 4.7.1
					 *
					 * @param string
					 */
					$name = apply_filters( 'tribe_tickets_rsvp_form_full_name', '' );

					/**
					 * Set the default value for the email on the RSVP form.
					 *
					 * @since 4.7.1
					 *
					 * * @param string
					 */
					$email = apply_filters( 'tribe_tickets_rsvp_form_email', '' );
					?>
					<table class="tribe-tickets-table">
						<tr class="tribe-tickets-full-name-row">
							<td>
								<label for="tribe-tickets-full-name"><?php esc_html_e( 'Full Name', 'event-tickets' ); ?>:</label>
							</td>
							<td colspan="3">
								<input type="text" name="attendee[full_name]" id="tribe-tickets-full-name" value="<?php echo esc_html( $name ); ?>">
							</td>
						</tr>
						<tr class="tribe-tickets-email-row">
							<td>
								<label for="tribe-tickets-email"><?php esc_html_e( 'Email', 'event-tickets' ); ?>:</label>
							</td>
							<td colspan="3">
								<input type="email" name="attendee[email]" id="tribe-tickets-email" value="<?php echo esc_html( $email ); ?>">
							</td>
						</tr>

						<tr class="tribe-tickets-order_status-row">
							<td>
								<label for="tribe-tickets-order_status"><?php echo esc_html( tribe_get_rsvp_label_singular( 'order_status_label' ) ); ?>:</label>
							</td>
							<td colspan="3">
								<?php Tribe__Tickets__Tickets_View::instance()->render_rsvp_selector( 'attendee[order_status]', '' ); ?>
							</td>
						</tr>

						<?php
						/**
						 * Use this filter to hide the Attendees List Optout
						 *
						 * @since 4.5.2
						 *
						 * @param bool
						 */
						$hide_attendee_list_optout = apply_filters( 'tribe_tickets_hide_attendees_list_optout', false );
						if ( ! $hide_attendee_list_optout
							 && class_exists( 'Tribe__Tickets_Plus__Attendees_List' )
							 && ! Tribe__Tickets_Plus__Attendees_List::is_hidden_on( get_the_ID() )
						) : ?>
							<tr class="tribe-tickets-attendees-list-optout">
								<td colspan="4">
									<input
										type="checkbox"
										name="attendee[optout]"
										id="tribe-tickets-attendees-list-optout"
									>
									<label for="tribe-tickets-attendees-list-optout">
										<?php esc_html_e( 'Don\'t list me on the public attendee list', 'event-tickets' ); ?>
									</label>
								</td>
							</tr>
						<?php endif; ?>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan="4" class="add-to-cart">
					<?php if ( $must_login ) : ?>
						<a href="<?php echo esc_url( Tribe__Tickets__Tickets::get_login_url() ); ?>">
							<?php esc_html_e( 'Login to RSVP', 'event-tickets' );?>
						</a>
					<?php else: ?>
						<input type="hidden" name="tribe_tickets_rsvp_submission" value="1" />
						<button
							type="submit"
							name="tickets_process"
							value="1"
							class="tribe-button tribe-button--rsvp"
						>
							<?php esc_html_e( 'Confirm RSVP', 'event-tickets' );?>
						</button>
					<?php endif; ?>
				</td>
			</tr>
		<?php endif; ?>
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
echo $content;

if ( $is_there_any_product ) {
	// If we have available tickets there is generally no need to display a 'tickets unavailable' message
	// for this post
	$this->do_not_show_tickets_unavailable_message();
} else {
	// Indicate that there are not any tickets, so a 'tickets unavailable' message may be
	// appropriate (depending on whether other ticket providers are active and have a similar
	// result)
	$this->maybe_show_tickets_unavailable_message( $tickets );
}
