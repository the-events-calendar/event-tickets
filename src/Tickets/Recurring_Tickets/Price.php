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
 * Rows hold prices in thousandths of the currency's unit, whatever the currency, so a change of the Tickets Commerce
 * currency never rescales them: they mean what the template's decimal price means. A ticket carries the amount with
 * the currency's own decimals, not the site's display setting, as Order Line Items stores money.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Price {
	/**
	 * How many stored units make one of the currency's unit.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const SCALE = 1000;

	/**
	 * Converts a stored price to the decimal string a ticket carries.
	 *
	 * @since TBD
	 *
	 * @param int $stored The price as stored, in thousandths of the currency's unit.
	 *
	 * @return string The amount, e.g. `10.50`.
	 */
	public static function to_decimal( int $stored ): string {
		$decimals = (int) ( Currency::get_default_currency_map()[ Currency::get_currency_code() ]['decimal_precision'] ?? 2 );

		return number_format( $stored / self::SCALE, $decimals, '.', '' );
	}

	/**
	 * Converts a price as Tickets Commerce stores it on a ticket to the thousandths a row stores.
	 *
	 * @since TBD
	 *
	 * @param mixed $price The price, a number or a numeric string; anything else is 0.
	 *
	 * @return int The price in thousandths of the currency's unit.
	 */
	public static function from_decimal( $price ): int {
		return is_numeric( $price ) ? (int) round( (float) $price * self::SCALE ) : 0;
	}
}
