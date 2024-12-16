<?php
/**
 * Currency Value
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Values;

use TEC\Tickets\Commerce\Order_Modifiers\Traits\Stringify;

/**
 * Class Currency_Value
 *
 * @since 5.18.0
 */
class Currency_Value extends Base_Value {
	/**
	 * The currency symbol.
	 *
	 * @var string
	 */
	protected $currency_symbol;

	/**
	 * The currency symbol position.
	 *
	 * @var string
	 */
	protected $currency_symbol_position;

	/**
	 * The thousands separator.
	 *
	 * @var string
	 */
	protected $thousands_separator;

	/**
	 * The decimal separator.
	 *
	 * @var string
	 */
	protected $decimal_separator;

	/**
	 * Default values.
	 *
	 * @var array
	 */
	protected static array $defaults = [
		'currency_symbol'          => '$',
		'thousands_separator'      => ',',
		'decimal_separator'        => '.',
		'currency_symbol_position' => 'before',
	];

	/**
	 * Currency_Value constructor.
	 *
	 * @since 5.18.0
	 *
	 * @param Precision_Value $value                    The value to store.
	 * @param string          $currency_symbol          The currency symbol.
	 * @param string          $thousands_separator      The thousands separator.
	 * @param string          $decimal_separator        The decimal separator.
	 * @param string          $currency_symbol_position The currency symbol position.
	 */
	public function __construct(
		Precision_Value $value,
		string $currency_symbol = '$',
		string $thousands_separator = ',',
		string $decimal_separator = '.',
		string $currency_symbol_position = 'before'
	) {
		$this->value                    = $value;
		$this->currency_symbol          = $currency_symbol;
		$this->thousands_separator      = $thousands_separator;
		$this->decimal_separator        = $decimal_separator;
		$this->currency_symbol_position = $currency_symbol_position;
	}

	/**
	 * Get the formatted value.
	 *
	 * @since 5.18.0
	 *
	 * @return string The value.
	 */
	public function get(): string {
		$formatted = number_format(
			$this->value->get(),
			$this->value->get_precision(),
			$this->decimal_separator,
			$this->thousands_separator
		);

		switch ( $this->currency_symbol_position ) {
			case 'after':
				return "{$formatted}{$this->currency_symbol}";

			case 'before':
			default:
				return "{$this->currency_symbol}{$formatted}";
		}
	}

	/**
	 * Get the raw value.
	 *
	 * This returns a clone of the value to prevent mutation.
	 *
	 * @since 5.18.0
	 *
	 * @return Precision_Value The raw value.
	 */
	public function get_raw_value(): Precision_Value {
		return clone $this->value;
	}

	/**
	 * Create a new instance of the class.
	 *
	 * @since 5.18.0
	 *
	 * @param Precision_Value $value The value to store.
	 *
	 * @return Currency_Value The new instance.
	 */
	public static function create( Precision_Value $value ): self {
		return new self(
			$value,
			self::$defaults['currency_symbol'],
			self::$defaults['thousands_separator'],
			self::$defaults['decimal_separator'],
			self::$defaults['currency_symbol_position']
		);
	}

	/**
	 * Set the default values for the class.
	 *
	 * Use this to allow for setting default values for all instances of this class
	 * that are created with the create() method.
	 *
	 * @since 5.18.0
	 *
	 * @param ?string $currency_symbol          The currency symbol.
	 * @param ?string $thousands_separator      The thousands separator.
	 * @param ?string $decimal_separator        The decimal separator.
	 * @param ?string $currency_symbol_position The currency symbol position.
	 *
	 * @return void
	 */
	public static function set_defaults(
		?string $currency_symbol = null,
		?string $thousands_separator = null,
		?string $decimal_separator = null,
		?string $currency_symbol_position = null
	) {
		self::$defaults = [
			'currency_symbol'          => $currency_symbol ?? '$',
			'thousands_separator'      => $thousands_separator ?? ',',
			'decimal_separator'        => $decimal_separator ?? '.',
			'currency_symbol_position' => $currency_symbol_position ?? 'before',
		];
	}

	/**
	 * Add a value to the current value.
	 *
	 * @since 5.18.0
	 *
	 * @param Currency_Value $value The value to add.
	 *
	 * @return Currency_Value The new value object.
	 */
	public function add( Currency_Value $value ): Currency_Value {
		$result = $this->value->add( $value->get_raw_value() );

		return new self(
			$result,
			$this->currency_symbol,
			$this->thousands_separator,
			$this->decimal_separator,
			$this->currency_symbol_position
		);
	}

	/**
	 * Subtract a value from the current value.
	 *
	 * @since 5.18.0
	 *
	 * @param Currency_Value $value The value to subtract.
	 *
	 * @return Currency_Value The new value object.
	 */
	public function subtract( Currency_Value $value ): Currency_Value {
		$result = $this->value->subtract( $value->get_raw_value() );

		return new self(
			$result,
			$this->currency_symbol,
			$this->thousands_separator,
			$this->decimal_separator,
			$this->currency_symbol_position
		);
	}

	/**
	 * Add multiple values together.
	 *
	 * @since 5.18.0
	 *
	 * @param Currency_Value ...$values The values to add.
	 *
	 * @return Currency_Value The new value object.
	 */
	public static function sum( Currency_Value ...$values ): Currency_Value {
		$sum = new Precision_Value( 0 );

		foreach ( $values as $value ) {
			$sum = $sum->add( $value->get_raw_value() );
		}

		return static::create( $sum );
	}

	/**
	 * Multiply the current value by an integer.
	 *
	 * @since 5.18.0
	 *
	 * @param Integer_Value $value The value to multiply by.
	 *
	 * @return Currency_Value The new value object.
	 */
	public function multiply_by_integer( Integer_Value $value ): Currency_Value {
		$new_value = $this->value->multiply_by_integer( $value );

		return new self(
			$new_value,
			$this->currency_symbol,
			$this->thousands_separator,
			$this->decimal_separator,
			$this->currency_symbol_position
		);
	}

	/**
	 * Validate that the value is valid.
	 *
	 * @since 5.18.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return void
	 * @throws InvalidArgumentException When the value is not valid.
	 */
	protected function validate( $value ): void {}
}
