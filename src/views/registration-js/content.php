<?php
/**
 * This template renders the registration/purchase attendee fields
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration-js/content.php
 *
 * @since TBD
 *
 * @version TBD
 *
 */

$passed_provider = tribe_get_request_var( 'provider' );
$passed_provider_class = $this->get_form_class( $passed_provider );
?>
<?php foreach ( $events as $event_id => $tickets ) : ?>

	<?php
		$provider_class = $passed_provider_class;
		$providers = array_unique( wp_list_pluck( wp_list_pluck( $tickets, 'provider' ), 'attendee_object' ) );

		if (  empty( $provider_class ) && ! empty( $providers[ $event_id ] ) ) {
			$provider_class = 'tribe-tickets__item__attendee__fields__form--' . $providers[ $event_id ];
		}

		$has_tpp = Tribe__Tickets__Commerce__PayPal__Main::ATTENDEE_OBJECT === $passed_provider || in_array( Tribe__Tickets__Commerce__PayPal__Main::ATTENDEE_OBJECT, $providers, true );
	?>
	<div
		class="tribe-tickets__registration__event"
		data-event-id="<?php echo esc_attr( $event_id ); ?>"
		data-is-meta-up-to-date="<?php echo absint( $is_meta_up_to_date ); ?>"
	>
		<?php $this->template( 'registration/summary/content', array( 'event_id' => $event_id, 'tickets' => $tickets ) ); ?>

		<div class="tribe-tickets__registration__actions">
			<?php $this->template( 'registration/button-cart', array( 'event_id' => $event_id ) ); ?>
		</div>

		<div class="tribe-tickets__item__attendee__fields">

			<?php $this->template( 'registration-js/attendees/error', array( 'event_id' => $event_id, 'tickets' => $tickets ) ); ?>

			<form
				method="post"
				class="tribe-tickets__item__attendee__fields__form <?php echo sanitize_html_class( $provider_class ); ?> tribe-validation"
				name="event<?php echo esc_attr( $event_id ); ?>"
				novalidate
			>
				<input type="hidden" name="tribe_tickets_saving_attendees" value="1" />
				<?php if ( $has_tpp ) : ?>
					<button type="submit"><?php esc_html_e( 'Save and Checkout', 'event-tickets' ); ?></button>
				<?php else: ?>
					<button type="submit"><?php esc_html_e( 'Save Attendee Info', 'event-tickets' ); ?></button>
				<?php endif; ?>
			</form>

		</div>

	</div>

<?php endforeach; ?>
