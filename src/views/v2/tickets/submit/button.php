<?php
/**
 * Block: Tickets
 * Submit Button
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/submit/button.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var bool   $must_login If the login is required to purchase tickets.
 * @var string $submit_button_name The name of the button.
 */

/** @var \Tribe__Tickets__Attendee_Registration__Main $attendee_registration */
$attendee_registration = tribe( 'tickets.attendee_registration' );

if (
	! empty( $must_login )
	|| $attendee_registration->is_modal_enabled()
) {
	return;
}

/**
 * Allow filtering of the button classes for the tickets block.
 *
 * @since 4.11.3
 *
 * @param array $button_name The button classes.
 */
$button_classes = apply_filters(
	'tribe_tickets_ticket_block_submit_classes',
	[
		'tribe-common-c-btn',
		'tribe-common-c-btn--small',
		'tribe-tickets__buy',
	]
);
?>
<button
	<?php tribe_classes( $button_classes ) ?>
	id="tribe-tickets__buy"
	type="submit"
	name="<?php echo esc_html( $submit_button_name ); ?>"
	<?php tribe_disabled( true ); ?>
>
	<?php
	/* translators: %s: Tickets label */
	echo esc_html( sprintf( _x( 'Get %s', 'Add tickets to cart.', 'event-tickets' ), tribe_get_ticket_label_plural( 'event-tickets' ) ) );
	?>
</button>
