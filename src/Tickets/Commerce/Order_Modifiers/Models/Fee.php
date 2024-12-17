<?php
/**
 * Fee model.
 *
 * @since 5.18.0
 *
 * @phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Models;

/**
 * Class Fee
 *
 * @since 5.18.0
 */
class Fee extends Order_Modifier {

	/**
	 * The modifier type.
	 *
	 * @since 5.18.0
	 *
	 * @var string
	 */
	protected static string $order_modifier_type = 'fee';
}
