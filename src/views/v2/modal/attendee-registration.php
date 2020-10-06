<?php
/**
 * Modal: Registration-JS
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/modal/attendee-registration.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 */

$meta           = Tribe__Tickets_Plus__Main::instance()->meta();
$non_meta_count = 0;

if ( ! empty( $providers ) ) {
	$providers_arr  = array_unique( wp_list_pluck( $providers, 'attendee_object' ) );
	$provider       = $providers[0];
	$provider_class = $view->get_form_class( $providers_arr[0] );
	$has_tpp        = in_array( Tribe__Tickets__Commerce__PayPal__Main::ATTENDEE_OBJECT, $providers, true );
}

// Set the CSS classes.
$classes = [
	'tribe-tickets__item__attendee__fields__form',
	$provider_class,
	'tribe-validation',
];
?>
<div class="tribe-tickets__item__attendee__fields">

	<?php $this->template( 'v2/modal/attendee-registration/title' ); ?>

	<?php $this->template( 'v2/modal/attendee-registration/notice/error' ); ?>

	<div
		id="tribe-modal__attendee_registration"
		<?php tribe_classes( $classes ); ?>
		method="post"
		name="event<?php echo esc_attr( $post_id ); ?>"
		autocomplete="off"
		novalidate
	>
		<?php foreach ( $tickets as $ticket ) : ?>
			<?php
			// Only include tickets with meta.
			if ( ! $ticket->has_meta_enabled() ) {
				$non_meta_count++;
				continue;
			}
			?>
			<div class="tribe-tickets__item__attendee__fields__container" data-ticket-id="<?php echo esc_attr( $ticket->ID ); ?>">
				<h3 class="tribe-common-h5 tribe-common-h5--min-medium tribe-common-h--alt tribe-ticket__heading">
					<?php echo esc_html( get_the_title( $ticket->ID ) ); ?>
				</h3>
			</div>
		<?php endforeach; ?>

		<?php $this->template( 'v2/modal/attendee-registration/notice/non-ar', [ 'non_meta_count' => $non_meta_count ] ); ?>

		<input type="hidden" name="tribe_tickets_saving_attendees" value="1"/>
		<input type="hidden" name="tribe_tickets_ar" value="1"/>
		<input type="hidden" name="tribe_tickets_ar_data" value="" id="tribe_tickets_ar_data"/>

		<?php $this->template( 'v2/modal/attendee-registration/footer' ); ?>

	</form>

</div>
