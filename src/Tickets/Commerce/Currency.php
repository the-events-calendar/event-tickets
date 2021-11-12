<?php

namespace TEC\Tickets\Commerce;

/**
 * Class Currency
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce
 */
class Currency {

	/**
	 * Retrieves the working currency set in Tribe Commerce or Tickets Commerce
	 *
	 * @todo @backend: due to time constraints, not all calls to tribe_get_option( 'ticket-commerce-currency-code', 'USD' )
	 *       were replaced by this method. We need to cleanup and have the entire codebase
	 *       read currency codes from this class in the future. Once all currency operations are fixed to use this method,
	 *       modify this method to delete the old keys from the settings array.
	 *
	 * @since TBD
	 *
	 * @return string
	 */

	public static function get_currency_code() {
		// New key
		$currency = tribe_get_option( 'tickets-commerce-currency-code' );

		if ( ! $currency ) {
			// Old key.
			$currency = tribe_get_option( 'ticket-commerce-currency-code', 'USD' );

			// Duplicate the currency code in the new key.
			tribe_update_option( 'tickets-commerce-currency-code', $currency );
		}

		return $currency;
	}

}