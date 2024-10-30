<?php
/**
 * Tickets Commerce functions.
 *
 * @since 5.3.1
 *
 * Helpers to work with and customize ticketing-related features.
 */

use TEC\Tickets\Commerce\Utils\Currency;


// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit( '-1' );
}

/**
 * Returns the Tickets Commerce currency code.
 *
 * @since 5.3.1
 *
 * @return string String containing the Tickets Commerce currency code.
 */
function tec_tickets_commerce_currency_code() {
	return Currency::get_currency_code();
}

/**
 * Returns the Tickets Commerce currency symbol.
 *
 * @since 5.3.1
 *
 * @return string String containing the Tickets Commerce currency symbol.
 */
function tec_tickets_commerce_currency_symbol() {
	return Currency::get_currency_symbol( tec_tickets_commerce_currency_code() );
}

/**
 * Returns the Tickets Commerce currency position.
 *
 * @since 5.4.2
 *
 * @return string String containing the Tickets Commerce currency position. Either `prefix` or `postfix`.
 */
function tec_tickets_commerce_currency_position() {
	return Currency::get_currency_symbol_position( tec_tickets_commerce_currency_code() );
}

/**
 * Returns the Tickets Commerce currency decimal point separator.
 *
 * @since 5.5.7
 *
 * @return string String containing the Tickets Commerce currency decimal point separator.
 */
function tec_tickets_commerce_currency_decimal_separator() : string {
	return Currency::get_currency_separator_decimal( tec_tickets_commerce_currency_code() );
}

/**
 * Returns the Tickets Commerce currency thousands separator.
 *
 * @since 5.5.7
 *
 * @return string String containing the Tickets Commerce currency thousands separator.
 */
function tec_tickets_commerce_currency_thousands_separator() : string {
	return Currency::get_currency_separator_thousands( tec_tickets_commerce_currency_code() );
}

/**
 * Returns the Tickets Commerce currency number of decimals.
 *
 * @since 5.5.7
 *
 * @return int Number of decimals for the Tickets Commerce currency.
 */
function tec_tickets_commerce_currency_number_of_decimals() : int {
	return Currency::get_currency_precision( tec_tickets_commerce_currency_code() );
}
