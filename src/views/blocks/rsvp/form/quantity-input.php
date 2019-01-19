<?php
/**
 * Block: RSVP
 * Form Quantity Input
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/form/quantity-input.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @version 4.9.4
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
