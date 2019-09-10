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

$passed_provider       = tribe_get_request_var('provider');
$passed_provider_class = tribe( 'tickets.attendee_registration.view' )->get_form_class( $passed_provider );
$provider_class = $passed_provider_class;
$providers = array_unique( wp_list_pluck( wp_list_pluck( $tickets, 'provider'), 'attendee_object') );
$has_tpp               = Tribe__Tickets__Commerce__PayPal__Main::ATTENDEE_OBJECT === $passed_provider || in_array( Tribe__Tickets__Commerce__PayPal__Main::ATTENDEE_OBJECT, $providers);
$event_id = get_the_id();
?>
<div class="tribe-block__tickets__item__attendee__fields">
	<h2 class="tribe-common-h3 tribe-common-h4--min-medium tribe-common-h--alt"><?php esc_html_e( 'Attendee Details', 'event-tickets' ); ?></h2>
	<form
		id="tribe-modal__attendee_registration"
		method="post"
		class="tribe-block__tickets__item__attendee__fields__form <?php echo sanitize_html_class( $provider_class ); ?>"
		name="<?php echo 'event' . esc_attr( $event_id ); ?>"
		novalidate
	>
		<?php foreach( $tickets as $ticket ) : ?>
		<div class="tribe-block__tickets__item__attendee__fields__container" data-ticket-id="<?php echo esc_attr( $ticket['id'] ); ?>">
			<h3 class="tribe-common-h5 tribe-common-h5--min-medium tribe-common-h--alt tribe-ticket__heading "><?php echo get_the_title( $ticket['id'] ); ?></h3>
		</div>
		<?php endforeach; ?>
		<input type="hidden" name="tribe_tickets_saving_attendees" value="1" />
		<div  class="tribe-block__tickets__item__attendee__fields__footer">
			<?php if ( $has_tpp ) : ?>
				<button type="submit name="checkout-button"><?php esc_html_e( 'Save and Checkout', 'event-tickets' ); ?></button>
			<?php else: ?>
				<button type="submit" class="tribe-common-c-btn-link tribe-common-c-btn--small tribe-block__tickets__item__attendee__fields__footer_submit" name="cart-button"><?php esc_html_e( 'Save and View Cart', 'event-tickets' ); ?></button>
				<span class="tribe-block__tickets__item__attendee__fields__footer__divider">or</span>
				<button type="submit" class="tribe-common-c-btn tribe-common-c-btn--small tribe-block__tickets__item__attendee__fields__footer_submit" name="checkout-button"><?php esc_html_e( 'Checkout Now', 'event-tickets' ); ?></button>
			<?php endif; ?>
		</div>
	</form>
</div>
