<?php
/**
 * This template renders the registration/purchase attendee fields
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration/content.php
 *
 * @since 4.9
 * @since 4.10.1 Update template paths to add the "registration/" prefix
 * @since 4.10.9 Add Filter to show an event/post tickets on AR Page
 *
 * @version 4.10.9
 *
 */
// If there are no events with tickets in cart, print the empty cart template
if ( empty( $events ) ) {
	$this->template( 'registration/cart-empty' );
	return;
}

$passed_provider = tribe_get_request_var('provider');
$passed_provider_class = $this->get_form_class( $passed_provider );
?>

<?php foreach ( $events as $event_id => $tickets ) :

	// Remove an event/post tickets if none have attendee registration.
	$show_tickets = tribe( 'tickets.attendee_registration' )->has_attendee_registration_enabled_in_array_of_tickets( $tickets );

	/**
	 * Filter to show an event/post tickets on Attendee Registration page regardless if they are enabled.
	 *
	 * @param boolean $show_tickets Rrue or false to show tickets for an event.
	 * @param array   $tickets      An array of ticket products.
	 * @param int     $event_id     The event/post ID.
	 *
	 * @since 4.10.9
	 */
	$show_tickets = apply_filters( 'tribe_tickets_filter_showing_tickets_on_attendee_registration', $show_tickets, $tickets, $event_id );

	if ( ! $show_tickets ) {
		continue;
	}


	$provider_class = $passed_provider_class;
	$providers = array_unique( wp_list_pluck( wp_list_pluck( $tickets, 'provider'), 'attendee_object') );

	if (  empty( $provider_class ) && ! empty( $providers[ $event_id ] ) ) {
		$provider_class = 'tribe-block__tickets__item__attendee__fields__form--' . $providers[ $event_id ];
	}

	$has_tpp = Tribe__Tickets__Commerce__PayPal__Main::ATTENDEE_OBJECT === $passed_provider || in_array( Tribe__Tickets__Commerce__PayPal__Main::ATTENDEE_OBJECT, $providers);
?>
	<div
		class="tribe-block__tickets__registration__event"
		data-event-id="<?php echo esc_attr( $event_id ); ?>"
		data-is-meta-up-to-date="<?php echo absint( $is_meta_up_to_date ); ?>"
	>
		<?php $this->template( 'registration/summary/content', array( 'event_id' => $event_id, 'tickets' => $tickets ) ); ?>

		<div class="tribe-block__tickets__registration__actions">
			<?php $this->template( 'registration/button-cart', array( 'event_id' => $event_id ) ); ?>
		</div>

		<div class="tribe-block__tickets__item__attendee__fields">

			<?php $this->template( 'registration/attendees/error', array( 'event_id' => $event_id, 'tickets' => $tickets ) ); ?>

			<form
				method="post"
				class="tribe-block__tickets__item__attendee__fields__form <?php echo sanitize_html_class( $provider_class ); ?>"
				name="<?php echo 'event' . esc_attr( $event_id ); ?>"
				novalidate
			>
				<?php $this->template( 'registration/attendees/content', array( 'event_id' => $event_id, 'tickets' => $tickets ) ); ?>
				<input type="hidden" name="tribe_tickets_saving_attendees" value="1" />
				<?php if ( $has_tpp ) : ?>
					<button type="submit"><?php esc_html_e( 'Save and Checkout', 'event-tickets' ); ?></button>
				<?php else: ?>
					<button type="submit"><?php esc_html_e( 'Save Attendee Info', 'event-tickets' ); ?></button>
				<?php endif; ?>

			</form>

			<?php $this->template( 'registration/attendees/error', array() ); ?>
			<?php $this->template( 'registration/attendees/success', array() ); ?>

			<?php $this->template( 'registration/attendees/loader', array() ); ?>

		</div>

	</div>

<?php endforeach; ?>

<?php $this->template( 'registration/button-checkout', array( 'checkout_url' => $checkout_url, 'cart_has_required_meta' => $cart_has_required_meta, 'is_meta_up_to_date' => $is_meta_up_to_date ) );
