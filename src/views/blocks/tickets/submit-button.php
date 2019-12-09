<?php
/**
 * Block: Tickets
 * Submit Button
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/submit-button.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9.3
 *
 * @version 4.11
 *
 */
/**
 * Allow filtering of the button name for the tickets block.
 *
 * @since 4.11
 *
 * @param string $button_name The button name. Set to cart-button to send to cart on submit, or set to checkout-button to send to checkout on submit.
 */
$button_name = apply_filters( 'tribe_tickets_ticket_block_submit', 'cart-button' );
?>
<button
	class="tribe-common-c-btn tribe-common-c-btn--small tribe-tickets__buy"
	type="submit"
	<?php if ( $button_name ) : ?>
		name="<?php echo esc_html( $button_name ); ?>"
	<?php endif; ?>

>
	<?php echo esc_html_x( 'Get Tickets', 'Add tickets to cart.', 'event-tickets' ); ?>
</button>
