<?php

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce\Utils\Value;

/**
 * @todo backend move common methods from Commerce/Order, Manual/Order and PayPal/Order here.
 */
abstract class Abstract_Order {

	/**
	 * Get a value object set with the combined price of a list of tickets.
	 *
	 * @since TBD
	 *
	 * @param int[]|float[] $items a list of values
	 *
	 * @return Value;
	 */
	public function get_value_total( $items ) {
		$sub_totals = Value::build_list( array_filter( wp_list_pluck( $items, 'sub_total' ) ) );
		$total_value = Value::create();
		return $total_value->total( $sub_totals );
	}
}