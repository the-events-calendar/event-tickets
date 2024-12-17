<?php
/**
 * Logic for handling coupons during the checkout process.
 *
 * @since   5.18.0
 * @package TEC\Tickets\Commerce\Order_Modifiers\Checkout
 *
 * @private Still a work in progress.
 *
 * @phpcs:disable
 * @todo: Add the necessary logic to handle coupons during the checkout process.
 */

?>
<div class="tribe-tickets__commerce-checkout-cart-coupons">
	<label for="coupon_input" class="tribe-tickets__commerce-checkout-cart-coupons__label">
		<?php esc_html_e( 'Enter Coupon Code', 'event-tickets' ); ?>
	</label>
	<input type="text" class="tribe-tickets__commerce-checkout-cart-coupons__input" id="coupon_input" name="coupons" aria-describedby="coupon_error">
	<button id="coupon_apply" class="tribe-tickets__commerce-checkout-cart-coupons__apply-button">
		<?php esc_html_e( 'Apply', 'event-tickets' ); ?>
	</button>
	<p id="coupon_error" class="tribe-tickets__commerce-checkout-cart-coupons__error" style="display: none; color: red;" aria-live="polite" role="alert">
		<?php esc_html_e( 'Invalid Coupon Code', 'event-tickets' ); ?>
	</p>
</div>
<div class="tribe-tickets__commerce-checkout-cart-coupons__applied" style="display: none;">
	<span class="tribe-tickets__commerce-checkout-cart-coupons__applied-text">
		<?php esc_html_e( 'Coupon:', 'event-tickets' ); ?> <span class="tribe-tickets__commerce-checkout-cart-coupons__applied-value"></span> - <?php esc_html_e( 'Discount:', 'event-tickets' ); ?> <span class="tribe-tickets__commerce-checkout-cart-coupons__applied-discount"></span>
	</span>
	<button class="tribe-tickets__commerce-checkout-cart-coupons__remove-button" type="button">
		<?php esc_html_x( 'X', 'text for button to remove an applied coupon', 'event-tickets' ); // @todo use an ICON instead. @see https://github.com/the-events-calendar/event-tickets/pull/3430#discussion_r1872545653 ?>
	</button>
</div>
