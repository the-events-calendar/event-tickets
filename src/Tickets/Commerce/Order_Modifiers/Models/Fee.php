<?php
/**
 * Fee model.
 *
 * @since TBD
 *
 * @phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Models;

/**
 * Class Fee
 *
 * @since TBD
 */
class Fee extends Order_Modifier {

	/**
	 * The modifier type.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static string $order_modifier_type = 'fee';
}
