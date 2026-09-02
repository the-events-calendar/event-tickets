<?php
/**
 * Deprecated display settings for Event Tickets.
 *
 * @since 4.12.3
 *
 * @package Tribe\Tickets
 */

// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCase

// Third argument is $replacement, so leaving it off gets "with no alternative available".
_deprecated_file( __FILE__, 'TBD' );

/**
 * Manages the admin settings UI in relation to display configuration.
 *
 * The only settings this ever added were the two new views toggles, which are gone: the new views are
 * on for every site.
 *
 * @since 4.12.3
 *
 * @deprecated TBD
 */
class Tribe__Tickets__Admin__Display_Settings {

	/**
	 * Add display settings on the Events > Settings > Display tab.
	 *
	 * @since 4.12.3
	 *
	 * @deprecated TBD
	 */
	public function hook() {
		_deprecated_function( __METHOD__, 'TBD' );
	}

	/**
	 * Add display settings for Event Tickets.
	 *
	 * @since 4.12.3
	 *
	 * @deprecated TBD
	 *
	 * @param array $settings List of display settings.
	 *
	 * @return array List of display settings, unchanged.
	 */
	public function add_display_settings( $settings ) {
		_deprecated_function( __METHOD__, 'TBD' );

		return $settings;
	}
}
