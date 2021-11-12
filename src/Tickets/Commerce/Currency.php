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
	 * The option key that stores the currency code in tribe options
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $currency_code_option = 'tickets-commerce-currency-code';

	/**
	 * Retrieves the working currency set in Tribe Commerce or Tickets Commerce
	 *
	 * @todo  @backend: due to time constraints, not all calls to tribe_get_option( 'ticket-commerce-currency-code' )
	 *       were replaced by this method. That key had a typo in it, and we're fixing that now. We need to clean up
	 *       and have the entire codebase read currency codes from this class in the future. Once all currency
	 *       operations are fixed to use this class, modify this method to delete the old keys from the settings array.
	 *
	 * @since TBD
	 *
	 * @return string
	 */

	public static function get_currency_code() {
		// New key
		$currency = tribe_get_option( static::$currency_code_option );

		if ( empty( $currency ) ) {
			// Old key.
			$currency = tribe_get_option( 'ticket-commerce-currency-code', 'USD' );

			// Duplicate the currency code in the new key.
			tribe_update_option( static::$currency_code_option, $currency );
		}

		return $currency;
	}

}