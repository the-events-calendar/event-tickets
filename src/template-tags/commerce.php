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
