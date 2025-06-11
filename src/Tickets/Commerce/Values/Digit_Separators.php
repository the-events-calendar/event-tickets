<?php
/**
 * Digit Separators trait.
 *
 * @since 5.21.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Values;

/**
 * Trait Digit_Separators
 *
 * @since 5.21.0
 */
trait Digit_Separators {

	/**
	 * The decimal separator.
	 *
	 * @since 5.21.0
	 *
	 * @var string
	 */
	protected $decimal_separator;

	/**
	 * The thousands separator.
	 *
	 * @since 5.21.0
	 *
	 * @var string
	 */
	protected $thousands_separator;

	/**
	 * The value.
	 *
	 * @var Precision_Value
	 */
	protected $value;

	/**
	 * Default values.
	 *
	 * @since 5.21.0
	 *
	 * @var array
	 */
	protected static array $separator_defaults = [
		'decimal_separator'   => '.',
		'thousands_separator' => ',',
	];

	/**
	 * Set the default decimal and thousands separators.
	 *
	 * @since 5.21.0
	 *
	 * @param ?string $decimal_separator   The decimal separator.
	 * @param ?string $thousands_separator The thousands separator.
	 *
	 * @return void
	 */
	protected static function set_separator_defaults(
		?string $decimal_separator = null,
		?string $thousands_separator = null
	) {
		self::$separator_defaults = [
			'decimal_separator'   => $decimal_separator ?? self::$separator_defaults['decimal_separator'],
			'thousands_separator' => $thousands_separator ?? self::$separator_defaults['thousands_separator'],
		];
	}

	/**
	 * Get a number formatted with the decimal and thousands separators.
	 *
	 * @since 5.21.0
	 *
	 * @param float $number    The number to format.
	 * @param int   $precision The number of decimal places to include.
	 *
	 * @return string
	 */
	protected function get_formatted_number( float $number, int $precision ): string {
		return number_format( $number, $precision, $this->decimal_separator, $this->thousands_separator );
	}
}
