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

	/**
	 * Builds a new model from a query builder object.
	 *
	 * This method overrides the parent to document the return type.
	 *
	 * @since TBD
	 *
	 * @param object $object The object to build the model from.
	 *
	 * @return Fee
	 */
	public static function fromQueryBuilderObject( $object ) {
		return parent::fromQueryBuilderObject( $object );
	}
}
