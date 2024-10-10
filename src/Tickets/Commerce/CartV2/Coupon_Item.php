<?php
/**
 * Coupon item class for Cart.
 *
 * @package TEC\Tickets\Commerce\CartV2
 */

namespace TEC\Tickets\Commerce\CartV2;

/**
 * Class Coupon_Item
 *
 * Represents a coupon item in the cart.
 */
class Coupon_Item extends Abstract_Item {
	/**
	 * @var string The type of the item, which is 'coupon'.
	 */
	protected $type = 'coupon';

	/**
	 * Calculates the amount for the coupon item, potentially based on a subtotal.
	 *
	 * @param int|null $subtotal Optional. The subtotal value to base the calculation on. Default null.
	 *
	 * @return int The calculated discount amount in cents (negative value).
	 */
	public function get_amount( ?int $subtotal = null ): int {
		if ( $this->sub_type === 'percent' && $subtotal !== null ) {
			// Calculate percentage discount based on the subtotal.
			return -( $subtotal * ( $this->value / 100 ) );
		}

		// Flat discount in cents.
		return -$this->value;
	}

	/**
	 * Checks if the coupon item should be counted in the subtotal.
	 *
	 * @return bool False, as coupons should not be included in the subtotal calculation.
	 */
	public function is_counted_in_subtotal(): bool {
		// Coupons should not be counted in the subtotal.
		return false;
	}

	/**
	 * Checks if the coupon item is in stock.
	 *
	 * @return bool True if the coupon is in stock, false otherwise.
	 */
	public function is_in_stock(): bool {
		return true;
	}
}
