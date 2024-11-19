<?php
/**
 * Fee model.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\Models;

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
