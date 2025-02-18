<?php
/**
 * Logic for handling coupons during the checkout process.
 *
 * @since   5.18.0
 * @since   TBD Updated with actual coupon logic.
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Checkout
 */

$coupon_input_classes = [
	'tribe-tickets__commerce-checkout-cart-coupons__input',
	'tribe-common-form-control-text__input',
	'tribe-tickets__form-field-input',
];

$apply_button_classes = [
	'tec-tickets__commerce-checkout-cart-coupons__apply-button',
	'tribe-common-c-btn',
];

?>
<div class="tribe-tickets__form tribe-tickets__commerce-checkout-coupons-wrapper tribe-common-b2">
	<div class="tec-tickets__commerce-checkout-cart-coupons">
		<input
			type="text"
			class="<?php tribe_classes( $coupon_input_classes ); ?>"
			id="coupon_input"
			name="coupons"
			aria-describedby="coupon_error"
			aria-label="<?php esc_attr_e( 'Enter coupon code', 'event-tickets' ); ?>"
			placeholder="<?php esc_attr_e( 'Enter coupon code', 'event-tickets' ); ?>"
		/>
		<button
			id="coupon_apply"
			class="<?php tribe_classes( $apply_button_classes ); ?>"
		>
			<?php echo esc_html_x( 'Apply', 'button to apply a coupon code', 'event-tickets' ); ?>
		</button>
		<p id="coupon_error" class="tec-tickets__commerce-checkout-cart-coupons__error" style="display: none; color: red;" aria-live="polite" role="alert">
			<?php esc_html_e( 'Invalid coupon code', 'event-tickets' ); ?>
		</p>
	</div>
	<div class="tec-tickets__commerce-checkout-cart-coupons__applied" style="display: none;">
	<span class="tec-tickets__commerce-checkout-cart-coupons__applied-text">
		<?php esc_html_e( 'Coupon:', 'event-tickets' ); ?> <span class="tec-tickets__commerce-checkout-cart-coupons__applied-value"></span> - <?php esc_html_e( 'Discount:', 'event-tickets' ); ?> <span class="tec-tickets__commerce-checkout-cart-coupons__applied-discount"></span>
	</span>
		<button class="tec-tickets__commerce-checkout-cart-coupons__remove-button" type="button">
			<?php echo esc_html_x( 'X', 'text for button to remove an applied coupon', 'event-tickets' ); // @todo use an ICON instead. @see https://github.com/the-events-calendar/event-tickets/pull/3430#discussion_r1872545653 ?>
		</button>
	</div>
</div>
