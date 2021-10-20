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
	 * The precision to use in decimal places. This is currently statically set to 2,
	 * but may become variable for supporting 3 digit decimals
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private static $precision = 2;

	/**
	 * Taking a given numerical price it will multiply the by the quantity passed it will not convert the values into
	 * float at any point, it will use full integers and strings to calculate, to avoid float point problems.
	 *
	 * This function expects that the incoming value will be either an integer with decimals as the last 2 digits
	 * or a formatted string using the same decimal and thousands separators as set in the system.
	 *
	 * Currently, we only allow two decimal digits.
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

		$number    = static::to_integer( $value, $decimal, $thousand_sep );
		$sub_total = static::to_decimal( $number * $quantity );

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
			return static::to_integer( $value, $decimal, $thousand_sep );
		}, $values );

		$total = array_sum( $values );
		$total = static::to_decimal( $total );

		return number_format( $total, 2, $decimal, $thousand_sep );
	}

	/**
	 * Removes decimal and thousands separator from a numeric string, transforming it into an int
	 *
	 * @todo  currently this requires that the $value be formatted using $decimal and $thousand_sep, which
	 *      can be an issue in migrated sites, or sites that changed number formatting. It will also fail if
	 *      $value is a float and neither $decimal or $thousand_sep are '.'.
	 *        We should expand this to remove any possible combination of decimal/thousands marks from numbers.
	 *
	 * @since TBD
	 *
	 * @param string $value        Numeric value to clean.
	 * @param string $decimal      Which Decimal separator.
	 * @param string $thousand_sep Which thousand separator.
	 *
	 * @return int
	 */
	public static function to_integer( $value, $decimal, $thousand_sep ) {

		// If the string is formatted with thousands separators but not with decimals, pad with decimals
		if ( false !== strpos( $value, $thousand_sep ) && false === strpos( $value, $decimal ) ) {
			$value = $value . '.00';
		}

		// We're done with thousands separators
		$value = str_replace( $thousand_sep, '', $value );

		// If the last char on the value is a decimal point, pad with two zeros
		if ( substr( $value, - 1 ) === $decimal ) {
			$value = $value . '00';
		}

		$value_arr = explode( $decimal, $value );

		// If the decimal part is longer than the precision, round it to the precision
		if ( isset( $value_arr[1] ) && strlen( $value_arr[1] ) > static::$precision ) {
			$rounded = round( $value, static::$precision );

			// If the decimal part should end w/ zeros after rounding
			// those zeros are now lost, so we add them back here
			$rounded_arr = explode( $decimal, $rounded );
			if ( isset( $rounded_arr[1] ) && strlen( $rounded_arr[1] ) < static::$precision ) {
				$rounded = str_pad( $rounded, ( strlen( $rounded_arr[0] ) + 1 + static::$precision ), '0' );
			}

			$value = $rounded;
		}

		return (int) str_replace( $decimal, '', $value );
	}

	/**
	 * Converts an int, float or numerical string to a float with the specified precision.
	 *
	 * @since TBD
	 *
	 * @param int|float|string $total     the total value to convert
	 * @param int              $precision the number of decimal values to keep
	 *
	 * @return float
	 */
	public static function to_decimal( $total ) {
		return round( $total / pow( 10, static::$precision ), static::$precision );
	}

	/**
	 * Takes a float, formats it to the proper separators, then format as currency
	 *
	 * @since TBD
	 *
	 * @param float  $value        The value to format.
	 * @param string $decimal      Which Decimal separator.
	 * @param string $thousand_sep Which thousand separator.
	 *
	 * @return string
	 */
	public static function to_string( $value, $decimal = '.', $thousand_sep = ',' ) {
		if ( ! is_float( $value ) ) {
			$value = static::to_decimal( static::to_integer( $value, $decimal, $thousand_sep ) );
		}

		return number_format( $value, static::$precision, $decimal, $thousand_sep );
	}

	/**
	 * Takes a string and formats it to the proper currency value
	 *
	 * @since TBD
	 *
	 * @param string $value The value to format.
	 *
	 * @return string
	 */
	public static function to_currency( $value ) {
		return tribe( 'tickets.commerce.paypal.currency' )->format_currency( $value );
	}
}