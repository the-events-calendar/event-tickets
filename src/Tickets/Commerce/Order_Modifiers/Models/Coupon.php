<?php
/**
 * Coupon model.
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Models;

use TEC\Tickets\Commerce\Values\Percent_Value;
use TEC\Tickets\Commerce\Values\Precision_Value;

/**
 * Class Coupon
 *
 * @since 5.18.0
 *
 * @property Percent_Value $raw_amount The raw amount.
 */
class Coupon extends Order_Modifier {

	/**
	 * The modifier type.
	 *
	 * @var string
	 */
	protected static string $order_modifier_type = 'coupon';

	/**
	 * Get the discount amount.
	 *
	 * @since TBD
	 *
	 * @param float $subtotal The price that should be used to calculate the discount.
	 *
	 * @return float The discount amount as a negative number.
	 */
	public function get_discount_amount( float $subtotal ): float {
		if ( 'flat' === $this->sub_type ) {
			return -1 * $this->raw_amount;
		}

		$base_price = new Precision_Value( $subtotal );
		$discount   = $base_price->multiply( $this->attributes['raw_amount'] );

		return -1 * $discount->get();
	}
}
