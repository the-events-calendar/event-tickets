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
		$decimal      = $decimal ?: tribe( \Tribe__Tickets__Commerce__Currency::class )->get_currency_locale( 'decimal_point' );
		$thousand_sep = $thousand_sep ?: tribe( \Tribe__Tickets__Commerce__Currency::class )->get_currency_locale( 'thousands_sep' );
		$number       = number_format( $value, 2, $decimal, $thousand_sep );
		$number       = (int) str_replace( [ $decimal, $thousand_sep ], '', $number );

		$sub_total = $number * $quantity;
		$sub_total = substr_replace( (string) $sub_total, $decimal, - 2, 0 );

		return number_format( $sub_total, 2, $decimal, $thousand_sep );
	}

	/**
	 * Taking an array of values it creates the sum of those values, it will not convert the values into float at any
	 * point, it will use full integers and strings to calculate, to avoid float point problems.
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
		$decimal      = $decimal ?: tribe( \Tribe__Tickets__Commerce__Currency::class )->get_currency_locale( 'decimal_point' );
		$thousand_sep = $thousand_sep ?: tribe( \Tribe__Tickets__Commerce__Currency::class )->get_currency_locale( 'thousands_sep' );

		$values = array_map( static function ( $value ) use ( $decimal, $thousand_sep ) {
			$number = number_format( $value, 2, $decimal, $thousand_sep );

			return (int) str_replace( [ $decimal, $thousand_sep ], '', $number );
		}, $values );


		$total = array_sum( array_filter( $values ) );
		$total = substr_replace( (string) $total, $decimal, - 2, 0 );

		return number_format( $total, 2, $decimal, $thousand_sep );
	}
}