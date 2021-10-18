<?php

namespace TEC\Tickets\Commerce\Utils;

/**
 * Class Price
 *
 * @since 5.1.9
 *
 */
class Price {

	/**
	 * Taking a given numerical price it will multiply the by the quantity passed it will not convert the values into
	 * float at any point, it will use full integers and strings to calculate, to avoid float point problems.
	 *
	 * This function expects that the incoming value will be either an integer with decimals as the last 2 digits
	 * or a formatted string using the same decimal and thousands separators as set in the system.
	 *
	 * We only allow two decimal points.
	 *
	 * @since 5.1.9
	 *
	 * @param string      $value        Which value we are going to multiply for the subtotal.
	 * @param int         $quantity     Quantity that the value will be multiplied..
	 * @param null|string $decimal      Which Decimal separator.
	 * @param null|string $thousand_sep Which thousand separator.
	 *
	 * @return string
	 */
	public static function sub_total( $value, $quantity, $decimal = null, $thousand_sep = null ) {
		$decimal      = ! is_null( $decimal ) ? $decimal : tribe( \Tribe__Tickets__Commerce__Currency::class )->get_currency_locale( 'decimal_point' );
		$thousand_sep = ! is_null( $thousand_sep ) ? $thousand_sep : tribe( \Tribe__Tickets__Commerce__Currency::class )->get_currency_locale( 'thousands_sep' );

		$number    = static::clean_formatting( $value, $decimal, $thousand_sep );
		$sub_total = static::convert_to_decimal( $number * $quantity );

		return number_format( $sub_total, 2, $decimal, $thousand_sep );
	}

	/**
	 * Taking an array of values it creates the sum of those values, it will not convert the values into float at any
	 * point, it will use full integers and strings to calculate, to avoid float point problems.
	 *
	 * This function expects that the incoming values will be either integers with decimals as the last 2 digits
	 * or formatted strings using the same decimal and thousands separators as set in the system.
	 *
	 * We only allow two decimal points.
	 *
	 * @since 5.1.9
	 *
	 * @param array       $values       Values that need to be summed.
	 * @param null|string $decimal      Which Decimal separator.
	 * @param null|string $thousand_sep Which thousand separator.
	 *
	 * @return string
	 */
	public static function total( array $values, $decimal = null, $thousand_sep = null ) {
		$decimal      = ! is_null( $decimal ) ? $decimal : tribe( \Tribe__Tickets__Commerce__Currency::class )->get_currency_locale( 'decimal_point' );
		$thousand_sep = ! is_null( $thousand_sep ) ? $thousand_sep : tribe( \Tribe__Tickets__Commerce__Currency::class )->get_currency_locale( 'thousands_sep' );

		$values = array_map( static function ( $value ) use ( $decimal, $thousand_sep ) {
			return static::clean_formatting( $value, $decimal, $thousand_sep );
		}, $values );

		$total = array_sum( $values );
		$total = static::convert_to_decimal( $total );

		return number_format( $total, 2, $decimal, $thousand_sep );
	}

	/**
	 * Removes decimal and thousands separator from a numeric string, transforming it into an int
	 *
	 * @todo currently this requires that the $value be formatted using $decimal and $thousand_sep, which
	 *       can be an issue in migrated sites, or sites that changed number formatting. If $value is a
	 *       float and neither $decimal or $thousand_sep are '.'. We should expand this to remove any
	 *         possible combination of decimal/thousands marks from numbers.
	 *
	 * @param array       $value        Numeric value to clean.
	 * @param null|string $decimal      Which Decimal separator.
	 * @param null|string $thousand_sep Which thousand separator.
	 *
	 * @return int
	 */
	private static function clean_formatting( $value, $decimal, $thousand_sep ) {
		return (int) str_replace( [ $decimal, $thousand_sep ], '', $value );
	}

	/**
	 * Converts an int, float or numerical string to a float with the specified precision.
	 *
	 * @param int|float|string $total     the total value to convert
	 * @param int              $precision the number of decimal values to keep
	 *
	 * @return float
	 */
	private static function convert_to_decimal( $total, $precision = 2 ) {
		return round( $total / pow( 10, $precision ), $precision );
	}
}