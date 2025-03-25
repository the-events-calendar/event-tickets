<?php
/**
 * Currency Value
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Values;

/**
 * Class Currency_Value
 *
 * @since 5.18.0
 */
class Currency_Value extends Base_Value {

	use Digit_Separators;

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
	 * The value.
	 *
	 * @var Precision_Value
	 */
	protected $value;

	/**
	 * Default values.
	 *
	 * @var array
	 */
	protected static array $defaults = [
		'currency_symbol'          => '$',
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
		$this->currency_symbol          = $currency_symbol;
		$this->thousands_separator      = $thousands_separator;
		$this->decimal_separator        = $decimal_separator;
		$this->currency_symbol_position = self::map_position( $currency_symbol_position );
		parent::__construct( $value );
	}

	/**
	 * Get the formatted value.
	 *
	 * @since 5.18.0
	 *
	 * @return string The value.
	 */
	public function get(): string {
		// If the value is negative, we need to remove the negative sign before formatting.
		if ( $this->value->get() < 0 ) {
			$value  = $this->value->invert_sign();
			$prefix = '- ';
		} else {
			$value  = $this->value;
			$prefix = '';
		}

		$formatted = $this->get_formatted_number( $value->get(), $value->get_precision() );

		switch ( $this->currency_symbol_position ) {
			case 'after':
				return "{$prefix}{$formatted}{$this->currency_symbol}";

			case 'before':
			default:
				return "{$prefix}{$this->currency_symbol}{$formatted}";
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
	 * @since 5.21.0 Added currency_symbol, thousands_separator, decimal_separator, and currency_symbol_position params.
	 *
	 * @param Precision_Value $value                    The value to store.
	 * @param ?string         $currency_symbol          The currency symbol. Will use the default if not provided.
	 * @param ?string         $thousands_separator      The thousands separator. Will use the default if not provided.
	 * @param ?string         $decimal_separator        The decimal separator. Will use the default if not provided.
	 * @param ?string         $currency_symbol_position The currency symbol position. Will use the default if
	 *                                                  not provided.
	 *
	 * @return Currency_Value The new instance.
	 */
	public static function create(
		Precision_Value $value,
		?string $currency_symbol = null,
		?string $thousands_separator = null,
		?string $decimal_separator = null,
		?string $currency_symbol_position = null
	): self {
		return new self(
			$value,
			$currency_symbol ?? self::$defaults['currency_symbol'],
			$thousands_separator ?? self::$separator_defaults['thousands_separator'],
			$decimal_separator ?? self::$separator_defaults['decimal_separator'],
			$currency_symbol_position ?? self::$defaults['currency_symbol_position']
		);
	}

	/**
	 * Create a new instance of the class from a float.
	 *
	 * @since 5.21.0
	 *
	 * @param float $value The value to store.
	 *
	 * @return Currency_Value The new instance.
	 */
	public static function create_from_float( float $value ): self {
		return self::create( new Precision_Value( $value ) );
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
		$position       = self::map_position( $currency_symbol_position ?? self::$defaults['currency_symbol_position'] );
		self::$defaults = [
			'currency_symbol'          => $currency_symbol ?? self::$defaults['currency_symbol'],
			'currency_symbol_position' => $position,
		];

		self::set_separator_defaults( $decimal_separator, $thousands_separator );
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
	 * Map a position to a valid value.
	 *
	 * @since 5.21.0
	 *
	 * @param string $position The position to map.
	 *
	 * @return string The mapped position, either 'before' or 'after'.
	 */
	protected static function map_position( string $position ): string {
		switch ( $position ) {
			case 'before':
			case 'after':
				return $position;

			case 'postfix':
				return 'after';

			case 'prefix':
			default:
				return 'before';
		}
	}
}
