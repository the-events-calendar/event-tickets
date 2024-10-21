<?php

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\Values;

use InvalidArgumentException;

/**
 * Class Locale_Format
 *
 * This class provides locale-based formatting for values.
 *
 * @since TBD
 */
class Locale_Format {

	/**
	 * Allowed values for symbol position.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	protected array $allowed_symbol_positions = [ 'before', 'after' ];

	/**
	 * Default formatting settings.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected static array $default_settings = [
		'thousands_separator' => ',',
		'decimal_separator'   => '.',
		'symbol_position'     => 'before', // Can be 'before' or 'after'.
		'symbol'              => '$',      // Default to USD.
		'precision'           => 2,        // Default precision.
	];

	/**
	 * Instance-specific settings.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected array $settings;

	/**
	 * Locale constructor.
	 *
	 * @since TBD
	 *
	 * @param array $custom_settings An optional array to override the default settings.
	 */
	public function __construct( array $custom_settings = [] ) {
		$this->settings = array_merge( self::$default_settings, $custom_settings );
		$this->validate_settings();
	}

	/**
	 * Format a value based on the current locale settings, with an option to escape the symbol.
	 *
	 * @since TBD
	 *
	 * @param float|int $value         The value to format.
	 * @param ?int      $precision     Optional precision, will override the setting if provided.
	 * @param bool      $escape_symbol Whether to escape the currency symbol.
	 *
	 * @return string The formatted value.
	 */
	public function format_value( $value, ?int $precision = null, bool $escape_symbol = false ): string {
		$precision ??= $this->settings['precision'];

		$formatted_value = number_format(
			$value,
			$precision,
			$this->settings['decimal_separator'],
			$this->settings['thousands_separator']
		);

		// @todo - This doesn't actually function the expected way. We may want to do it similar to how Tribe__Tickets__Commerce__Currency did it.
		$symbol = $escape_symbol ? htmlspecialchars( $this->settings['symbol'], ENT_QUOTES ) : $this->settings['symbol'];

		if ( 'after' === $this->settings['symbol_position'] ) {
			return "{$formatted_value}{$symbol}";
		}

		return "{$symbol}{$formatted_value}";
	}

	/**
	 * Format a value without the currency symbol.
	 *
	 * @since TBD
	 *
	 * @param float|int $value     The value to format.
	 * @param ?int      $precision Optional precision, will override the setting if provided.
	 *
	 * @return string The formatted value without the symbol.
	 */
	public function format_value_without_symbol( $value, ?int $precision = null ): string {
		return number_format(
			$value,
			$precision ?? $this->settings['precision'],
			$this->settings['decimal_separator'],
			$this->settings['thousands_separator']
		);
	}

	/**
	 * Set new formatting settings.
	 *
	 * @since TBD
	 *
	 * @param array $custom_settings Settings to override the defaults.
	 *
	 * @throws InvalidArgumentException If the symbol_position is invalid.
	 */
	public function set_formatting_settings( array $custom_settings ): void {
		$this->settings = array_merge( $this->settings, $custom_settings );
		$this->validate_settings();
	}

	/**
	 * Validate the locale settings.
	 *
	 * @since TBD
	 *
	 * @throws InvalidArgumentException If the symbol_position is invalid.
	 */
	protected function validate_settings(): void {
		if ( ! in_array( $this->settings['symbol_position'], $this->allowed_symbol_positions, true ) ) {
			throw new InvalidArgumentException(
				sprintf(
					"Invalid symbol position '%s'. Allowed values are: %s",
					$this->settings['symbol_position'],
					implode( ', ', $this->allowed_symbol_positions )
				)
			);
		}
	}

	/**
	 * Get the current settings.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_formatting_settings(): array {
		return $this->settings;
	}

	/**
	 * Set the default settings globally for all instances.
	 *
	 * @since TBD
	 *
	 * @param array $defaults The new default settings.
	 *
	 * @throws InvalidArgumentException If the symbol_position is invalid.
	 */
	public static function set_default_settings( array $defaults ): void {
		self::$default_settings = array_merge( self::$default_settings, $defaults );
	}

	/**
	 * Return the default settings.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public static function get_default_settings(): array {
		return self::$default_settings;
	}
}
