<?php
/**
 * Coupon model.
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Models;

/**
 * Class Coupon
 *
 * @since 5.18.0
 */
class Coupon extends Order_Modifier {

	/**
	 * The modifier type.
	 *
	 * @var string
	 */
	protected static string $order_modifier_type = 'coupon';
}
