<?php

namespace TEC\Tickets\Commerce\Utils;

/**
 * Class Currency Utils
 *
 * This class holds stateless methods used to properly set-up currencies in Tickets Commerce.
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce
 */
class Currency {

	/**
	 * The option key that stores the currency code in Tickets Commerce.
	 *
	 * @since 5.2.3
	 *
	 * @var string
	 */
	public static $currency_code_option = 'tickets-commerce-currency-code';

	/**
	 * The option key that was used to store the currency code in Tribe Commerce.
	 *
	 * @since 5.2.3
	 *
	 * @var string
	 */
	public static $legacy_currency_code_option = 'ticket-commerce-currency-code';

	/**
	 * The fallback currency code to use if none is found.
	 *
	 * @since 5.2.3
	 *
	 * @var string
	 */
	public static $currency_code_fallback = 'USD';

	/**
	 * Retrieves the working currency code.
	 *
	 * @since 5.2.3
	 *
	 * @return string
	 */

	public static function get_currency_code() {
		return tribe_get_option( static::$currency_code_option, static::$currency_code_fallback );
	}

	/**
	 * Retrieve a fallback currency code.
	 *
	 * @since 5.2.3
	 *
	 * @return string
	 */
	public static function get_currency_code_fallback() {

		// Check if we have a value set from Tribe Commerce.
		$currency_code = tribe_get_option( static::$legacy_currency_code_option, static::$currency_code_fallback );

		// Duplicate the currency code in the Tickets Commerce key.
		tribe_update_option( static::$currency_code_option, $currency_code );

		return $currency_code;
	}

	/**
	 * Return the currency symbol to use as defined in the currency map.
	 *
	 * @since 5.2.3
	 *
	 * @param string $code the currency 3-letter code
	 *
	 * @return string
	 */
	public static function get_currency_symbol( $code ) {
		$map    = static::get_default_currency_map();
		$symbol = '';

		if ( isset( $map[ $code ] ) ) {
			$symbol = $map[ $code ]['symbol'];
		}

		/**
		 * Filter the specific currency symbol before returning. $code is the 3-letter currency code.
		 *
		 * @since 5.2.3
		 *
		 * @param string $symbol The currency symbol.
		 *
		 * @return string
		 */
		$symbol = apply_filters( "tec_tickets_commerce_currency_{$code}_symbol", $symbol );

		/**
		 * Filter all currency symbols before returning.
		 *
		 * @since 5.2.3
		 *
		 * @param string $symbol The currency symbol.
		 *
		 * @return string
		 */
		return apply_filters( 'tec_tickets_commerce_currency_symbol', $symbol );
	}

	/**
	 * Return the currency name to use as defined in the currency map.
	 *
	 * @since 5.3.0
	 *
	 * @param string $code The currency 3-letter code.
	 *
	 * @return string
	 */
	public static function get_currency_name( $code ) {
		$map  = static::get_default_currency_map();
		$name = '';

		if ( isset( $map[ $code ] ) ) {
			$name = $map[ $code ]['name'];
		}

		/**
		 * Filter the specific currency name before returning. $code is the 3-letter currency code.
		 *
		 * @since 5.3.0
		 *
		 * @param string $name The currency name.
		 *
		 * @return string
		 */
		$name = apply_filters( "tec_tickets_commerce_currency_{$code}_name", $name );

		/**
		 * Filter all currency symbols before returning.
		 *
		 * @since 5.3.0
		 *
		 * @param string $name The currency name.
		 *
		 * @return string
		 */
		return apply_filters( 'tec_tickets_commerce_currency_name', $name );
	}

	/**
	 * Return the currency precision to use as the number of decimals allowed.
	 *
	 * @since 5.3.0
	 *
	 * @param string $code The currency 3-letter code.
	 *
	 * @return string
	 */
	public static function get_currency_precision( $code ) {
		$map       = static::get_default_currency_map();
		$precision = 2;

		if ( isset( $map[ $code ] ) ) {
			$precision = $map[ $code ]['decimal_precision'];
		}

		/**
		 * Filter the specific currency precision before returning. $code is the 3-letter currency code.
		 *
		 * @since 5.3.0
		 *
		 * @param int $precision The currency precision.
		 *
		 * @return int
		 */
		$precision = apply_filters( "tec_tickets_commerce_currency_{$code}_precision", $precision );

		/**
		 * Filter all currency symbols before returning.
		 *
		 * @since 5.3.0
		 *
		 * @param int $precision The currency precision.
		 *
		 * @return int
		 */
		return apply_filters( 'tec_tickets_commerce_currency_precision', $precision );
	}

	/**
	 * Return the currency decimal separator character to use as defined in the currency map.
	 *
	 * @since 5.2.3
	 *
	 * @param string $code the currency 3-letter code
	 *
	 * @return string
	 */
	public static function get_currency_separator_decimal( $code ) {
		$map       = static::get_default_currency_map();
		$separator = '';

		if ( isset( $map[ $code ] ) ) {
			$separator = $map[ $code ]['decimal_point'];
		}

		/**
		 * Filter the specific currency decimal separator before returning. $code is the 3-letter currency code.
		 *
		 * @since 5.2.3
		 *
		 * @param string $separator The currency decimal separator character.
		 *
		 * @return string
		 */
		$separator = apply_filters( "tec_tickets_commerce_currency_{$code}_separator_decimal", $separator );

		/**
		 * Filter all currency decimal separators before returning.
		 *
		 * @since 5.2.3
		 *
		 * @param string $separator The currency decimal separator character.
		 *
		 * @return string
		 */
		return apply_filters( 'tec_tickets_commerce_currency_separator_decimal', $separator );
	}

	/**
	 * Return the currency thousands separator character to use as defined in the currency map.
	 *
	 * @since 5.2.3
	 *
	 * @param string $code the currency 3-letter code
	 *
	 * @return string
	 */
	public static function get_currency_separator_thousands( $code ) {
		$map       = static::get_default_currency_map();
		$separator = '';

		if ( isset( $map[ $code ] ) ) {
			$separator = $map[ $code ]['thousands_sep'];
		}

		/**
		 * Filter the specific currency thousands separator before returning. $code is the 3-letter currency code.
		 *
		 * @since 5.2.3
		 *
		 * @param string $separator The currency thousands separator character.
		 *
		 * @return string
		 */
		$separator = apply_filters( "tec_tickets_commerce_currency_{$code}_separator_thousands", $separator );

		/**
		 * Filter all currency thousands separators before returning.
		 *
		 * @since 5.2.3
		 *
		 * @param string $separator The currency thousands separator character.
		 *
		 * @return string
		 */
		return apply_filters( 'tec_tickets_commerce_currency_separator_thousands', $separator );
	}

	/**
	 * Get and allow filtering of the currency symbol position.
	 *
	 * @since                                            4.7
	 * @since                                            4.10.8 Set the default position of the Euro currency symbol to
	 *                                                   'suffix' if site language is not English.
	 *
	 * @param int|null $post_id
	 *
	 * @return string
	 * @link                                             https://en.wikipedia.org/wiki/Euro_sign#Use EU guideline
	 *                                                   stating symbol should be placed in front of the amount in
	 *                                                   English but after in most other languages.
	 *
	 */
	public static function get_currency_symbol_position( $code ) {
		$map = static::get_default_currency_map();
		if ( ! isset( $map[ $code ]['position'] ) ) {
			$currency_position = 'prefix';
		} else {
			$currency_position = $map[ $code ]['position'];
		}

		if (
			'prefix' === $currency_position
			&& 'EUR' === $code
			&& 0 !== strpos( get_locale(), 'en_' ) // site language does not start with 'en_'
		) {
			$currency_position = 'postfix';
		}

		/**
		 * Whether the currency position should be 'prefix' or 'postfix' (i.e. suffix).
		 *
		 * @since 5.2.3
		 *
		 * @param string $currency_position The currency position string.
		 *
		 * @return string
		 */
		$currency_position = apply_filters( 'tec_tickets_commerce_currency_symbol_position', $currency_position );

		// Plugin's other code only accounts for one of these two values.
		if ( ! in_array( $currency_position, [ 'prefix', 'postfix' ], true ) ) {
			$currency_position = 'prefix';
		}

		return $currency_position;
	}

	/**
	 * Returns the default currency settings mapping.
	 *
	 * @see   https://en.wikipedia.org/wiki/Decimal_separator for separators informmation
	 * @since 5.2.3
	 *
	 */
	public static function get_default_currency_map() {

		/**
		 * Filter the default currency map before returning. This filter can be used to add or remove or modify how
		 * currencies are formatted in Event Tickets.
		 *
		 * @since 5.2.3
		 *
		 * @param array $currency_map The currency position string.
		 *
		 * @return array
		 */
		return apply_filters( 'tec_tickets_commerce_default_currency_map', [
			'AUD' => [
				'name'                  => __( 'Australian Dollar (AUD)', 'event-tickets' ),
				'symbol'                => '&#x41;&#x24;',
				'thousands_sep'         => ',',
				'decimal_point'         => '.',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 0.50,
			],
			'BRL' => [
				'name'                  => __( 'Brazilian Real  (BRL)', 'event-tickets' ),
				'symbol'                => '&#82;&#x24;',
				'thousands_sep'         => '.',
				'decimal_point'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 5.00, // minimum charge is 0.50, but boleto requires 5.00
			],
			'CAD' => [
				'name'                  => __( 'Canadian Dollar (CAD)', 'event-tickets' ),
				'symbol'                => '&#x24;',
				'thousands_sep'         => ',',
				'decimal_point'         => '.',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 0.50,
			],
			'CHF' => [
				'name'                  => __( 'Swiss Franc (CHF)', 'event-tickets' ),
				'symbol'                => '&#x43;&#x48;&#x46;',
				'decimal_point'         => ',',
				'thousands_sep'         => '.',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 0.50,
			],
			'CZK' => [
				'name'                  => __( 'Czech Koruna (CZK)', 'event-tickets' ),
				'symbol'                => '&#x4b;&#x10d;',
				'position'              => 'postfix',
				'decimal_point'         => ',',
				'thousands_sep'         => '.',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 15.00,
			],
			'DKK' => [
				'name'                  => __( 'Danish Krone (DKK)', 'event-tickets' ),
				'symbol'                => '&#107;&#114;',
				'decimal_point'         => ',',
				'thousands_sep'         => '.',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 2.50,
			],
			'EUR' => [
				'name'                  => __( 'Euro (EUR)', 'event-tickets' ),
				'symbol'                => '&#8364;',
				'decimal_point'         => ',',
				'thousands_sep'         => '.',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 0.50,
			],
			'GBP' => [
				'name'                  => __( 'Pound Sterling (GBP)', 'event-tickets' ),
				'symbol'                => '&#163;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 0.30,
			],
			'HKD' => [
				'name'                  => __( 'Hong Kong Dollar (HKD)', 'event-tickets' ),
				'symbol'                => '&#x24;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 4.00,
			],
			'HUF' => [
				'name'                  => __( 'Hungarian Forint (HUF)', 'event-tickets' ),
				'symbol'                => '&#x46;&#x74;',
				'decimal_point'         => ',',
				'thousands_sep'         => '.',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 175.00,
			],
			'ILS' => [
				'name'                  => __( 'Israeli New Sheqel (ILS)', 'event-tickets' ),
				'symbol'                => '&#x20aa;',
				'decimal_point'         => ',',
				'thousands_sep'         => '.',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => null,
			],
			'INR' => [
				'name'                  => __( 'Indian Rupee (INR)', 'event-tickets' ),
				'symbol'                => '&#x20B9;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 0.50,
			],
			'JPY' => [
				'name'                  => __( 'Japanese Yen (JPY)', 'event-tickets' ),
				'symbol'                => '&#165;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 0,
				'stripe_minimum_charge' => 50,
			],
			'MYR' => [
				'name'                  => __( 'Malaysian Ringgit (MYR)', 'event-tickets' ),
				'symbol'                => '&#82;&#77;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 2.00,
			],
			'MXN' => [
				'name'                  => __( 'Mexican Peso (MXN)', 'event-tickets' ),
				'symbol'                => '&#x24;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 10.00,
			],
			'NOK' => [
				'name'                  => __( 'Norwegian Krone (NOK)', 'event-tickets' ),
				'symbol'                => '',
				'decimal_point'         => ',',
				'thousands_sep'         => '.',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 3.00,
			],
			'NZD' => [
				'name'                  => __( 'New Zealand Dollar (NZD)', 'event-tickets' ),
				'symbol'                => '&#x24;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 0.50,
			],
			'PHP' => [
				'name'                  => __( 'Philippine Peso (PHP)', 'event-tickets' ),
				'symbol'                => '&#x20b1;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => null,
			],
			'PLN' => [
				'name'                  => __( 'Polish Zloty (PLN)', 'event-tickets' ),
				'symbol'                => '&#x7a;&#x142;',
				'decimal_point'         => ',',
				'thousands_sep'         => '.',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 2.00,
			],
			'RUB' => [
				'name'                  => __( 'Russian Ruble (RUB)', 'event-tickets' ),
				'symbol'                => '&#x20BD;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => null,
			],
			'SEK' => [
				'name'                  => __( 'Swedish Krona (SEK)', 'event-tickets' ),
				'symbol'                => '&#x6b;&#x72;',
				'decimal_point'         => ',',
				'thousands_sep'         => '.',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 3.00,
			],
			'SGD' => [
				'name'                  => __( 'Singapore Dollar (SGD)', 'event-tickets' ),
				'symbol'                => '&#x53;&#x24;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 0.50,
			],
			'THB' => [
				'name'                  => __( 'Thai Baht (THB)', 'event-tickets' ),
				'symbol'                => '&#x0e3f;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => null,
			],
			'TWD' => [
				'name'                  => __( 'Taiwan New Dollar (TWD)', 'event-tickets' ),
				'symbol'                => '&#x4e;&#x54;&#x24;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => null,
			],
			'USD' => [
				'name'                  => __( 'U.S. Dollar (USD)', 'event-tickets' ),
				'symbol'                => '&#x24;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 0.50,
			],
			'ZAR' => [
				'name'                  => __( 'South African Rand (ZAR)', 'event-tickets' ),
				'symbol'                => '&#082;',
				'decimal_point'         => '.',
				'thousands_sep'         => ' ',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 30,
			],
		] );
	}
}