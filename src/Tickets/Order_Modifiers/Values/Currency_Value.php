<?php

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\Values;

/**
 * Class Currency_Value
 *
 * @since TBD
 */
class Currency_Value implements Value_Interface {

	/**
	 * The value.
	 *
	 * @var Precision_Value
	 */
	protected Precision_Value $value;

	/**
	 * The Locale_Format instance for formatting the currency value.
	 *
	 * @var Locale_Format
	 */
	protected Locale_Format $locale_format;

	/**
	 * Currency_Value constructor.
	 *
	 * @since TBD
	 *
	 * @param Precision_Value $value           The value to store.
	 * @param array           $custom_settings Optional custom locale formatting settings.
	 */
	public function __construct(
		Precision_Value $value,
		array $custom_settings = []
	) {
		$this->value         = $value;
		$this->locale_format = new Locale_Format( $custom_settings );
	}

	/**
	 * Get the formatted value (unescaped symbol).
	 *
	 * @since TBD
	 *
	 * @return string The formatted value with the unescaped currency symbol.
	 */
	public function get(): string {
		return $this->locale_format->format_value(
			$this->value->get(),
			$this->value->get_precision()
		);
	}

	/**
	 * Get the formatted value with an escaped currency symbol.
	 *
	 * @since TBD
	 *
	 * @return string The formatted value with an escaped currency symbol.
	 */
	public function get_escaped_value(): string {
		return $this->locale_format->format_value(
			$this->value->get(),
			$this->value->get_precision(),
			true
		);
	}

	/**
	 * Get the raw value.
	 *
	 * @since TBD
	 *
	 * @return Precision_Value The raw value.
	 */
	public function get_raw_value(): Precision_Value {
		return clone $this->value;
	}

	/**
	 * The __toString method allows a class to decide how it will react when it is converted to a string.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->get();
	}

	/**
	 * Create a new instance of the class with default settings.
	 *
	 * @since TBD
	 *
	 * @param Precision_Value $value The value to store.
	 *
	 * @return Currency_Value
	 */
	public static function create( Precision_Value $value ): self {
		return new self( $value, Locale_Format::get_default_settings() );
	}

	/**
	 * Set the default formatting values.
	 *
	 * @since TBD
	 *
	 * @param ?string $currency_symbol          The currency symbol.
	 * @param ?string $thousands_separator      The thousands' separator.
	 * @param ?string $decimal_separator        The decimal separator.
	 * @param ?string $currency_symbol_position The symbol position - before, after.
	 *
	 * @return void
	 */
	public static function set_defaults(
		?string $currency_symbol = null,
		?string $thousands_separator = null,
		?string $decimal_separator = null,
		?string $currency_symbol_position = null
	): void {
		$defaults = [
			'symbol'              => $currency_symbol ?? '$',
			'thousands_separator' => $thousands_separator ?? ',',
			'decimal_separator'   => $decimal_separator ?? '.',
			'symbol_position'     => $currency_symbol_position ?? 'before',
		];

		Locale_Format::set_default_settings( $defaults );
	}

	/**
	 * Reset the default locale formatting to the initial state.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public static function reset_locale_to_defaults(): void {
		Locale_Format::set_default_settings(
			[
				'symbol'              => '$',
				'thousands_separator' => ',',
				'decimal_separator'   => '.',
				'symbol_position'     => 'before',
			]
		);
	}
}
