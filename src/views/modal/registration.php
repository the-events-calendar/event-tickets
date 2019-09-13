<?php
/**
 * Modal: Registration
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/modal/registration.php
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
	<h2 class="tribe-common-h3 tribe-common-h4--min-medium tribe-common-h--alt tribe-block__tickets__item__attendee__fields__title"><?php esc_html_e( 'Attendee Details', 'event-tickets' ); ?></h2>
	<form
		method="post"
		class="tribe-block__tickets__item__attendee__fields__form <?php echo sanitize_html_class( $provider_class ); ?>"
		name="<?php echo 'event' . esc_attr( $event_id ); ?>"
		novalidate
	>
		<?php $template->template( 'registration/attendees/content', [ 'event_id' => $event_id, 'tickets' => $tickets ] ); ?>
		<input type="hidden" name="tribe_tickets_saving_attendees" value="1" />
		<?php if ( $has_tpp ) : ?>
			<button type="submit"><?php esc_html_e( 'Save and Checkout', 'event-tickets' ); ?></button>
		<?php else: ?>
			<button type="submit"><?php esc_html_e( 'Save Attendee Info', 'event-tickets' ); ?></button>
		<?php endif; ?>

	</form>

</div>
