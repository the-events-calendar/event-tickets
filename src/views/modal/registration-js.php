<?php
/**
 * Modal: Registration-JS
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/modal/registration-js.php
 *
 * @since TBD
 *
 * @version TBD
 *
 */

$passed_provider       = tribe_get_request_var( 'provider' );
$passed_provider_class = tribe( 'tickets.attendee_registration.view' )->get_form_class( $passed_provider );
$provider_class        = $passed_provider_class;
$providers             = array_unique( wp_list_pluck( wp_list_pluck( $tickets, 'provider' ), 'attendee_object' ) );
$has_tpp               = Tribe__Tickets__Commerce__PayPal__Main::ATTENDEE_OBJECT === $passed_provider || in_array( Tribe__Tickets__Commerce__PayPal__Main::ATTENDEE_OBJECT, $providers, true );
$event_id              = get_the_ID();
$meta                  = Tribe__Tickets_Plus__Main::instance()->meta();
$non_meta_count        = 0;
?>
<div class="tribe-tickets__item__attendee__fields">
	<h2 class="tribe-common-h3 tribe-common-h4--min-medium tribe-common-h--alt tribe-tickets__item__attendee__fields__title"><?php esc_html_e( 'Attendee Details', 'event-tickets' ); ?></h2>
	<div class="tribe-tickets-notice tribe-tickets-notice--error">
		<h3 class="tribe-common-h7 tribe-tickets-notice__title"><?php esc_html_e( 'Whoops', 'event-tickets' ); ?></h3>
		<p>
			<?php
				echo sprintf(
					_x(
						'You have %s ticket(s) with a field that requires information.',
						'Note about missing required fields, %s is the html-wrapped number of tickets.',
						'event-tickets'
					),
					'<span class="tribe-tickets-notice--error__count">1</span>'
				);
			?>
		</p>
	</div>
	<form
		id="tribe-modal__attendee_registration"
		method="post"
		class="tribe-tickets__item__attendee__fields__form <?php echo sanitize_html_class( $provider_class ); ?> tribe-validation"
		name="event<?php echo esc_attr( $event_id ); ?>"
		autocomplete="off"
		novalidate
	>
		<?php foreach ( $tickets as $ticket ) : ?>
			<?php
			// Only include tickets with meta
			$has_meta = get_post_meta( $ticket['id'], '_tribe_tickets_meta_enabled', true );

			if ( empty( $has_meta ) || ! tribe_is_truthy( $has_meta ) ) {
				$non_meta_count++;
				continue;
			}
			?>
				<div class="tribe-tickets__item__attendee__fields__container" data-ticket-id="<?php echo esc_attr( $ticket['id'] ); ?>">
					<h3 class="tribe-common-h5 tribe-common-h5--min-medium tribe-common-h--alt tribe-ticket__heading">
						<?php echo esc_html( get_the_title( $ticket['id'] ) ); ?>
					</h3>
				</div>
		<?php endforeach; ?>
		<p class="tribe-tickets-notice tribe-tickets-notice--non-ar">
			<?php
				echo sprintf(
					_x(
						'There are %s other tickets in your cart that do not require attendee information.',
						'Note that there are more tickets in the cart, %s is the html-wrapped number.',
						'event-tickets'
					),
					'<span id="tribe-tickets__non-ar-count">' . absint( $non_meta_count ) . '</span>'
				);
			?>
		</p>
		<input type="hidden" name="tribe_tickets_saving_attendees" value="1" />
		<div  class="tribe-tickets__item__attendee__fields__footer">
			<?php if ( $has_tpp ) : ?>
				<button type="submit name="checkout-button"><?php esc_html_e( 'Save and Checkout', 'event-tickets' ); ?></button>
			<?php else: ?>
				<button
					type="submit"
					class="tribe-common-c-btn-link tribe-common-c-btn--small tribe-block__tickets__item__attendee__fields__footer_submit tribe-validation-submit"
					name="cart-button"
					>
						<?php esc_html_e( 'Save and View Cart', 'event-tickets' ); ?>
				</button>
				<span class="tribe-block__tickets__item__attendee__fields__footer__divider"><?php esc_html_e( 'or', 'event-tickets' ); ?></span>
				<button
					type="submit"
					class="tribe-common-c-btn tribe-common-c-btn--small tribe-block__tickets__item__attendee__fields__footer_submit tribe-validation-submit"
					name="checkout-button"
					>
						<?php esc_html_e( 'Checkout Now', 'event-tickets' ); ?>
					</button>
			<?php endif; ?>
		</div>
	</form>
</div>
