<?php
/**
 * Coupon model.
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Models;

use TEC\Tickets\Commerce\Cart\Cart_Interface;
use TEC\Tickets\Commerce\Traits\Type;
use TEC\Tickets\Commerce\Values\Percent_Value;
use TEC\Tickets\Commerce\Values\Precision_Value;

/**
 * Class Coupon
 *
 * @since 5.18.0
 */
class Coupon extends Order_Modifier {

	use Type;

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

	/**
	 * Add the coupon to the cart.
	 *
	 * @since TBD
	 *
	 * @param Cart_Interface $cart     The cart repository.
	 * @param int            $quantity The quantity.
	 */
	public function add_to_cart( Cart_Interface $cart, int $quantity = 1 ) {
		$cart->upsert_item(
			$this->get_unique_type_id( $this->id, 'coupon' ),
			$quantity,
			[ 'type' => 'coupon' ]
		);
	}

	/**
	 * Remove the coupon from the cart.
	 *
	 * @since TBD
	 *
	 * @param Cart_Interface $cart The cart repository.
	 *
	 * @return void
	 */
	public function remove_from_cart( Cart_Interface $cart ) {
		$cart->remove_item( $this->get_unique_type_id( $this->id, 'coupon' ) );
	}
}
