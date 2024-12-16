<?php
/**
 * Legacy Value Factory.
 *
 * @since 5.18.0
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Values;

use TEC\Tickets\Commerce\Utils\Value as LegacyValue;

/**
 * Class Legacy_Value_Factory
 *
 * @since 5.18.0
 */
class Legacy_Value_Factory {

	/**
	 * Convert a value object to a legacy value object.
	 *
	 * @since 5.18.0
	 *
	 * @param Value_Interface $value The value to convert.
	 *
	 * @return LegacyValue The legacy value object.
	 */
	public static function to_legacy_value( Value_Interface $value ): LegacyValue {
		$new_value = LegacyValue::create( $value->get() );
		if ( $value instanceof Precision_Value ) {
			$new_value->set_precision( $value->get_precision() );
		}

		return $new_value;
	}
}
