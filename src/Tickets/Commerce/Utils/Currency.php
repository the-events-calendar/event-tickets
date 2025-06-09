<?php

namespace TEC\Tickets\Commerce\Utils;

use TEC\Tickets\Commerce\Settings;
use Tribe__Cache;

/**
 * Class Currency Utils
 *
 * This class holds stateless methods used to properly set-up currencies in Tickets Commerce.
 *
 * @since 5.1.9
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
	 * The fallback currency code to use if none is found.
	 *
	 * @since 5.5.7
	 *
	 * @var string
	 */
	public static $currency_code_fallback_symbol = '&#x24;';

	/**
	 * The fallback currency thousands separator to use if none is found.
	 *
	 * @since 5.5.7
	 *
	 * @var string
	 */
	public static $currency_code_thousands_separator = ',';

	/**
	 * The fallback currency decimal separator to use if none is found.
	 *
	 * @since 5.5.7
	 *
	 * @var string
	 */
	public static $currency_code_decimal_separator = '.';

	/**
	 * The fallback number of decimals for currency.
	 *
	 * @since 5.5.7
	 *
	 * @var string
	 */
	public static $currency_code_number_of_decimals = '2';

	/**
	 * Unsupported Currency.
	 *
	 * @since 5.5.7
	 *
	 * @var array
	 */
	public static $unsupported_currency = [];

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
		$precision = static::$currency_code_number_of_decimals;

		if ( isset( $map[ $code ] ) ) {
			$precision = $map[ $code ]['decimal_precision'];
		}

		$precision = tribe_get_option( Settings::$option_currency_number_of_decimals, $precision );

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
		$separator = static::$currency_code_decimal_separator;

		if ( isset( $map[ $code ] ) ) {
			$separator = $map[ $code ]['decimal_point'];
		}

		$separator = tribe_get_option( Settings::$option_currency_decimal_separator, $separator );

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
		$separator = static::$currency_code_thousands_separator;

		if ( isset( $map[ $code ] ) ) {
			$separator = $map[ $code ]['thousands_sep'];
		}

		$separator = tribe_get_option( Settings::$option_currency_thousands_separator, $separator );

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
	 * @since 4.7
	 * @since 4.10.8 Set the default position of the Euro currency symbol to 'suffix' if site language is not English.
	 *
	 * @param int|null $post_id
	 *
	 * @return string
	 *
	 * @link https://en.wikipedia.org/wiki/Euro_sign#Use EU guideline stating symbol should be placed in front of the amount in
	 *                                                   English but after in most other languages.
	 *
	 */
	public static function get_currency_symbol_position( $code ) {
		$currency_position = tribe_get_option( Settings::$option_currency_position, 'prefix' );

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
		static $currency_map = null;

		// Generate the default currency map only once.
		if ( null === $currency_map ) {
			$currency_map = self::get_raw_currency_map();
		}

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
		$default_map = apply_filters( 'tec_tickets_commerce_default_currency_map', $currency_map );

		/** @var Tribe__Cache $cache */
		$cache     = tribe( 'cache' );
		$cache_key = 'tec_tc_stripe_default_currency_map';
		$map       = $cache[ $cache_key ] ?? false;

		// If not cached or the count is different, store the map in alpha order.
		if ( ! $map || ! is_array( $map ) || count( $map ) != count( $default_map ) ) {
			ksort( $default_map );
			$map                 = $default_map;
			$cache[ $cache_key ] = $map;
		}

		return $map;
	}

	/**
	 * Returns the raw currency map.
	 *
	 * @since 5.18.0
	 * @return array
	 */
	protected static function get_raw_currency_map(): array {
		return [
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
			'AED' => [
				'name'                  => __( 'United Arab Emirates dirham (AED)', 'event-tickets' ),
				'symbol'                => '&#x62f;.&#x625;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 2,
			],
			'AFN' => [
				'name'                  => __( 'Afghan afghani (AFN)', 'event-tickets' ),
				'symbol'                => '&#x60b;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 85,
			],
			'ALL' => [
				'name'                  => __( 'Albanian lek (ALL)', 'event-tickets' ),
				'symbol'                => 'L',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 115,
			],
			'AMD' => [
				'name'                  => __( 'Armenian dram (AMD)', 'event-tickets' ),
				'symbol'                => 'AMD',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 455,
			],
			'ANG' => [
				'name'                  => __( 'Netherlands Antillean guilder (ANG)', 'event-tickets' ),
				'symbol'                => '&fnof;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 2,
			],
			'AOA' => [
				'name'                  => __( 'Angolan kwanza (AOA)', 'event-tickets' ),
				'symbol'                => 'Kz',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 415,
			],
			'ARS' => [
				'name'                  => __( 'Argentine peso (ARS)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 110,
			],
			'AWG' => [
				'name'                  => __( 'Aruban florin (AWG)', 'event-tickets' ),
				'symbol'                => 'Afl.',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 2,
			],
			'AZN' => [
				'name'                  => __( 'Azerbaijani manat (AZN)', 'event-tickets' ),
				'symbol'                => 'AZN',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 2,
			],
			'BAM' => [
				'name'                  => __( 'Bosnia and Herzegovina convertible mark (BAM)', 'event-tickets' ),
				'symbol'                => 'KM',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 2,
			],
			'BND' => [
				'name'                  => __( 'Brunei dollar (BND)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 2,
			],
			'BZD' => [
				'name'                  => __( 'Belize dollar (BZD)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 3,
			],
			'LBP' => [
				'name'                  => __( 'Lebanese pound (LBP)', 'event-tickets' ),
				'symbol'                => '&#x644;.&#x644;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 23900,
			],
			'LKR' => [
				'name'                  => __( 'Sri Lankan rupee (LKR)', 'event-tickets' ),
				'symbol'                => '&#xdbb;&#xdd4;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 361,
			],
			'LRD' => [
				'name'                  => __( 'Liberian dollar (LRD)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 153,
			],
			'LSL' => [
				'name'                  => __( 'Lesotho loti (LSL)', 'event-tickets' ),
				'symbol'                => 'L',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 16,
			],
			'MAD' => [
				'name'                  => __( 'Moroccan dirham (MAD)', 'event-tickets' ),
				'symbol'                => '&#x62f;.&#x645;.',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 10,
			],
			'MDL' => [
				'name'                  => __( 'Moldovan leu (MDL)', 'event-tickets' ),
				'symbol'                => 'MDL',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 20,
			],
			'MGA' => [
				'name'                  => __( 'Malagasy ariary (MGA)', 'event-tickets' ),
				'symbol'                => 'Ar',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 4043,
			],
			'MKD' => [
				'name'                  => __( 'Macedonian denar (MKD)', 'event-tickets' ),
				'symbol'                => '&#x434;&#x435;&#x43d;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 58,
			],
			'MMK' => [
				'name'                  => __( 'Burmese kyat (MMK)', 'event-tickets' ),
				'symbol'                => 'Ks',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 1853,
			],
			'MNT' => [
				'name'                  => __( 'Mongolian tögrög (MNT)', 'event-tickets' ),
				'symbol'                => '&#x20ae;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 3103,
			],
			'MOP' => [
				'name'                  => __( 'Macanese pataca (MOP)', 'event-tickets' ),
				'symbol'                => 'P',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 9,
			],
			'MUR' => [
				'name'                  => __( 'Mauritian rupee (MUR)', 'event-tickets' ),
				'symbol'                => '&#x20a8;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 44,
			],
			'MVR' => [
				'name'                  => __( 'Maldivian rufiyaa (MVR)', 'event-tickets' ),
				'symbol'                => '.&#x783;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 16,
			],
			'MWK' => [
				'name'                  => __( 'Malawian kwacha (MWK)', 'event-tickets' ),
				'symbol'                => 'MK',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 818,
			],
			'MXN' => [
				'name'                  => __( 'Mexican peso (MXN)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 20,
			],
			'MYR' => [
				'name'                  => __( 'Malaysian ringgit (MYR)', 'event-tickets' ),
				'symbol'                => '&#082;&#077;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 5,
			],
			'MZN' => [
				'name'                  => __( 'Mozambican metical (MZN)', 'event-tickets' ),
				'symbol'                => 'MT',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 64,
			],
			'NAD' => [
				'name'                  => __( 'Namibian dollar (NAD)', 'event-tickets' ),
				'symbol'                => 'N&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 16,
			],
			'NGN' => [
				'name'                  => __( 'Nigerian naira (NGN)', 'event-tickets' ),
				'symbol'                => '&#8358;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 416,
			],
			'NIO' => [
				'name'                  => __( 'Nicaraguan córdoba (NIO)', 'event-tickets' ),
				'symbol'                => 'C&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 34,
			],
			'NPR' => [
				'name'                  => __( 'Nepalese rupee (NPR)', 'event-tickets' ),
				'symbol'                => '&#8360;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 125,
			],
			'PAB' => [
				'name'                  => __( 'Panamanian balboa (PAB)', 'event-tickets' ),
				'symbol'                => 'B/.',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 1,
			],
			'PEN' => [
				'name'                  => __( 'Peruvian sol (PEN)', 'event-tickets' ),
				'symbol'                => 'S/',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 4,
			],
			'PGK' => [
				'name'                  => __( 'Papua New Guinean kina (PGK)', 'event-tickets' ),
				'symbol'                => 'K',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 4,
			],
			'PHP' => [
				'name'                  => __( 'Philippine peso (PHP)', 'event-tickets' ),
				'symbol'                => '&#8369;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 53,
			],
			'PKR' => [
				'name'                  => __( 'Pakistani rupee (PKR)', 'event-tickets' ),
				'symbol'                => '&#8360;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 202,
			],
			'PYG' => [
				'name'                  => __( 'Paraguayan guaraní (PYG)', 'event-tickets' ),
				'symbol'                => '&#8370;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 6838,
			],
			'QAR' => [
				'name'                  => __( 'Qatari riyal (QAR)', 'event-tickets' ),
				'symbol'                => '&#x631;.&#x642;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 4,
			],
			'RON' => [
				'name'                  => __( 'Romanian leu (RON)', 'event-tickets' ),
				'symbol'                => 'lei',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 5,
			],
			'RSD' => [
				'name'                  => __( 'Serbian dinar (RSD)', 'event-tickets' ),
				'symbol'                => '&#1088;&#1089;&#1076;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 110,
			],
			'RWF' => [
				'name'                  => __( 'Rwandan franc (RWF)', 'event-tickets' ),
				'symbol'                => 'Fr',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 1028,
			],
			'SAR' => [
				'name'                  => __( 'Saudi riyal (SAR)', 'event-tickets' ),
				'symbol'                => '&#x631;.&#x633;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 4,
			],
			'SBD' => [
				'name'                  => __( 'Solomon Islands dollar (SBD)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 9,
			],
			'SCR' => [
				'name'                  => __( 'Seychellois rupee (SCR)', 'event-tickets' ),
				'symbol'                => '&#x20a8;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 14,
			],
			'BBD' => [
				'name'                  => __( 'Barbadian dollar (BBD)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 2,
			],
			'BDT' => [
				'name'                  => __( 'Bangladeshi taka (BDT)', 'event-tickets' ),
				'symbol'                => '&#2547;&nbsp;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 88,
			],
			'BGN' => [
				'name'                  => __( 'Bulgarian lev (BGN)', 'event-tickets' ),
				'symbol'                => '&#1083;&#1074;.',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 2,
			],
			'BIF' => [
				'name'                  => __( 'Burundian franc (BIF)', 'event-tickets' ),
				'symbol'                => 'Fr',
				'decimal_point'         => '',
				'thousands_sep'         => ',',
				'decimal_precision'     => 0,
				'stripe_minimum_charge' => 2052,
			],
			'BMD' => [
				'name'                  => __( 'Bermudian dollar (BMD)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 1,
			],
			'BOB' => [
				'name'                  => __( 'Bolivian boliviano (BOB)', 'event-tickets' ),
				'symbol'                => 'Bs.',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 7,
			],
			'BSD' => [
				'name'                  => __( 'Bahamian dollar (BSD)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 1,
			],
			'BWP' => [
				'name'                  => __( 'Botswana pula (BWP)', 'event-tickets' ),
				'symbol'                => 'P',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 13,
			],
			'BYN' => [
				'name'                  => __( 'Belarusian ruble (BYN)', 'event-tickets' ),
				'symbol'                => 'Br',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 4,
			],
			'CDF' => [
				'name'                  => __( 'Congolese franc (CDF)', 'event-tickets' ),
				'symbol'                => 'Fr',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 2002,
			],
			'CLP' => [
				'name'                  => __( 'Chilean peso (CLP)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '',
				'thousands_sep'         => ',',
				'decimal_precision'     => 0,
				'stripe_minimum_charge' => 840,
			],
			'CNY' => [
				'name'                  => __( 'Chinese yuan (CNY)', 'event-tickets' ),
				'symbol'                => '&yen;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 7,
			],
			'COP' => [
				'name'                  => __( 'Colombian peso (COP)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 3950,
			],
			'CRC' => [
				'name'                  => __( 'Costa Rican colón (CRC)', 'event-tickets' ),
				'symbol'                => '&#x20a1;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 680,
			],
			'CVE' => [
				'name'                  => __( 'Cape Verdean escudo (CVE)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 105,
			],
			'DJF' => [
				'name'                  => __( 'Djiboutian franc (DJF)', 'event-tickets' ),
				'symbol'                => 'Fr',
				'decimal_point'         => '',
				'thousands_sep'         => ',',
				'decimal_precision'     => 0,
				'stripe_minimum_charge' => 190,
			],
			'DOP' => [
				'name'                  => __( 'Dominican peso (DOP)', 'event-tickets' ),
				'symbol'                => 'RD&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 60,
			],
			'DZD' => [
				'name'                  => __( 'Algerian dinar (DZD)', 'event-tickets' ),
				'symbol'                => '&#x62f;.&#x62c;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 150,
			],
			'EGP' => [
				'name'                  => __( 'Egyptian pound (EGP)', 'event-tickets' ),
				'symbol'                => 'EGP',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 19,
			],
			'ETB' => [
				'name'                  => __( 'Ethiopian birr (ETB)', 'event-tickets' ),
				'symbol'                => 'Br',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 53,
			],
			'FJD' => [
				'name'                  => __( 'Fijian dollar (FJD)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 3,
			],
			'GEL' => [
				'name'                  => __( 'Georgian lari (GEL)', 'event-tickets' ),
				'symbol'                => '&#x20be;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 3,
			],
			'GIP' => [
				'name'                  => __( 'Gibraltar pound (GIP)', 'event-tickets' ),
				'symbol'                => '&pound;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 1,
			],
			'GMD' => [
				'name'                  => __( 'Gambian dalasi (GMD)', 'event-tickets' ),
				'symbol'                => 'D',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 55,
			],
			'GNF' => [
				'name'                  => __( 'Guinean franc (GNF)', 'event-tickets' ),
				'symbol'                => 'Fr',
				'decimal_point'         => '',
				'thousands_sep'         => ',',
				'decimal_precision'     => 0,
				'stripe_minimum_charge' => 8850,
			],
			'GTQ' => [
				'name'                  => __( 'Guatemalan quetzal (GTQ)', 'event-tickets' ),
				'symbol'                => 'Q',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 8,
			],
			'GYD' => [
				'name'                  => __( 'Guyanese dollar (GYD)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 210,
			],
			'HNL' => [
				'name'                  => __( 'Honduran lempira (HNL)', 'event-tickets' ),
				'symbol'                => 'L',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 25,
			],
			'HTG' => [
				'name'                  => __( 'Haitian gourde (HTG)', 'event-tickets' ),
				'symbol'                => 'G',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 113,
			],
			'IDR' => [
				'name'                  => __( 'Indonesian rupiah (IDR)', 'event-tickets' ),
				'symbol'                => 'Rp',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 16000,
			],
			'ISK' => [
				'name'                  => __( 'Icelandic krona (ISK)', 'event-tickets' ),
				'symbol'                => 'Kr.',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 130,
			],
			'JMD' => [
				'name'                  => __( 'Jamaican dollar (JMD)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 156,
			],
			'KES' => [
				'name'                  => __( 'Kenyan shilling (KES)', 'event-tickets' ),
				'symbol'                => 'KSh',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 120,
			],
			'KGS' => [
				'name'                  => __( 'Kyrgyzstani som (KGS)', 'event-tickets' ),
				'symbol'                => '&#x441;&#x43e;&#x43c;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 82,
			],
			'KHR' => [
				'name'                  => __( 'Cambodian riel (KHR)', 'event-tickets' ),
				'symbol'                => '&#x17db;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 4300,
			],
			'KMF' => [
				'name'                  => __( 'Comorian franc (KMF)', 'event-tickets' ),
				'symbol'                => 'Fr',
				'decimal_point'         => '',
				'thousands_sep'         => ',',
				'decimal_precision'     => 0,
				'stripe_minimum_charge' => 490,
			],
			'KRW' => [
				'name'                  => __( 'South Korean won (KRW)', 'event-tickets' ),
				'symbol'                => '&#8361;',
				'decimal_point'         => '',
				'thousands_sep'         => ',',
				'decimal_precision'     => 0,
				'stripe_minimum_charge' => 1300,
			],
			'KYD' => [
				'name'                  => __( 'Cayman Islands dollar (KYD)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 1,
			],
			'KZT' => [
				'name'                  => __( 'Kazakhstani tenge (KZT)', 'event-tickets' ),
				'symbol'                => '&#8376;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 440,
			],
			'LAK' => [
				'name'                  => __( 'Lao kip (LAK)', 'event-tickets' ),
				'symbol'                => '&#8365;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 14000,
			],
			'SHP' => [
				'name'                  => __( 'Saint Helena pound (SHP)', 'event-tickets' ),
				'symbol'                => '&pound;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 1,
			],
			'SLL' => [
				'name'                  => __( 'Sierra Leonean leone (SLL)', 'event-tickets' ),
				'symbol'                => 'Le',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 14000,
			],
			'SOS' => [
				'name'                  => __( 'Somali shilling (SOS)', 'event-tickets' ),
				'symbol'                => 'Sh',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 600,
			],
			'SRD' => [
				'name'                  => __( 'Surinamese dollar (SRD)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 22,
			],
			'SZL' => [
				'name'                  => __( 'Swazi lilangeni (SZL)', 'event-tickets' ),
				'symbol'                => 'L',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 17,
			],
			'THB' => [
				'name'                  => __( 'Thai baht (THB)', 'event-tickets' ),
				'symbol'                => '&#3647;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 36,
			],
			'TJS' => [
				'name'                  => __( 'Tajikistani somoni (TJS)', 'event-tickets' ),
				'symbol'                => '&#x405;&#x41c;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 13,
			],
			'TOP' => [
				'name'                  => __( 'Tongan paʻanga (TOP)', 'event-tickets' ),
				'symbol'                => 'T&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 3,
			],
			'TRY' => [
				'name'                  => __( 'Turkish lira (TRY)', 'event-tickets' ),
				'symbol'                => '&#8378;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 17,
			],
			'TTD' => [
				'name'                  => __( 'Trinidad and Tobago dollar (TTD)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 8,
			],
			'TWD' => [
				'name'                  => __( 'New Taiwan dollar (TWD)', 'event-tickets' ),
				'symbol'                => '&#078;&#084;&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 30,
			],
			'TZS' => [
				'name'                  => __( 'Tanzanian shilling (TZS)', 'event-tickets' ),
				'symbol'                => 'Sh',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 2400,
			],
			'UAH' => [
				'name'                  => __( 'Ukrainian hryvnia (UAH)', 'event-tickets' ),
				'symbol'                => '&#8372;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 32,
			],
			'UGX' => [
				'name'                  => __( 'Ugandan shilling (UGX)', 'event-tickets' ),
				'symbol'                => 'UGX',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 3700,
			],
			'UYU' => [
				'name'                  => __( 'Uruguayan peso (UYU)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 41,
			],
			'UZS' => [
				'name'                  => __( 'Uzbekistani som (UZS)', 'event-tickets' ),
				'symbol'                => 'UZS',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 11500,
			],
			'VND' => [
				'name'                  => __( 'Vietnamese dong (VND)', 'event-tickets' ),
				'symbol'                => '&#8363;',
				'decimal_point'         => '',
				'thousands_sep'         => ',',
				'decimal_precision'     => 0,
				'stripe_minimum_charge' => 24000,
			],
			'VUV' => [
				'name'                  => __( 'Vanuatu vatu (VUV)', 'event-tickets' ),
				'symbol'                => 'Vt',
				'decimal_point'         => '',
				'thousands_sep'         => ',',
				'decimal_precision'     => 0,
				'stripe_minimum_charge' => 117,
			],
			'WST' => [
				'name'                  => __( 'Samoan tālā (WST)', 'event-tickets' ),
				'symbol'                => 'T',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 3,
			],
			'XAF' => [
				'name'                  => __( 'Central African CFA franc (XAF)', 'event-tickets' ),
				'symbol'                => 'CFA',
				'decimal_point'         => '',
				'thousands_sep'         => ',',
				'decimal_precision'     => 0,
				'stripe_minimum_charge' => 650,
			],
			'XCD' => [
				'name'                  => __( 'East Caribbean dollar (XCD)', 'event-tickets' ),
				'symbol'                => '&#036;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 3,
			],
			'XOF' => [
				'name'                  => __( 'West African CFA franc (XOF)', 'event-tickets' ),
				'symbol'                => 'CFA',
				'decimal_point'         => '',
				'thousands_sep'         => ',',
				'decimal_precision'     => 0,
				'stripe_minimum_charge' => 650,
			],
			'XPF' => [
				'name'                  => __( 'CFP franc (XPF)', 'event-tickets' ),
				'symbol'                => 'Fr',
				'decimal_point'         => '',
				'thousands_sep'         => ',',
				'decimal_precision'     => 0,
				'stripe_minimum_charge' => 120,
			],
			'YER' => [
				'name'                  => __( 'Yemeni rial (YER)', 'event-tickets' ),
				'symbol'                => '&#xfdfc;',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 252,
			],
			'ZMW' => [
				'name'                  => __( 'Zambian kwacha (ZMW)', 'event-tickets' ),
				'symbol'                => 'ZK',
				'decimal_point'         => '.',
				'thousands_sep'         => ',',
				'decimal_precision'     => 2,
				'stripe_minimum_charge' => 19,
			]
		];
	}

	/**
	 * Creates the array for a currency drop-down using only code & name.
	 *
	 * @since 5.3.2
	 *
	 * @return array<string, string>
	 */
	public function get_currency_code_options() {
		$currency_map = $this->get_default_currency_map();
		$options = array_combine(
			array_keys( $currency_map ),
			wp_list_pluck( $currency_map, 'name' )
		);

		/**
		 * Filters the currency code options shown to the user in the TC settings.
		 *
		 * @since 5.3.2
		 *
		 * @param array<string, string> $options
		 */
		return apply_filters( 'tec_tickets_commerce_currency_code_options', $options );
	}

	/**
	 * Get unsupported currencies and notice texts.
	 *
	 * @since 5.5.7
	 *
	 * @return array
	 */
	public static function get_unsupported_currencies(): array {
		/**
		 * Filter all unsupported currencies before returning.
		 *
		 * @since 5.5.7
		 *
		 * @return array
		 */
		return apply_filters( 'tec_tickets_commerce_unsupported_currencies', [
				'HRK' => [
					'heading'   => __( 'Tickets Commerce is now selling with Euro', 'event-tickets' ),
					'message'   => __( 'From the 1st of January 2023, the euro became the official currency for Croatia. We have removed the Croatian Kuna from our currency settings and updated your settings to start selling with Euro.', 'event-tickets' ),
					'new_value' => 'EUR',
				],
			]
		);
	}

	/**
	 * Verify if currency is supported.
	 *
	 * @since 5.5.7
	 *
	 * @return bool
	 */
	public static function is_current_currency_supported(): bool {
		// Get currency code option.
		$currency = tribe_get_option( static::$currency_code_option );

		// Get unsupported currencies.
		$unsupported_currencies = static::get_unsupported_currencies();

		if ( array_key_exists( $currency, $unsupported_currencies ) ) {
			// Get the unsupported currency.
			static::$unsupported_currency = $unsupported_currencies[ $currency ];

			// Get the currency symbol.
			$default_map = static::get_default_currency_map();
			static::$unsupported_currency['symbol'] = $default_map[ $currency ]['symbol'];

			// Update currency option to the new value.
			static::update_currency_option( $unsupported_currencies[ $currency ]['new_value'] );

			return false;
		}

		return true;
	}

	/**
	 * Update currency option to the new value if the currency is unsupported.
	 *
	 * @since 5.5.7
	 *
	 * @param string $new_currency_option
	 * @return void
	 */
	public static function update_currency_option( $new_currency_option ) {
		// Update currency option.
		tribe_update_option( static::$currency_code_option, $new_currency_option );
	}
}
