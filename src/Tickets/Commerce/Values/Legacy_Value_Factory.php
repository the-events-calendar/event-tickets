<?php
/**
 * Legacy Value Factory.
 *
 * @since 5.18.0
 */

namespace TEC\Tickets\Commerce\Values;

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

	/**
	 * Convert a legacy value object to a Precision_Value object.
	 *
	 * @since 5.21.0
	 *
	 * @param LegacyValue $value The value to convert.
	 *
	 * @return Precision_Value The new value object.
	 */
	public static function to_precision_value( LegacyValue $value ): Precision_Value {
		return new Precision_Value( $value->get_float(), $value->get_precision() );
	}

	/**
	 * Convert a legacy value object to a Currency_Value object.
	 *
	 * @since 5.21.0
	 *
	 * @param LegacyValue $value The value to convert.
	 *
	 * @return Currency_Value The new value object.
	 */
	public static function to_currency_value( LegacyValue $value ): Currency_Value {
		return Currency_Value::create(
			static::to_precision_value( $value ),
			$value->get_currency_symbol(),
			$value->get_currency_separator_thousands(),
			$value->get_currency_separator_decimal(),
			$value->get_currency_symbol_position()
		);
	}
}
