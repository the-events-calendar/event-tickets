<?php
/**
 * Logic for handling coupons during the checkout process.
 *
 * @since   5.18.0
 * @since   TBD Updated with actual coupon logic.
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Checkout
 *
 * @var array[] $items [Global] List of Items on the cart to be checked out.
 */

use TEC\Tickets\Commerce\Utils\Value;

$coupon_input_classes = [
	'tribe-tickets__commerce-checkout-cart-coupons__input',
	'tribe-common-form-control-text__input',
	'tribe-tickets__form-field-input',
];

$apply_button_classes = [
	'tec-tickets__commerce-checkout-cart-coupons__apply-button',
	'tribe-common-c-btn',
];

$coupon_items = array_filter( $items, fn( $item ) => 'coupon' === ( $item['type'] ?? '' ) );

// If we have any coupons, always use the first one. There *shouldn't* be more than one.
$coupon = ! empty( $coupon_items ) ? $coupon_items[ array_key_first( $coupon_items ) ] : [];

// Determine the discount to display.
$discount = '';
if ( isset( $coupon['sub_total'] ) && $coupon['sub_total'] instanceof Value ) {
	$discount = $coupon['sub_total']->get_currency();
}

$hide_input = ! empty( $coupon );
$hide_applied = empty( $coupon );

?>
<div class="tribe-tickets__form tribe-tickets__commerce-checkout-coupons-wrapper tribe-common-b2">
	<div class="tec-tickets__commerce-checkout-cart-coupons" <?php echo $hide_input ? 'style="display: none;"' : ''; ?>>
		<input
			type="text"
			class="<?php tribe_classes( $coupon_input_classes ); ?>"
			id="coupon_input"
			name="coupons"
			aria-describedby="coupon_error"
			aria-label="<?php esc_attr_e( 'Enter coupon code', 'event-tickets' ); ?>"
			placeholder="<?php esc_attr_e( 'Enter coupon code', 'event-tickets' ); ?>"
			value="<?php echo esc_attr( $coupon['slug'] ?? '' ); ?>"
		/>
		<button
			id="coupon_apply"
			class="<?php tribe_classes( $apply_button_classes ); ?>"
		>
			<?php echo esc_html_x( 'Apply', 'button to apply a coupon code', 'event-tickets' ); ?>
		</button>
	</div>
	<p id="coupon_error" class="tec-tickets__commerce-checkout-cart-coupons__error" style="display: none; color: red;" aria-live="polite" role="alert">
		<?php esc_html_e( 'Invalid coupon code', 'event-tickets' ); ?>
	</p>
	<div class="tec-tickets__commerce-checkout-cart-coupons__applied" <?php echo $hide_applied ? 'style="display: none;"' : ''; ?>>
		<span class="tec-tickets__commerce-checkout-cart-coupons__applied-text">
			<?php esc_html_e( 'Coupon:', 'event-tickets' ); ?>
			<span class="tec-tickets__commerce-checkout-cart-coupons__applied-value">
				<?php echo esc_html( $coupon['display_name'] ?? '' ); ?>
			</span>
			- <?php esc_html_e( 'Discount:', 'event-tickets' ); ?>
			<span class="tec-tickets__commerce-checkout-cart-coupons__applied-discount">
				<?php echo esc_html( $discount ); ?>
			</span>
		</span>
		<button class="tec-tickets__commerce-checkout-cart-coupons__remove-button" type="button">
			<?php echo esc_html_x( 'X', 'text for button to remove an applied coupon', 'event-tickets' ); // @todo use an ICON instead. @see https://github.com/the-events-calendar/event-tickets/pull/3430#discussion_r1872545653 ?>
		</button>
	</div>
</div>
