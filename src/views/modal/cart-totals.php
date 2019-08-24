<?php
/**
 * Modal: Cart totals
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/modal/cart-totals.php
 *
 *
 * @since TBD
 *
 * @version TBD
 *
 */
?>
<div class="tribe-modal__cart__totals">
	<span class="tribe-modal__cart__qty__wrap">
		<span class="tribe-modal__cart__qty__label"><?php esc_html_e( 'Quantity', 'event-tickets'); ?>: </span>
		<span class="tribe-modal__cart__total__qty"></span>
	</span>
	<span class="tribe-modal__cart__total__amount__wrap">
		<span class="tribe-modal__cart__total__amount__label"><?php esc_html_e( 'Total', 'event-tickets'); ?>: </span>
		<span class="tribe-modal__cart__total__amount__currency__symbol"><?php echo tribe_get_option( 'defaultCurrencySymbol', '$' ); ?></span><span class="tribe-modal__cart__total__amount"></span>
	</span>
</div>