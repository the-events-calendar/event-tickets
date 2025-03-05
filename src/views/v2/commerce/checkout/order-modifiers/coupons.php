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
use TEC\Tickets\Commerce\Values\Legacy_Value_Factory;

// Filter coupon items. If we have any coupons, always use the first one. There *shouldn't* be more than one.
$coupon_items = array_filter( $items, fn( $item ) => 'coupon' === ( $item['type'] ?? '' ) );
$coupon       = array_shift( $coupon_items ) ?? [];

// Determine the discount to display.
$discount = '';
if ( isset( $coupon['sub_total'] ) && $coupon['sub_total'] instanceof Value ) {
	$discount = Legacy_Value_Factory::to_currency_value( $coupon['sub_total'] )->get();
}

// Set up classes for all of the elements.
$apply_button_classes = [
	'tec-tickets__commerce-checkout-cart-coupons__apply-button',
	'tribe-common-c-btn',
];

$input_container_classes = [
	'tec-tickets__commerce-checkout-cart-coupons',
	'tribe-common-a11y-hidden',
];

$add_coupon_link_classes = [
	'tec-tickets__commerce-checkout-cart-coupons__add-coupon-link' => true,
	'tribe-common-a11y-hidden'                                     => ! empty( $coupon ),
];

$applied_container_classes = [
	'tec-tickets__commerce-checkout-cart-coupons__applied' => true,
	'tribe-common-a11y-hidden'                             => empty( $coupon ),
];

?>
<div class="tec-tickets__commerce-checkout-coupons-wrapper tribe-tickets__form tribe-common-b2">
	<p <?php tribe_classes( $add_coupon_link_classes ); ?>>
		<button>
			<?php esc_html_e( 'Add coupon code', 'event-tickets' ); ?>
		</button>
	</p>
	<div <?php tribe_classes( $input_container_classes ); ?>>
		<input
			class="tec-tickets__commerce-checkout-cart-coupons__input"
			type="text"
			id="coupon_input"
			name="coupons"
			aria-describedby="coupon_error"
			aria-label="<?php esc_attr_e( 'Enter coupon code', 'event-tickets' ); ?>"
			placeholder="<?php esc_attr_e( 'Enter coupon code', 'event-tickets' ); ?>"
			value="<?php echo esc_attr( $coupon['slug'] ?? '' ); ?>"
		/>
		<button
			id="coupon_apply"
			<?php tribe_classes( $apply_button_classes ); ?>
		>
			<?php echo esc_html_x( 'Apply', 'button to apply a coupon code', 'event-tickets' ); ?>
		</button>
	</div>
	<p id="coupon_error" class="tec-tickets__commerce-checkout-cart-coupons__error" style="display: none; color: red;" aria-live="polite" role="alert">
		<?php esc_html_e( 'Invalid coupon code', 'event-tickets' ); ?>
	</p>
	<div <?php tribe_classes( $applied_container_classes ); ?>>
		<ul>
			<li>
				<span class="tec-tickets__commerce-checkout-cart-coupons__applied-text tribe-tickets__commerce-checkout-cart-footer-quantity-label">
					<span class="tec-tickets__commerce-checkout-cart-coupons__applied-label">
						<?php echo esc_html( $coupon['slug'] ?? '' ); ?>
					</span>
					<button class="tec-tickets__commerce-checkout-cart-coupons__remove-button" type="button">
						<img
							src="<?php echo esc_url( tribe_resource_url( 'images/icons/close.svg', false, null, Tribe__Main::instance() ) ); ?>"
							alt="<?php esc_attr_e( 'Close icon', 'event-tickets' ); ?>"
							title="<?php esc_attr_e( 'Remove coupon', 'event-tickets' ); ?>"
						>
					</button>
				</span>
				<span class="tec-tickets__commerce-checkout-cart-coupons__applied-discount tribe-tickets__commerce-checkout-cart-footer-quantity-number">
					<?php echo esc_html( $discount ); ?>
				</span>
			</li>
		</ul>
	</div>
</div>
