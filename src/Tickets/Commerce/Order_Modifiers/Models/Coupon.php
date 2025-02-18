<?php
/**
 * Coupon model.
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Models;

use TEC\Tickets\Commerce\Order_Modifiers\Values\Percent_Value;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Precision_Value;

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
	 * @param float $ticket_price The ticket price.
	 *
	 * @return float The discount amount.
	 */
	public function get_discount_amount( $ticket_price ): float {
		if ( 'flat' === $this->sub_type ) {
			return $this->getAttribute( 'raw_amount' );
		}

		$base_price = new Precision_Value( $ticket_price );
		$discount   = $base_price->multiply( $this->attributes['raw_amount'] );

		return $discount->get();
	}
}
