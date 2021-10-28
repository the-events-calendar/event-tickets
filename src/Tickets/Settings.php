<?php
/**
 * The main class for managing settings related to Tickets Commerce.
 *
 * @since   5.1.6
 * @package TEC\Tickets
 */

namespace TEC\Tickets;

use TEC\Tickets\Commerce\Payments_Tab;

/**
 * Class Settings.
 *
 * @since   5.1.6
 *
 * @package TEC\Tickets
 */
class Settings {
	/**
	 * Holds the setting for loading or not the tickets commerce.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public static $tickets_commerce_enabled = 'tickets_commerce_enabled';

//	/**
//	 * Force enable TicketsCommerce for new users.
//	 *
//	 * @since TBD
//	 *
//	 * @param bool $enabled
//	 *
//	 * @return bool|mixed
//	 */
//	public static function force_enable_tickets_commerce( $enabled ) {
//
//		// If the tickets_commerce setting was saved before, then maintain the setting value.
//		if ( '' != tribe_get_option( Payments_Tab::$option_enable ) ) {
//			return $enabled;
//		}
//
//		return tribe_installed_after( 'Tribe__Tickets__Main', '5.1.10' );
//	}
}