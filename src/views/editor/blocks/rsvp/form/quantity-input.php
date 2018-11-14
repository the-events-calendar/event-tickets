<?php
/**
 * This template renders the RSVP ticket form quantity input
 *
 * @version 0.3.0-alpha
 *
 */
$must_login = ! is_user_logged_in() && tribe( 'tickets.rsvp' )->login_required();
$remaining  = $ticket->remaining();
?>
<input
	type="number"
	name="quantity_<?php echo absint( $ticket->ID ); ?>"
	class="tribe-tickets-quantity"
	step="1"
	min="1"
	value="1"
	required
	data-remaining="<?php echo esc_attr( $remaining ); ?>"
	<?php if ( -1 !== $remaining ) : ?>
		max="<?php echo esc_attr( $remaining ); ?>"
	<?php endif; ?>
	<?php disabled( $must_login ); ?>
/>