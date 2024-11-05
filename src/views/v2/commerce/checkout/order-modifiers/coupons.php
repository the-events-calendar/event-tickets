<?php
/**
 * Logic for handling coupons during the checkout process.
 *
 * @since   TBD
 * @package TEC\Tickets\Commerce\Order_Modifiers\Checkout
 *
 * @private Still a work in progress.
 *
 * @phpcs:disable
 * @todo: Add the necessary logic to handle coupons during the checkout process.
 */

?>
<div class="tribe-tickets__commerce-checkout-cart-coupons">
	<label for="coupon_input" class="tribe-tickets__commerce-checkout-cart-coupons__label">Enter Coupon Code</label>
	<input type="text" class="tribe-tickets__commerce-checkout-cart-coupons__input" id="coupon_input" name="coupons" aria-describedby="coupon_error">
	<button id="coupon_apply" class="tribe-tickets__commerce-checkout-cart-coupons__apply-button">Apply</button>
	<p id="coupon_error" class="tribe-tickets__commerce-checkout-cart-coupons__error" style="display: none; color: red;" aria-live="polite" role="alert">Invalid Coupon Code</p>
</div>
<div class="tribe-tickets__commerce-checkout-cart-coupons__applied" style="display: none;">
	<span class="tribe-tickets__commerce-checkout-cart-coupons__applied-text">
		Coupon: <span class="tribe-tickets__commerce-checkout-cart-coupons__applied-value"></span> - Discount: <span class="tribe-tickets__commerce-checkout-cart-coupons__applied-discount"></span>
	</span>
	<button class="tribe-tickets__commerce-checkout-cart-coupons__remove-button" type="button">X</button>
</div>
