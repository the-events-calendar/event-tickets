<?php
/**
 * The money rule of the Recurring Event Tickets table.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use TEC\Tickets\Commerce\Utils\Currency;

/**
 * Class Price.
 *
 * Rows hold prices in minor units of the Tickets Commerce currency, with the currency's own decimals rather than
 * the site's display setting, as Order Line Items stores money.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Price {
	/**
	 * Converts minor units to the decimal string a ticket carries.
	 *
	 * @since TBD
	 *
	 * @param int $minor The amount in minor units.
	 *
	 * @return string The amount, e.g. `10.50`.
	 */
	public static function from_minor( int $minor ): string {
		$decimals = (int) ( Currency::get_default_currency_map()[ Currency::get_currency_code() ]['decimal_precision'] ?? 2 );

		return number_format( $minor / ( 10 ** $decimals ), $decimals, '.', '' );
	}
}
