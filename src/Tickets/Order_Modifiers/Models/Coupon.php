<?php
/**
 * Coupon model.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\Models;

/**
 * Class Coupon
 *
 * @since TBD
 */
class Coupon extends Order_Modifier {

	/**
	 * The modifier type.
	 *
	 * @var string
	 */
	protected static string $order_modifier_type = 'coupon';
}
